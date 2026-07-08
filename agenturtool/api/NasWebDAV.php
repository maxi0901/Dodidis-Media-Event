<?php
declare(strict_types=1);

require_once __DIR__ . '/nas_env.php';

/**
 * Thin WebDAV client — streams data through PHP without buffering to Netcup disk.
 *
 * Zugangsdaten kommen aus nas_credentials() (siehe nas_env.php):
 *   1. Umgebungsvariablen NAS_DAV_BASE / NAS_DAV_USER / NAS_DAV_PASS
 *      (PHP-FPM-Pool "env[…]" oder .htaccess "SetEnv" — SetEnv wird unter FPM
 *       aber oft NICHT durchgereicht, daher der Fallback)
 *   2. Gitignorierte Datei agenturtool/config.nas.php
 *        return ['base' => 'https://…/pfad', 'user' => 'toolsvc', 'pass' => '…'];
 *
 * Tunnel-Wechsel: nur 'base' anpassen — kein Code-Change nötig.
 */
class NasWebDAV
{
    private string $base;
    private string $user;
    private string $pass;

    public function __construct()
    {
        $creds = nas_credentials();
        $base = $creds['base'];
        $user = $creds['user'];
        $pass = $creds['pass'];

        if ($base === '' || $user === '' || $pass === '') {
            throw new \RuntimeException(
                'NAS-Zugangsdaten fehlen: setze NAS_DAV_BASE/USER/PASS als PHP-FPM env[…] '
                . 'oder lege agenturtool/config.nas.php an (siehe config.nas.php.example).'
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
     * Raw-Uploads: kein Temp-File, keine Disk-Nutzung auf Netcup.
     *
     * @param int         $contentLength  Value of Content-Length header (0 = unknown/chunked)
     * @param string|null $teePath        Optional: Kopie beim Hochladen lokal mitschreiben
     *                                    (Review-Cache für finale Schnitte — bewusste
     *                                    Produktentscheidung, gilt nur für kind=final).
     *                                    Single-Pass: der Stream wird beim Durchleiten
     *                                    geschrieben, kein doppelter Transfer.
     */
    public function putStream(string $key, int $contentLength, string $contentType, ?string $teePath = null): void
    {
        $fh = fopen('php://input', 'r');
        if (!$fh) {
            throw new \RuntimeException('Konnte php://input nicht öffnen.');
        }

        $tee = null;
        if ($teePath !== null) {
            $tee = @fopen($teePath, 'wb');
            if (!$tee) {
                // Cache ist best-effort — Upload läuft ohne Kopie weiter
                error_log('[NasWebDAV] Tee-Datei nicht schreibbar: ' . $teePath);
            }
        }

        $ch = $this->newCurl($this->url($key));
        curl_setopt($ch, CURLOPT_UPLOAD, true);
        if ($tee) {
            curl_setopt($ch, CURLOPT_READFUNCTION, static function ($ch, $ignored, int $length) use ($fh, $tee): string {
                $chunk = fread($fh, $length);
                if ($chunk === false || $chunk === '') return '';
                fwrite($tee, $chunk);
                return $chunk;
            });
        } else {
            curl_setopt($ch, CURLOPT_INFILE, $fh);
        }
        if ($contentLength > 0) {
            curl_setopt($ch, CURLOPT_INFILESIZE, $contentLength);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: ' . $contentType,
            'Expect:',  // disable 100-continue round-trip
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        try {
            $code = $this->exec($ch);
        } catch (\Throwable $e) {
            fclose($fh);
            if ($tee) { fclose($tee); @unlink($teePath); }
            throw $e;
        }
        fclose($fh);

        if ($tee) {
            fclose($tee);
            // Unvollständige oder fehlgeschlagene Kopien nicht behalten
            $badSize = $contentLength > 0 && @filesize($teePath) !== $contentLength;
            if ($code !== 201 && $code !== 204 || $badSize) {
                @unlink($teePath);
            }
        }

        if ($code !== 201 && $code !== 204) {
            throw new \RuntimeException("PUT {$key} lieferte HTTP {$code}");
        }
    }

    /**
     * Stream a NAS file to the client response.
     * Sends Content-Disposition: attachment so the browser downloads it.
     * No buffering to Netcup disk.
     */
    public function passthru(string $key, string $filename, string $disposition = 'attachment'): void
    {
        [$ctype, $size, $lastMod, $etag] = $this->head($key);

        header('Content-Type: ' . ($ctype ?: 'application/octet-stream'));
        header('Content-Disposition: ' . $disposition . '; filename="' . str_replace('"', '\\"', $filename) . '"');
        header('Accept-Ranges: bytes');   // ermöglicht Fortsetzen/Chunk-Download
        header('Cache-Control: private, no-store');
        header('X-Accel-Buffering: no');  // nginx hint: disable proxy buffering
        // Validatoren → Browser kann einen abgebrochenen Download automatisch
        // fortsetzen (Range + If-Range).
        if ($lastMod !== '') header('Last-Modified: ' . $lastMod);
        if ($etag !== '')    header('ETag: ' . $etag);

        // Range-Anfrage des Browsers auswerten → Teilinhalt (206) + an NAS weitergeben.
        $curlRange = null;
        $range = $_SERVER['HTTP_RANGE'] ?? '';
        if ($size > 0 && preg_match('/^bytes=(\d*)-(\d*)$/', trim($range), $m) && ($m[1] !== '' || $m[2] !== '')) {
            if ($m[1] === '') {                       // Suffix: letzte N Bytes
                $start = max(0, $size - (int)$m[2]);
                $end   = $size - 1;
            } else {
                $start = (int)$m[1];
                $end   = ($m[2] === '') ? $size - 1 : min((int)$m[2], $size - 1);
            }
            if ($start > $end || $start >= $size) {
                http_response_code(416);
                header("Content-Range: bytes */{$size}");
                return;
            }
            $curlRange = $start . '-' . $end;
            http_response_code(206);
            header("Content-Range: bytes {$start}-{$end}/{$size}");
            header('Content-Length: ' . ($end - $start + 1));
        } elseif ($size > 0) {
            header('Content-Length: ' . $size);
        }

        $ch = $this->newCurl($this->url($key));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        // Große Streams: KEIN hartes Gesamt-Timeout (sonst brechen Multi-GB-
        // Downloads nach 1h bzw. bei langsamer Leitung ab). Stattdessen nur
        // Abbruch bei echtem Stillstand (<1 Byte/s über 120s).
        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
        curl_setopt($ch, CURLOPT_LOW_SPEED_LIMIT, 1);
        curl_setopt($ch, CURLOPT_LOW_SPEED_TIME, 120);
        if ($curlRange !== null) {
            curl_setopt($ch, CURLOPT_RANGE, $curlRange);
        }
        // In den Ausgabepuffer streamen; bei Client-Abbruch sauber stoppen.
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, static function ($ch, string $chunk): int {
            if (connection_aborted()) return 0; // signalisiert cURL: abbrechen
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
        // Last-Modified/ETag mitlesen → als Validatoren für Browser-Resume nutzbar
        $lastMod = ''; $etag = '';
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, static function ($ch, string $h) use (&$lastMod, &$etag): int {
            $p = explode(':', $h, 2);
            if (count($p) === 2) {
                $name = strtolower(trim($p[0]));
                if ($name === 'last-modified')   $lastMod = trim($p[1]);
                elseif ($name === 'etag')        $etag    = trim($p[1]);
            }
            return strlen($h);
        });

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
            return ['', 0, '', ''];
        }
        if ($code !== 200) {
            throw new \RuntimeException("HEAD {$key} lieferte HTTP {$code}");
        }
        return [$ct, max(0, $cl), $lastMod, $etag];
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

    /**
     * List files directly in a NAS directory (PROPFIND Depth: 1).
     * Nur Dateien (keine Unterordner), relativ zum Verzeichnis.
     *
     * @return array<int, array{name:string, size:int, ctype:string}>
     *         Leeres Array, wenn das Verzeichnis nicht existiert (404).
     */
    public function listDir(string $path): array
    {
        $dirUrl = $this->url(rtrim($path, '/')) . '/';
        $ch = $this->newCurl($dirUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PROPFIND');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Depth: 1', 'Content-Type: application/xml']);
        curl_setopt($ch, CURLOPT_POSTFIELDS,
            '<?xml version="1.0"?><d:propfind xmlns:d="DAV:"><d:prop>'
            . '<d:resourcetype/><d:getcontentlength/><d:getcontenttype/></d:prop></d:propfind>');

        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err) throw new \RuntimeException("PROPFIND {$path} cURL error: {$err}");
        if ($code === 404) return [];
        if ($code !== 207 && $code !== 200) {
            throw new \RuntimeException("PROPFIND {$path} lieferte HTTP {$code}");
        }

        return $this->parsePropfind((string)$body);
    }

    /** Parse a WebDAV multistatus XML into a flat file list (collections skipped). */
    private function parsePropfind(string $xml): array
    {
        if ($xml === '') return [];
        $prev = libxml_use_internal_errors(true);
        $doc = simplexml_load_string($xml);
        libxml_use_internal_errors($prev);
        if ($doc === false) return [];

        // DAV:-Namespace robust registrieren (Präfix variiert je nach Server)
        $ns = $doc->getNamespaces(true);
        $davPrefix = 'd';
        foreach ($ns as $p => $uri) {
            if (strtoupper((string)$uri) === 'DAV:') { $davPrefix = $p ?: 'd'; break; }
        }
        $doc->registerXPathNamespace('d', 'DAV:');

        $out = [];
        foreach ($doc->xpath('//d:response') ?: [] as $resp) {
            $resp->registerXPathNamespace('d', 'DAV:');
            $hrefN = $resp->xpath('d:href');
            if (!$hrefN) continue;
            $href = rawurldecode(trim((string)$hrefN[0]));

            // Kollektionen (Ordner) überspringen
            $isDir = $resp->xpath('.//d:resourcetype/d:collection');
            if ($href === '' || substr($href, -1) === '/' || $isDir) continue;

            $name = basename(rtrim($href, '/'));
            if ($name === '' || $name[0] === '.') continue; // versteckte Dateien ignorieren

            $sizeN  = $resp->xpath('.//d:getcontentlength');
            $ctypeN = $resp->xpath('.//d:getcontenttype');
            $out[] = [
                'name'  => $name,
                'size'  => $sizeN  ? (int)(string)$sizeN[0] : 0,
                'ctype' => $ctypeN ? (string)$ctypeN[0] : '',
            ];
        }
        return $out;
    }

    // ── private helpers ───────────────────────────────────────────────────────

    private function newCurl(string $url): \CurlHandle
    {
        $ch = curl_init($url);
        // BASIC präemptiv (nicht ANY): sendet die Credentials sofort mit.
        // ANY wartet auf die 401-Challenge und will den Body danach erneut
        // senden — php://input kann aber nicht zurückspulen ("necessary data
        // rewind wasn't possible") → Streaming-PUT schlägt fehl.
        curl_setopt($ch, CURLOPT_USERPWD,       $this->user . ':' . $this->pass);
        curl_setopt($ch, CURLOPT_HTTPAUTH,       CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_HTTP_VERSION,   CURL_HTTP_VERSION_1_1);
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
