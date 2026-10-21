<?php
/*
 * Copyright (C) 2012-2026 by TEQneers GmbH & Co. KG
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace PHPKnock;

/**
 * KnockService
 *
 * Encapsulates the fwknop execution logic: rate limiting, host validation,
 * command building, and process execution. Extracted from the controller
 * in public/index.php so that the logic can be unit-tested independently.
 */
class KnockService
{
    public const VALID_HMAC_DIGEST_TYPES = ['md5', 'sha1', 'sha256', 'sha384', 'sha512'];

    /**
     * Password file of the currently running execute() call, removed on destruction
     * in case the script gets interrupted.
     */
    private ?string $currentPasswordFile = null;

    public function __construct(
        private readonly string $fwknopCli,
        private readonly string $tmpPath,
        private readonly string $passwordFilePath,
        private readonly bool $verbose = false,
        private readonly ?string $auditLogPath = null,
        private readonly string $encryptionMode = 'rijndael',
        private readonly ?string $hmacDigestType = null,
        private readonly ?string $gpgRecipientKey = null,
        private readonly ?string $gpgSignerKey = null,
        private readonly ?string $gpgHomeDir = null,
    ) {
    }

    public function __destruct()
    {
        $this->removePasswordFile();
    }

    public static function isValidHmacDigestType(string $type): bool
    {
        return in_array($type, self::VALID_HMAC_DIGEST_TYPES, true);
    }

    /**
     * Appends an entry to the audit log file (if configured).
     *
     * Format: ISO-8601 timestamp, remote IP, allow IP, destination, result.
     */
    public function auditLog(string $remoteIp, string $allowIp, string $destination, string $result): void
    {
        if ($this->auditLogPath === null) {
            return;
        }
        $line = sprintf(
            "[%s] remote=%s allow=%s dst=%s result=%s\n",
            date('c'),
            self::escapeLogValue($remoteIp),
            self::escapeLogValue($allowIp),
            self::escapeLogValue($destination),
            self::escapeLogValue($result),
        );
        @file_put_contents($this->auditLogPath, $line, FILE_APPEND | LOCK_EX);
    }

    /**
     * Escapes control characters (e.g. newlines) so a value cannot forge additional log lines.
     */
    private static function escapeLogValue(string $value): string
    {
        return addcslashes($value, "\0..\37\177\\");
    }

    /**
     * Returns the client's IP address.
     *
     * Proxy headers (X-Forwarded-For, X-Real-IP) are only honoured when the direct peer
     * (REMOTE_ADDR) is one of the trusted proxies; otherwise any client could spoof them.
     *
     * @param array<mixed>  $server          Usually $_SERVER
     * @param array<string> $trustedProxies  IP addresses or CIDR ranges
     */
    public static function clientIp(array $server, array $trustedProxies): string
    {
        $remoteAddr = is_string($server['REMOTE_ADDR'] ?? null) ? $server['REMOTE_ADDR'] : '0.0.0.0';
        if (!self::isTrustedProxy($remoteAddr, $trustedProxies)) {
            return $remoteAddr;
        }

        // X-Forwarded-For: walk from the right (closest hop) and return the first untrusted address
        $forwarded = $server['HTTP_X_FORWARDED_FOR'] ?? null;
        if (is_string($forwarded) && $forwarded !== '') {
            $hops = array_reverse(array_map('trim', explode(',', $forwarded)));
            $last = null;
            foreach ($hops as $hop) {
                if (filter_var($hop, FILTER_VALIDATE_IP) === false) {
                    break;
                }
                $last = $hop;
                if (!self::isTrustedProxy($hop, $trustedProxies)) {
                    return $hop;
                }
            }
            if ($last !== null) {
                return $last;
            }
        }

        $realIp = $server['HTTP_X_REAL_IP'] ?? null;
        if (is_string($realIp) && filter_var(trim($realIp), FILTER_VALIDATE_IP) !== false) {
            return trim($realIp);
        }

        return $remoteAddr;
    }

