<?php
declare(strict_types=1);

require_once __DIR__ . '/claude_env.php';

/**
 * Dünner Anthropic-/Claude-API-Client (Messages API) für das Agenturtool.
 *
 * Zugangsdaten kommen aus claude_config() (siehe claude_env.php):
 *   1. Umgebungsvariable ANTHROPIC_API_KEY (SetEnv wird unter FPM aber oft NICHT
 *      durchgereicht)
 *   2. Gitignorierte Datei agenturtool/config.claude.php (Fallback)
 *
 * So bleibt der API-Key außerhalb von Git. Muster analog zu MetaClient.php.
 *
 * NUR serverseitig verwenden — der Key darf niemals ans Frontend gelangen.
 */
class ClaudeClient
{
    private const ENDPOINT    = 'https://api.anthropic.com/v1/messages';
    private const API_VERSION = '2023-06-01';

    private string $apiKey;
    private string $model;
    private int    $maxTokens;

    public function __construct()
    {
        $cfg = claude_config();
        $this->apiKey    = $cfg['api_key'];
        $this->model     = $cfg['model'];
        $this->maxTokens = $cfg['max_tokens'];

        if ($this->apiKey === '') {
            throw new \RuntimeException(
                'Claude-API nicht konfiguriert: ANTHROPIC_API_KEY als Env-Var setzen '
                . 'oder agenturtool/config.claude.php anlegen (siehe config.claude.php.example). '
                . 'Hinweis: Das Claude-Max-Abo ist KEIN API-Key — der Key kommt aus der Anthropic Console.'
            );
        }
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '';
    }

    public function model(): string
    {
        return $this->model;
    }

    /**
     * Einfachster Weg: System-Prompt + eine Nutzer-Nachricht → Antworttext.
     *
     * @param string   $system    Rolle/Anweisung (z. B. "Du bist Social-Media-Analyst …")
     * @param string   $userText  Die eigentliche Eingabe (Daten, Frage …)
     * @param int|null $maxTokens Optionales Limit für diese Anfrage
     * @return string  Zusammengesetzter Antworttext (alle text-Blöcke)
     * @throws \RuntimeException bei API-/Transportfehler
     */
    public function complete(string $system, string $userText, ?int $maxTokens = null): string
    {
        $res = $this->message(
            [['role' => 'user', 'content' => $userText]],
            $system,
            $maxTokens
        );
        return $this->textOf($res);
    }

    /**
     * Voller Messages-API-Aufruf. $messages = [['role'=>'user'|'assistant','content'=>…], …].
     * @param array<int,array{role:string,content:mixed}> $messages
     * @return array<string,mixed> Rohe API-Antwort
     * @throws \RuntimeException
     */
    public function message(array $messages, string $system = '', ?int $maxTokens = null): array
    {
        $payload = [
            'model'      => $this->model,
            'max_tokens' => $maxTokens && $maxTokens > 0 ? $maxTokens : $this->maxTokens,
            'messages'   => array_values($messages),
        ];
        if ($system !== '') $payload['system'] = $system;

        return $this->exec($payload);
    }

    /**
     * Extrahiert den reinen Text aus einer Messages-API-Antwort.
     * @param array<string,mixed> $res
     */
    public function textOf(array $res): string
    {
        $out = '';
        foreach (($res['content'] ?? []) as $block) {
            if (($block['type'] ?? '') === 'text') $out .= (string)($block['text'] ?? '');
        }
        return trim($out);
    }

    // ── HTTP ─────────────────────────────────────────────────────────────────

    /** @param array<string,mixed> $payload @return array<string,mixed> */
    private function exec(array $payload): array
    {
        $ch = curl_init(self::ENDPOINT);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_HTTPHEADER     => [
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: ' . self::API_VERSION,
                'content-type: application/json',
            ],
            CURLOPT_TIMEOUT        => 120,   // Analysen können ein paar Sekunden dauern
            CURLOPT_CONNECTTIMEOUT => 15,
            // Systemzertifikate — TLS-Verifikation nie deaktivieren
        ]);
        $raw  = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err) throw new \RuntimeException("Claude-API cURL: {$err}");

        $json = json_decode((string)$raw, true);
        if (!is_array($json)) {
            throw new \RuntimeException("Claude-API: ungültige Antwort (HTTP {$code}).");
        }
        if (($json['type'] ?? '') === 'error' || isset($json['error'])) {
            // Key nie mitloggen; nur die Fehlermeldung der API durchreichen
            $msg = (string)($json['error']['message'] ?? 'Unbekannter Fehler');
            throw new \RuntimeException("Claude-API-Fehler (HTTP {$code}): {$msg}");
        }
        if ($code >= 400) {
            throw new \RuntimeException("Claude-API lieferte HTTP {$code}.");
        }
        return $json;
    }
}
