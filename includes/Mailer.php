<?php

declare(strict_types=1);

class Mailer
{
    private static ?string $lastError = null;

    public static function getLastError(): ?string
    {
        return self::$lastError;
    }

    public static function isEnabled(): bool
    {
        return getSetting('smtp_enabled') === '1'
            && trim(getSetting('smtp_host', '') ?? '') !== ''
            && trim(getSetting('smtp_from_email', '') ?? '') !== '';
    }

    public static function send(string $to, string $subject, string $htmlBody, ?string $textBody = null): bool
    {
        self::$lastError = null;

        if (!self::isEnabled()) {
            self::$lastError = 'SMTP is not enabled or from email is missing.';
            return false;
        }

        if (!function_exists('stream_socket_client')) {
            self::$lastError = 'PHP streams are not available on this server.';
            return false;
        }

        $to = trim($to);
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            self::$lastError = 'Invalid recipient email address.';
            return false;
        }

        $host = trim(getSetting('smtp_host', '') ?? '');
        $port = (int) (getSetting('smtp_port', '587') ?: 587);
        $encryption = strtolower(trim(getSetting('smtp_encryption', 'tls') ?? 'tls'));
        $username = trim(getSetting('smtp_username', '') ?? '');
        $password = getSetting('smtp_password', '') ?? '';
        $fromEmail = trim(getSetting('smtp_from_email', '') ?? '');
        $fromName = trim(getSetting('smtp_from_name', 'DogeSeeds.org') ?? 'DogeSeeds.org');

        if ($port === 465 && $encryption === 'tls') {
            $encryption = 'ssl';
        } elseif ($port === 587 && $encryption === 'ssl') {
            $encryption = 'tls';
        }

        $textBody ??= strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $htmlBody));
        $ehloHost = self::ehloHost($fromEmail);

        $context = stream_context_create([
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ],
        ]);

        $remote = $encryption === 'ssl' ? "ssl://{$host}:{$port}" : "tcp://{$host}:{$port}";
        $socket = @stream_socket_client($remote, $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
        if (!$socket) {
            self::$lastError = "Could not connect to {$host}:{$port} ({$errstr}). Check host, port, and encryption (465=SSL, 587=TLS).";
            return false;
        }

        stream_set_timeout($socket, 30);

        try {
            self::expect(self::read($socket), [220], 'greeting');
            self::cmd($socket, 'EHLO ' . $ehloHost, [250], 'EHLO');

            if ($encryption === 'tls') {
                self::cmd($socket, 'STARTTLS', [220], 'STARTTLS');
                $cryptoMethod = self::tlsCryptoMethod();
                if (!@stream_socket_enable_crypto($socket, true, $cryptoMethod)) {
                    throw new RuntimeException('TLS handshake failed. Try SSL on port 465, or TLS on port 587.');
                }
                self::cmd($socket, 'EHLO ' . $ehloHost, [250], 'EHLO after STARTTLS');
            }

            if ($username !== '') {
                self::authenticate($socket, $username, $password);
            }

            self::cmd($socket, 'MAIL FROM:<' . $fromEmail . '>', [250], 'MAIL FROM');
            self::cmd($socket, 'RCPT TO:<' . $to . '>', [250, 251], 'RCPT TO');
            self::cmd($socket, 'DATA', [354], 'DATA');

            $message = self::buildMessage($fromEmail, $fromName, $to, $subject, $htmlBody, $textBody);
            fwrite($socket, $message . "\r\n.\r\n");
            self::expect(self::read($socket), [250], 'message body');
            self::cmd($socket, 'QUIT', [221], 'QUIT');
            fclose($socket);
            return true;
        } catch (Throwable $e) {
            @fclose($socket);
            self::$lastError = $e->getMessage();
            return false;
        }
    }

    private static function ehloHost(string $fromEmail): string
    {
        $parts = explode('@', $fromEmail);
        if (count($parts) === 2 && $parts[1] !== '') {
            return $parts[1];
        }
        $host = gethostname();
        return $host && $host !== 'localhost' ? $host : 'localhost';
    }

    private static function tlsCryptoMethod(): int
    {
        $methods = 0;
        foreach ([
            'STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT',
            'STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT',
            'STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT',
            'STREAM_CRYPTO_METHOD_TLS_CLIENT',
        ] as $const) {
            if (defined($const)) {
                $methods |= constant($const);
            }
        }
        return $methods ?: STREAM_CRYPTO_METHOD_TLS_CLIENT;
    }

    private static function authenticate($socket, string $username, string $password): void
    {
        try {
            self::cmd($socket, 'AUTH LOGIN', [334], 'AUTH LOGIN');
            self::cmd($socket, base64_encode($username), [334], 'AUTH username');
            self::cmd($socket, base64_encode($password), [235], 'AUTH password');
        } catch (RuntimeException $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, '(535)') || str_contains($msg, 'Authentication')) {
                throw new RuntimeException(
                    'Authentication failed. Use your full email as username and an app-specific password if your mail provider requires it.'
                );
            }
            throw $e;
        }
    }

    private static function buildMessage(
        string $fromEmail,
        string $fromName,
        string $to,
        string $subject,
        string $htmlBody,
        string $textBody
    ): string {
        $boundary = 'ds_' . bin2hex(random_bytes(8));
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $headers = [
            'From: ' . self::formatAddress($fromEmail, $fromName),
            'To: <' . $to . '>',
            'Subject: ' . $encodedSubject,
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
            'Date: ' . date('r'),
        ];

        $message = implode("\r\n", $headers) . "\r\n\r\n";
        $message .= '--' . $boundary . "\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $message .= chunk_split(base64_encode($textBody), 76, "\r\n");
        $message .= '--' . $boundary . "\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $message .= chunk_split(base64_encode($htmlBody), 76, "\r\n");
        $message .= '--' . $boundary . "--\r\n";

        return preg_replace('/\r\n\./', "\r\n..", $message) ?? $message;
    }

    private static function formatAddress(string $email, string $name): string
    {
        $safeName = str_replace(['"', "\r", "\n"], '', $name);
        return $safeName !== '' ? '"' . $safeName . '" <' . $email . '>' : '<' . $email . '>';
    }

    private static function cmd($socket, string $command, array $okCodes, string $step): string
    {
        if (!@fwrite($socket, $command . "\r\n")) {
            throw new RuntimeException("Could not send {$step} command to SMTP server.");
        }
        return self::expect(self::read($socket), $okCodes, $step);
    }

    private static function read($socket): string
    {
        $data = '';
        while (($line = @fgets($socket, 515)) !== false) {
            $data .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        if ($data === '') {
            throw new RuntimeException('SMTP server closed the connection unexpectedly.');
        }
        return $data;
    }

    private static function expect(string $response, array $okCodes, string $step): string
    {
        $code = (int) substr($response, 0, 3);
        if (!in_array($code, $okCodes, true)) {
            $detail = trim(preg_replace('/\s+/', ' ', $response) ?? $response);
            throw new RuntimeException("SMTP {$step} failed ({$code}): {$detail}");
        }
        return $response;
    }
}
