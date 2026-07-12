<?php
declare(strict_types=1);

/**
 * Löst die Anthropic-/Claude-API-Zugangsdaten robust auf — in dieser Reihenfolge:
 *
 *   1. Umgebungsvariablen (getenv / $_SERVER)
 *        ANTHROPIC_API_KEY / CLAUDE_MODEL / CLAUDE_MAX_TOKENS.
 *        Achtung: .htaccess-"SetEnv" wird unter PHP-FPM je nach Setup NICHT an
 *        PHP durchgereicht — dann greift automatisch Schritt 2.
 *   2. Gitignorierte PHP-Datei  agenturtool/config.claude.php
 *        return ['api_key'=>…, 'model'=>…, 'max_tokens'=>…];
 *
 * So bleibt der API-Key aus dem Git-Repo heraus und funktioniert unabhängig
 * von Apache/FPM-Env — analog zu meta_env.php / nas_env.php.
 *
 * WICHTIG: Das ist NICHT das Claude-Max-Abo (Claude.ai/Desktop). Für die
 * Einbindung ins Agenturtool braucht es einen API-Key aus der Anthropic
 * Console (console.anthropic.com) — separat abgerechnet über die Organisation
 * der Geschäftspartner (dort Spend-Limit setzen).
 *
 * @return array{api_key:string,model:string,max_tokens:int}
 */
function claude_config(): array
{
    static $cache = null;
    if ($cache !== null) return $cache;

    $env = static fn(string $k): string =>
        (string)(getenv($k) ?: ($_SERVER[$k] ?? '') ?: '');

    $file = [];
    $cfgFile = __DIR__ . '/../config.claude.php';
    if (is_file($cfgFile)) {
        $tmp = require $cfgFile;
        if (is_array($tmp)) $file = $tmp;
    }

    $pick = static function (string $envKey, string $fileKey) use ($env, $file): string {
        $v = $env($envKey);
        if ($v === '' && isset($file[$fileKey])) $v = (string)$file[$fileKey];
        return $v;
    };

    $model = $pick('CLAUDE_MODEL', 'model') ?: 'claude-opus-4-8';
    $max   = (int)($pick('CLAUDE_MAX_TOKENS', 'max_tokens') ?: '2048');

    $cache = [
        'api_key'    => $pick('ANTHROPIC_API_KEY', 'api_key'),
        'model'      => $model,
        'max_tokens' => $max > 0 ? $max : 2048,
    ];
    return $cache;
}