    /**
     * Returns true when $ip matches one of the given IP addresses or CIDR ranges.
     *
     * @param array<string> $trustedProxies
     */
    public static function isTrustedProxy(string $ip, array $trustedProxies): bool
    {
        $ipBin = @inet_pton($ip);
        if ($ipBin === false) {
            return false;
        }
        foreach ($trustedProxies as $proxy) {
            $parts  = explode('/', $proxy, 2);
            $netBin = @inet_pton(trim($parts[0]));
            if ($netBin === false || strlen($netBin) !== strlen($ipBin)) {
                continue;
            }
            $bits = isset($parts[1]) ? (int)$parts[1] : strlen($netBin) * 8;
            if ($bits < 0 || $bits > strlen($netBin) * 8) {
                continue;
            }
            $bytes = intdiv($bits, 8);
            if (substr($ipBin, 0, $bytes) !== substr($netBin, 0, $bytes)) {
                continue;
            }
            $rest = $bits % 8;
            if ($rest === 0) {
                return true;
            }
            $mask = (0xFF << (8 - $rest)) & 0xFF;
            if ((ord($ipBin[$bytes]) & $mask) === (ord($netBin[$bytes]) & $mask)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns true when $httpHost is a valid "host" or "host:port" value (IPv6 in brackets).
     */
    public static function isValidHttpHost(string $httpHost): bool
    {
        if (!preg_match('/^(?:\[(?P<ipv6>[0-9a-fA-F:.]+)\]|(?P<host>[^:\[\]]+))(?::(?P<port>\d{1,5}))?$/', $httpHost, $m)) {
            return false;
        }
        if ((int)($m['port'] ?? 0) > 65535) {
            return false;
        }
        if (($m['ipv6'] ?? '') !== '') {
            return filter_var($m['ipv6'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
        }
        return self::isValidHost($m['host'] ?? '');
    }

    /**
     * Returns true when $host is a valid IPv4/IPv6 address or a valid RFC 1123 hostname.
     */
    public static function isValidHost(string $host): bool
    {
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return true;
        }
        // RFC 1123: each label 1-63 chars (letters, digits, hyphens), total <= 253
        return strlen($host) <= 253
            && (bool)preg_match(
                '/^(?:[a-zA-Z0-9](?:[a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)*[a-zA-Z0-9](?:[a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?$/',
                $host
            );
    }

    /**
     * Resolves the list of target hosts from the configuration value and
     * the form-submitted value.
     *
     * @param string|array<int|string, string>|null $configDestination  The $DESTINATION config value
     * @param mixed             $formValue          The form element's dbValue()
     * @return array<string>    List of host strings
     */
    public static function resolveHosts(string|array|null $configDestination, mixed $formValue): array
    {
        if (is_string($configDestination)) {
            // destination is given as fixed value by configuration
            if (!str_contains($configDestination, ';')) {
                return [$configDestination];
            }
            return array_map('trim', explode(';', $configDestination));
        }

        if ($formValue === null || $formValue === '' || $formValue === []) {
            return [];
        }

        if (is_array($configDestination)) {
            // destinations come from a dropdown — $formValue is an array of selected keys
            $hosts = [];
            $formArray = is_array($formValue) ? $formValue : [$formValue];
            foreach ($formArray as $key) {
                if (!is_string($key) && !is_int($key)) {
                    continue;
                }
                $keyStr = (string)$key;
                $keyInt = (int)$key;
                if ((string)$keyInt === $keyStr && isset($configDestination[$keyInt])) {
                    // Numeric key: the value is the hostname
                    $hosts[] = $configDestination[$keyInt];
                } elseif (isset($configDestination[$keyStr])) {
                    // String key: the key itself is the hostname, value is just a label
                    $hosts[] = $keyStr;
                }
                // anything else was not offered in the dropdown and is ignored
            }
            return $hosts;
        }

        // $configDestination is null — free-text input
        if (is_string($formValue)) {
            if (!str_contains($formValue, ';')) {
                return [$formValue];
            }
            return array_map('trim', explode(';', $formValue));
        }

        return [];
    }

    /**
     * Checks per-IP rate limit using a file lock in the tmp directory.
     *
     * Returns true when the request is within the allowed limit, false when exceeded.
     */
    public function checkRateLimit(string $ip, int $limit, int $window): bool
    {
        $file = $this->tmpPath . '/rl_' . md5($ip) . '.json';
        $fh   = @fopen($file, 'c+');
        if ($fh === false) {
            return true; // fail open: don't block if tmp is unexpectedly unwritable
        }

        flock($fh, LOCK_EX);

        $content  = stream_get_contents($fh);
        $decoded  = $content ? json_decode($content, true) : null;
        $now      = time();

        if (
            is_array($decoded)
            && array_key_exists('since', $decoded)
            && is_int($decoded['since'])
            && ($now - $decoded['since']) < $window
            && array_key_exists('count', $decoded)
            && is_int($decoded['count'])
        ) {
            $count = $decoded['count'] + 1;
            $since = $decoded['since'];
        } else {
            $count = 1;
            $since = $now;
        }
        $data = ['count' => $count, 'since' => $since];

        ftruncate($fh, 0);
        rewind($fh);
        $encoded = json_encode($data);
        if ($encoded !== false) {
            fwrite($fh, $encoded);
        }
        flock($fh, LOCK_UN);
        fclose($fh);

        return $data['count'] <= $limit;
    }

    /**
     * Builds the fwknop command arguments array.
     *
     * @param string      $allowIp              Source IP to allow
     * @param int|null    $configServerPort      Server port from config (null = user-supplied)
     * @param string|null $formServerPort        Server port from form input
     * @param string|null $configAccessPortList  Access port list from config (null = user-supplied)
     * @param string|null $formAccessPortList    Access port list from form input
     * @return array<string, string>  Keyed array of command arguments
     */
    public function buildCommand(
        string $allowIp,
        ?int $configServerPort,
        ?string $formServerPort,
        ?string $configAccessPortList,
        ?string $formAccessPortList,
    ): array {
        $execute = ['cli' => $this->fwknopCli];

        if ($this->verbose) {
            $execute['verbose'] = '--verbose';
        }

        if ($configServerPort !== null) {
            $execute['server-port'] = '--server-port ' . $configServerPort;
        } elseif ($formServerPort !== null && $formServerPort !== '') {
            $execute['server-port'] = '--server-port ' . escapeshellarg(
                escapeshellcmd($formServerPort)
            );
        }

        if ($configAccessPortList !== null) {
            $execute['A'] = '-A ' . escapeshellarg($configAccessPortList);
        } elseif ($formAccessPortList !== null && $formAccessPortList !== '') {
            $execute['A'] = '-A ' . escapeshellarg(
                escapeshellcmd(str_replace(' ', '', $formAccessPortList))
            );
        }

        $execute['a'] = '-a ' . escapeshellarg(escapeshellcmd($allowIp));

        if ($this->hmacDigestType !== null) {
            $execute['hmac-digest-type'] = '--hmac-digest-type ' . escapeshellarg($this->hmacDigestType);
        }

        if ($this->encryptionMode === 'gpg') {
            if ($this->gpgRecipientKey !== null) {
                $execute['gpg-recipient-key'] = '--gpg-recipient-key ' . escapeshellarg($this->gpgRecipientKey);
            }
            if ($this->gpgSignerKey !== null) {
                $execute['gpg-signer-key'] = '--gpg-signer-key ' . escapeshellarg($this->gpgSignerKey);
            }
            if ($this->gpgHomeDir !== null) {
                $execute['gpg-home-dir'] = '--gpg-home-dir ' . escapeshellarg($this->gpgHomeDir);
            }
        }

        return $execute;
    }

    /**
     * Executes fwknop for a single target host.
     *
     * Writes the encryption key to a unique password file (mode 0600), runs proc_open
     * with the fwknop CLI, collects output, and adds success/error messages.
     * The password file is always deleted after execution.
     *
     * @param string               $target         Destination host (IP or hostname)
     * @param string               $encryptionKey  The encryption/SPA key
     * @param array<string, string> $baseCommand   Command array from buildCommand()
     * @param Message              $message        Message collector for output
     * @param string               $charset        Character set for htmlspecialchars (default: UTF-8)
     */
    public function execute(
        string $target,
        string $encryptionKey,
        array $baseCommand,
        Message $message,
        string $charset = 'UTF-8',
        string $sourceIp = '',
        string $allowIp = '',
        ?string $hmacKey = null,
    ): void {
        if (!self::isValidHost($target)) {
            $message->addError(
                'Invalid destination: "' . htmlspecialchars($target, ENT_QUOTES, $charset) . '".'
            );
            $this->auditLog($sourceIp, $allowIp, $target, 'invalid');
            return;
        }

        $passContent = $target . ':' . $encryptionKey;
        if ($hmacKey !== null && $this->encryptionMode === 'rijndael') {
            $passContent .= ':' . $hmacKey;
        }
        // tempnam() atomically creates a unique file with mode 0600, so concurrent
        // requests never share or overwrite each other's keys
        $passwordFile = tempnam(dirname($this->passwordFilePath), basename($this->passwordFilePath) . '.');
        if ($passwordFile === false || realpath(dirname($passwordFile)) !== realpath(dirname($this->passwordFilePath))) {
            if ($passwordFile !== false) {
                unlink($passwordFile);
            }
            $message->addError('Unable to create password file.');
            $this->auditLog($sourceIp, $allowIp, $target, 'fail');
            return;
        }
        $this->currentPasswordFile = $passwordFile;

        try {
            file_put_contents($passwordFile, $passContent);

            $command = $baseCommand;
            $command['G'] = '-G ' . escapeshellarg($passwordFile);
            $command['D'] = '-D ' . escapeshellarg(escapeshellcmd($target));

            $cmd = implode(' ', $command) . ' 2>&1';

            $descriptorspec = [
                0 => ['pipe', 'r+'],  // STDIN
                1 => ['pipe', 'w'],   // STDOUT
                2 => ['pipe', 'w'],   // STDERR
            ];

            $env = [
                'HOME' => $this->tmpPath,
            ];

            $process = proc_open($cmd, $descriptorspec, $pipes, $this->tmpPath, $env);

            if (is_resource($process)) {
                fclose($pipes[0]);

                $output = (string)stream_get_contents($pipes[1]);
                $error  = (string)stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);

                // Close all pipes before proc_close to prevent deadlocks
                $return = proc_close($process);

                if ($return === 0) {
                    $message->addMessage(
                        'Knock send successfully to "' . $target
                        . '". With correct settings, you should be able to access the server for a limited time now.'
                    );
                    $this->auditLog($sourceIp, $allowIp, $target, 'success');
                } else {
                    if (!empty($output) && !empty($error)) {
                        $output .= "\n$error";
                    }
                    $output = preg_replace("(\n$)", '', $output) ?? $output;
                    $message->addError(
                        'Unable to execute fwknop. It says: "' . str_replace(
                            "\n",
                            "<br />\n",
                            htmlspecialchars($output, ENT_QUOTES, $charset) . '".'
                        )
                    );
                    $this->auditLog($sourceIp, $allowIp, $target, 'fail');
                }

                if ($this->verbose) {
                    $sanitizedCmd = str_replace($encryptionKey, '****', $cmd);
                    if ($hmacKey !== null) {
                        $sanitizedCmd = str_replace($hmacKey, '****', $sanitizedCmd);
                    }
                    $message->addMessage(
                        'Command:<br />' . htmlspecialchars(
                            $sanitizedCmd,
                            ENT_QUOTES,
                            $charset
                        )
                        . '<br /><br />Output:<br />' . str_replace(
                            "\n",
                            "<br />\n",
                            htmlspecialchars($output, ENT_QUOTES, $charset)
                        )
                    );
                }
            }
        } finally {
            $this->removePasswordFile();
        }
    }

    private function removePasswordFile(): void
    {
        if ($this->currentPasswordFile !== null && file_exists($this->currentPasswordFile)) {
            unlink($this->currentPasswordFile);
        }
        $this->currentPasswordFile = null;
    }
}
