<?php
declare(strict_types=1);

require_once __DIR__ . '/meta_env.php';

/**
 * Dünner Meta-Graph-API-Client für den Posting-Planer (Stufe C).
 *
 * App-Zugangsdaten kommen aus meta_config() (siehe meta_env.php):
 *   1. Umgebungsvariablen META_APP_ID / META_APP_SECRET / META_REDIRECT_URI /
 *      META_GRAPH_VERSION (SetEnv wird unter FPM aber oft NICHT durchgereicht)
 *   2. Gitignorierte Datei agenturtool/config.meta.php (Fallback)
 *
 * So bleibt das App-Secret außerhalb von Git.
 */
class MetaClient
{
    /** Von Meta empfohlene aktuelle Version bei Bedarf per Env anheben. */
    private const DEFAULT_GRAPH_VERSION = 'v21.0';

    /** Publish-Berechtigungen (App Review erforderlich für Live-Betrieb). */
    public const SCOPES = [
        'pages_show_list',
        'pages_read_engagement',
        'pages_manage_posts',
        'business_management',
        'instagram_basic',
        'instagram_content_publish',
        'instagram_manage_comments',   // Kommentare lesen & beantworten
        'instagram_manage_insights',   // Views/Reichweite je Post
        'instagram_manage_messages',   // Auto-DMs als Antwort auf Kommentare
    ];

    private string $appId;
    private string $appSecret;
    private string $version;
    private string $redirectUri;

    public function __construct(?string $redirectUri = null)
    {
        $cfg = meta_config();

        $this->appId     = $cfg['app_id'];
        $this->appSecret = $cfg['app_secret'];
        $this->version   = $cfg['graph_version'] ?: self::DEFAULT_GRAPH_VERSION;
        $this->redirectUri = $redirectUri
            ?: ($cfg['redirect_uri'] ?: '');

        if ($this->appId === '' || $this->appSecret === '') {
            throw new \RuntimeException(
                'Meta-App nicht konfiguriert: META_APP_ID/META_APP_SECRET als Env-Vars setzen '
                . 'oder agenturtool/config.meta.php anlegen (siehe config.meta.php.example).'
            );
        }
    }

    public function isConfigured(): bool
    {
        return $this->appId !== '' && $this->appSecret !== '';
    }

    public function redirectUri(): string { return $this->redirectUri; }
    public function version(): string     { return $this->version; }

    /** OAuth-Login-URL, auf die der Admin zum Autorisieren weitergeleitet wird. */
    public function loginUrl(string $state): string
    {
        $q = http_build_query([
            'client_id'     => $this->appId,
            'redirect_uri'  => $this->redirectUri,
            'state'         => $state,
            'scope'         => implode(',', self::SCOPES),
            'response_type' => 'code',
        ]);
        return "https://www.facebook.com/{$this->version}/dialog/oauth?{$q}";
    }

    /** Auth-Code gegen kurzlebiges User-Token tauschen. */
    public function exchangeCode(string $code): string
    {
        $res = $this->get('/oauth/access_token', [
            'client_id'     => $this->appId,
            'client_secret' => $this->appSecret,
            'redirect_uri'  => $this->redirectUri,
            'code'          => $code,
        ]);
        if (empty($res['access_token'])) {
            throw new \RuntimeException('Kein access_token im Token-Tausch erhalten.');
        }
        return (string)$res['access_token'];
    }

    /**
     * Kurzlebiges → langlebiges User-Token (~60 Tage).
     * @return array{token:string, expires_at:?string}
     */
    public function longLivedToken(string $shortToken): array
    {
        $res = $this->get('/oauth/access_token', [
            'grant_type'        => 'fb_exchange_token',
            'client_id'         => $this->appId,
            'client_secret'     => $this->appSecret,
            'fb_exchange_token' => $shortToken,
        ]);
        if (empty($res['access_token'])) {
            throw new \RuntimeException('Kein langlebiges Token erhalten.');
        }
        $expiresAt = null;
        if (!empty($res['expires_in'])) {
            $expiresAt = date('Y-m-d H:i:s', time() + (int)$res['expires_in']);
        }
        return ['token' => (string)$res['access_token'], 'expires_at' => $expiresAt];
    }

    /**
     * Seiten des Users samt (optional) verbundenem Instagram-Business-Account.
     * Page-Tokens aus einem langlebigen User-Token sind selbst langlebig.
     *
     * @return array<int, array{id:string,name:string,access_token:string,
     *                          ig_id:?string,ig_username:?string}>
     */
    public function fetchPages(string $userToken): array
    {
        $res = $this->get('/me/accounts', [
            'fields'       => 'id,name,access_token,instagram_business_account{id,username}',
            'access_token' => $userToken,
            'limit'        => 100,
        ]);
        $out = [];
        foreach (($res['data'] ?? []) as $p) {
            $out[] = [
                'id'           => (string)($p['id'] ?? ''),
                'name'         => (string)($p['name'] ?? ''),
                'access_token' => (string)($p['access_token'] ?? ''),
                'ig_id'        => isset($p['instagram_business_account']['id'])
                                    ? (string)$p['instagram_business_account']['id'] : null,
                'ig_username'  => isset($p['instagram_business_account']['username'])
                                    ? (string)$p['instagram_business_account']['username'] : null,
            ];
        }
        return $out;
    }

