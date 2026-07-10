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
