<?php
declare(strict_types=1);

/**
 * Thin WebDAV client — streams data through PHP without buffering to Netcup disk.
 *
 * Credentials via environment variables (set in PHP-FPM pool config or .htaccess):
 *   NAS_DAV_BASE  e.g. https://ug.link/dmxbfnas/agentur-media
 *   NAS_DAV_USER  e.g. toolsvc
 *   NAS_DAV_PASS  (secret)
 *
 * Later switch to WireGuard tunnel: only change NAS_DAV_BASE — no code change needed.
 */
class NasWebDAV
{
    private string $base;
    private string $user;
    private string $pass;

    public function __construct()
    {
        // Apache SetEnv populates $_SERVER on some PHP-FPM setups but not getenv()
        $base = (string)(getenv('NAS_DAV_BASE') ?: ($_SERVER['NAS_DAV_BASE'] ?? '') ?: '');
        $user = (string)(getenv('NAS_DAV_USER') ?: ($_SERVER['NAS_DAV_USER'] ?? '') ?: '');
        $pass = (string)(getenv('NAS_DAV_PASS') ?: ($_SERVER['NAS_DAV_PASS'] ?? '') ?: '');

        if ($base === '' || $user === '' || $pass === '') {
            throw new \RuntimeException(
                'Umgebungsvariablen NAS_DAV_BASE, NAS_DAV_USER, NAS_DAV_PASS müssen gesetzt sein.'
            );
        }

        $this->base = rtrim($base, '/');
        $this->user = $user;
        $this->pass = $pass;
    }

    /** Full URL from a nas_key (relative path) — each segment rawurlencoded, slashes preserved */
    public function url(string $key): string
    {
        $segments = explode('/', ltrim($key, '/'));
        return $this->base . '/' . implode('/', array_map('rawurlencode', $segments));
    }

    /**
     * Create a directory path on the NAS, segment by segment.
     * Idempotent: 405 (already exists) is treated as success.
     */
    public function ensureDir(string $path): void
    {
        $built = '';
        foreach (explode('/', trim($path, '/')) as $part) {
            if ($part === '') continue;
            $built .= '/' . $part;
            $ch = $this->newCurl($this->url($built));
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'MKCOL');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $code = $this->exec($ch);
            if ($code !== 201 && $code !== 405) {
                throw new \RuntimeException("MKCOL {$built} lieferte HTTP {$code}");
            }
        }
    }

    /**
     * Stream php://input directly to NAS via PUT.
     * No temp file, no disk usage on Netcup.
     *
     * @param int $contentLength  Value of Content-Length header (0 = unknown/chunked)
     */
    public function putStream(string $key, int $contentLength, string $contentType): void
    {
        $fh = fopen('php://input', 'r');
        if (!$fh) {
            throw new \RuntimeException('Konnte php://input nicht öffnen.');
        }

        $ch = $this->newCurl($this->url($key));
        curl_setopt($ch, CURLOPT_PUT, true);
        curl_setopt($ch, CURLOPT_INFILE, $fh);
        if ($contentLength > 0) {
            curl_setopt($ch, CURLOPT_INFILESIZE, $contentLength);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: ' . $contentType,
            'Expect:',  // disable 100-continue round-trip
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $code = $this->exec($ch);
        fclose($fh);

        if ($code !== 201 && $code !== 204) {
            throw new \RuntimeException("PUT {$key} lieferte HTTP {$code}");
        }
    }

    /**
     * Stream a NAS file to the client response.
     * Sends Content-Disposition: attachment so the browser downloads it.
     * No buffering to Netcup disk.
     */
    public function passthru(string $key, string $filename): void
    {
        [$ctype, $size] = $this->head($key);

        header('Content-Type: ' . ($ctype ?: 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . str_replace('"', '\\"', $filename) . '"');
        if ($size > 0) {
            header('Content-Length: ' . $size);
        }
        header('Cache-Control: private, no-store');
        header('X-Accel-Buffering: no');  // nginx hint: disable proxy buffering

        $ch = $this->newCurl($this->url($key));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        // Write directly to output buffer in chunks
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, static function ($ch, string $chunk): int {
            echo $chunk;
            if (ob_get_level() > 0) ob_flush();
            flush();
            return strlen($chunk);
        });
        $this->exec($ch);
    }

    /**
     * HEAD request to check existence and get metadata.
     *
     * @return array{string, int}  [content-type, content-length]
     *                             ['', 0] when resource does not exist (404)
     */
    public function head(string $key): array
    {
        $ch = $this->newCurl($this->url($key));
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $ct   = (string)(curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: '');
        $cl   = (int)(curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD) ?? 0);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err) {
            throw new \RuntimeException("HEAD {$key} cURL error: {$err}");
        }
        if ($code === 404 || $code === 0) {
            return ['', 0];
        }
        if ($code !== 200) {
            throw new \RuntimeException("HEAD {$key} lieferte HTTP {$code}");
        }
        return [$ct, max(0, $cl)];
    }

    /**
     * Delete a file on the NAS.
     * 404 is silently ignored (idempotent).
     */
    public function delete(string $key): void
    {
        $ch = $this->newCurl($this->url($key));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $code = $this->exec($ch);
        if ($code !== 204 && $code !== 200 && $code !== 404) {
            throw new \RuntimeException("DELETE {$key} lieferte HTTP {$code}");
        }
    }

    // ── private helpers ───────────────────────────────────────────────────────

    private function newCurl(string $url): \CurlHandle
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_USERPWD,       $this->user . ':' . $this->pass);
        curl_setopt($ch, CURLOPT_HTTPAUTH,       CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_TIMEOUT,        3600);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        // System CA bundle — never disable TLS verification
        return $ch;
    }

    private function exec(\CurlHandle $ch): int
    {
        curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);
        if ($err) {
            throw new \RuntimeException("cURL error: {$err}");
        }
        return $code;
    }
}