    /**
     * Instagram-Business: Reel (Video) oder Bild direkt veröffentlichen.
     * Ablauf: Media-Container erstellen → auf Verarbeitung warten → media_publish.
     * $mediaUrl MUSS eine öffentlich per HTTPS erreichbare URL sein (Meta lädt sie).
     *
     * @param bool $isVideo true = Reel (video_url), false = Bild (image_url)
     * @return array{id:string, permalink:?string}
     */
    public function publishInstagram(
        string $igUserId, string $pageToken, string $mediaUrl,
        string $caption, bool $isVideo, int $maxWaitSec = 180, ?string $coverUrl = null
    ): array {
        // 1. Media-Container erstellen
        $params = ['caption' => $caption, 'access_token' => $pageToken];
        if ($isVideo) {
            $params['media_type'] = 'REELS';
            $params['video_url']  = $mediaUrl;
            // Optionales Vorschaubild (Reel-Cover). Muss öffentlich per HTTPS
            // erreichbar sein (media_public.php, signiert).
            if ($coverUrl !== null && $coverUrl !== '') $params['cover_url'] = $coverUrl;
        } else {
            $params['image_url'] = $mediaUrl;
        }

        $c = $this->post("/{$igUserId}/media", $params);
        $creationId = (string)($c['id'] ?? '');
        if ($creationId === '') throw new \RuntimeException('Kein Media-Container von Instagram erhalten.');

        // 2. Auf Verarbeitung warten (Reels/Videos brauchen einige Sekunden)
        $deadline = time() + max(10, $maxWaitSec);
        while (true) {
            $st   = $this->get("/{$creationId}", ['fields' => 'status_code,status', 'access_token' => $pageToken]);
            $code = (string)($st['status_code'] ?? '');
            if ($code === 'FINISHED') break;
            if ($code === 'ERROR' || $code === 'EXPIRED') {
                throw new \RuntimeException('Media-Verarbeitung fehlgeschlagen: ' . (string)($st['status'] ?? $code));
            }
            if (time() >= $deadline) throw new \RuntimeException('Zeitüberschreitung bei der Media-Verarbeitung.');
            sleep(3);
        }

        // 3. Veröffentlichen
        $pub     = $this->post("/{$igUserId}/media_publish", ['creation_id' => $creationId, 'access_token' => $pageToken]);
        $mediaId = (string)($pub['id'] ?? '');
        if ($mediaId === '') throw new \RuntimeException('Veröffentlichen fehlgeschlagen (keine Media-ID).');

        // 4. Permalink (optional, best effort)
        $permalink = null;
        try {
            $pl = $this->get("/{$mediaId}", ['fields' => 'permalink', 'access_token' => $pageToken]);
            if (!empty($pl['permalink'])) $permalink = (string)$pl['permalink'];
        } catch (\Throwable $e) { /* Permalink ist optional */ }

        return ['id' => $mediaId, 'permalink' => $permalink];
    }

    /**
     * Basiszahlen (likes/comments — immer verfügbar) + Insights (Reichweite/
     * Views — best effort, braucht instagram_manage_insights) für einen IG-Media.
     * @return array<string,mixed>
     */
    public function getMediaStats(string $mediaId, string $token): array
    {
        $base = $this->get("/{$mediaId}", [
            'fields'       => 'id,caption,media_type,media_product_type,permalink,thumbnail_url,media_url,timestamp,like_count,comments_count',
            'access_token' => $token,
        ]);
        $type = (string)($base['media_product_type'] ?? ($base['media_type'] ?? ''));
        $out  = [
            'id'        => (string)($base['id'] ?? $mediaId),
            'permalink' => $base['permalink'] ?? null,
            'thumbnail' => $base['thumbnail_url'] ?? ($base['media_url'] ?? null),
            'mediaType' => $type,
            'timestamp' => $base['timestamp'] ?? null,
            'likes'     => isset($base['like_count'])     ? (int)$base['like_count']     : null,
            'comments'  => isset($base['comments_count']) ? (int)$base['comments_count'] : null,
            'views'     => null, 'reach' => null, 'saved' => null, 'shares' => null,
        ];
        // Insights best-effort. 'views' gibt es auch für FEED-Media (nicht nur
        // Reels) → für ALLE Typen anfragen; nur falls die API 'views' ablehnt,
        // ohne 'views' erneut (damit reach/saved/shares nicht verloren gehen).
        $ins = [];
        try {
            $ins = $this->get("/{$mediaId}/insights", ['metric' => 'reach,saved,shares,views', 'access_token' => $token]);
        } catch (\Throwable $e) {
            try {
                $ins = $this->get("/{$mediaId}/insights", ['metric' => 'reach,saved,shares', 'access_token' => $token]);
            } catch (\Throwable $e2) { $ins = []; }
        }
        foreach (($ins['data'] ?? []) as $m) {
            $name = (string)($m['name'] ?? '');
            $val  = (int)($m['values'][0]['value'] ?? 0);
            if (in_array($name, ['reach', 'saved', 'shares', 'views'], true)) $out[$name] = $val;
        }
        return $out;
    }

    /**
     * Kommentare eines IG-Media inkl. Antworten (neueste zuerst).
     * @return array<int,array<string,mixed>>
     */
    public function listComments(string $mediaId, string $token): array
    {
        $res = $this->get("/{$mediaId}/comments", [
            'fields'       => 'id,text,username,timestamp,like_count,replies{id,text,username,timestamp,like_count}',
            'access_token' => $token,
            'limit'        => 50,
        ]);
        return $res['data'] ?? [];
    }

    /** Auf einen Kommentar antworten. @return array<string,mixed> */
    public function replyToComment(string $commentId, string $token, string $message): array
    {
        return $this->post("/{$commentId}/replies", ['message' => $message, 'access_token' => $token]);
    }

    /**
     * WhatsApp Cloud API: 1:1-Textnachricht senden.
     * Nur innerhalb des 24-h-Kundenservice-Fensters frei formulierbar; außerhalb
     * verlangt Meta genehmigte Vorlagen (dann liefert die API einen Fehler).
     *
     * @param string $phoneNumberId ID der Business-Nummer (nicht die Nummer)
     * @param string $token         WhatsApp-Dauer-Token
     * @param string $to            Ziel-WhatsApp-ID (Telefonnummer, intl. ohne +)
     * @return array<string,mixed>  Antwort mit messages[0].id
     */
    public function sendWhatsAppText(string $phoneNumberId, string $token, string $to, string $text): array
    {
        $url = "https://graph.facebook.com/{$this->version}/{$phoneNumberId}/messages";
        $payload = json_encode([
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $to,
            'type'              => 'text',
            'text'              => ['preview_url' => false, 'body' => $text],
        ], JSON_UNESCAPED_UNICODE);
        return $this->execJson($url, $token, (string)$payload);
    }

    /**
     * Instagram „Private Reply": als Reaktion auf einen Kommentar eine DM an den
     * Kommentierenden senden. Nutzt die Messaging-API des IG-Business-Kontos.
     * Voraussetzung: Berechtigung instagram_manage_messages + verbundene Seite;
     * nur innerhalb des von Meta erlaubten Fensters nach dem Kommentar.
     *
     * @param string $igUserId  ID des IG-Business-Kontos (Empfänger-/Absenderkonto)
     * @param string $token     Seiten-/Zugriffstoken des Kontos
     * @param string $commentId ID des auslösenden Kommentars
     * @return array<string,mixed>
     */
    public function sendInstagramPrivateReply(string $igUserId, string $token, string $commentId, string $text): array
    {
        $url = "https://graph.facebook.com/{$this->version}/{$igUserId}/messages";
        $payload = json_encode([
            'recipient' => ['comment_id' => $commentId],
            'message'   => ['text' => $text],
        ], JSON_UNESCAPED_UNICODE);
        return $this->execJson($url, $token, (string)$payload);
    }

    /** JSON-POST mit Bearer-Token (für WhatsApp). @return array<string,mixed> */
    private function execJson(string $url, string $token, string $json): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        $raw  = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err) throw new \RuntimeException("Graph-API cURL: {$err}");
        $j = json_decode((string)$raw, true);
        if (!is_array($j)) throw new \RuntimeException("Graph-API: ungültige Antwort (HTTP {$code}).");
        if (isset($j['error'])) {
            throw new \RuntimeException("Graph-API-Fehler: " . (string)($j['error']['message'] ?? 'Unbekannt'));
        }
        if ($code >= 400) throw new \RuntimeException("Graph-API lieferte HTTP {$code}.");
        return $j;
    }

    // ── HTTP-Helfer ───────────────────────────────────────────────────────────

    /** @return array<string,mixed> */
    public function get(string $path, array $params = []): array
    {
        $url = "https://graph.facebook.com/{$this->version}" . $path;
        if ($params) $url .= '?' . http_build_query($params);
        return $this->exec($url, 'GET', null);
    }

    /** @return array<string,mixed> */
    public function post(string $path, array $params = []): array
    {
        $url = "https://graph.facebook.com/{$this->version}" . $path;
        return $this->exec($url, 'POST', $params);
    }

    /** @return array<string,mixed> */
    private function exec(string $url, string $method, ?array $body): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($body));
        }
        // Systemzertifikate — TLS-Verifikation nie deaktivieren
        $raw  = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err) throw new \RuntimeException("Graph-API cURL: {$err}");
        $json = json_decode((string)$raw, true);
        if (!is_array($json)) {
            throw new \RuntimeException("Graph-API: ungültige Antwort (HTTP {$code}).");
        }
        if (isset($json['error'])) {
            $msg = (string)($json['error']['message'] ?? 'Unbekannter Fehler');
            // Secret/Token nie mitloggen; nur die Meta-Fehlermeldung durchreichen
            throw new \RuntimeException("Graph-API-Fehler: {$msg}");
        }
        if ($code >= 400) {
            throw new \RuntimeException("Graph-API lieferte HTTP {$code}.");
        }
        return $json;
    }
}
