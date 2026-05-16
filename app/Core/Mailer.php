<?php

declare(strict_types=1);

namespace App\Core;

final class Mailer
{
    private static bool $fakeMode = false;
    private static array $sentMessages = [];

    public static function fake(): void
    {
        self::$fakeMode = true;
        self::$sentMessages = [];
    }

    public static function sent(): array
    {
        return self::$sentMessages;
    }

    public static function clear(): void
    {
        self::$fakeMode = false;
        self::$sentMessages = [];
    }

    public static function send(string $to, string $subject, string $html, string $text = null): bool
    {
        if (self::$fakeMode) {
            self::$sentMessages[] = [
                'to' => $to,
                'subject' => $subject,
                'html' => $html,
                'text' => $text ?? self::htmlToText($html),
            ];
            return true;
        }

        $from = Env::get('MAIL_FROM_ADDRESS', Env::get('MAIL_FROM', 'no-reply@skyare.space'));
        $fromName = Env::get('MAIL_FROM_NAME', 'Skyare Accounting');
        $host = Env::get('MAIL_HOST', '');
        $port = (int) Env::get('MAIL_PORT', '465');
        $user = Env::get('MAIL_USERNAME', '');
        $pass = Env::get('MAIL_PASSWORD', '');
        $encryption = strtolower(Env::get('MAIL_ENCRYPTION', 'ssl'));

        // Build the full message
        $boundary = '==Multipart_Boundary_x' . bin2hex(random_bytes(16));
        $plainText = $text ?? self::htmlToText($html);

        $msg  = 'From: ' . self::encodeHeader($fromName) . ' <' . $from . '>' . "\r\n";
        $msg .= 'To: ' . $to . "\r\n";
        $msg .= 'Subject: ' . self::encodeHeader($subject) . "\r\n";
        $msg .= 'MIME-Version: 1.0' . "\r\n";
        $msg .= 'Content-Type: multipart/alternative; boundary="' . $boundary . '"' . "\r\n";
        $msg .= "\r\n";
        $msg .= '--' . $boundary . "\r\n";
        $msg .= 'Content-Type: text/plain; charset=UTF-8' . "\r\n";
        $msg .= 'Content-Transfer-Encoding: 7bit' . "\r\n\r\n";
        $msg .= $plainText . "\r\n\r\n";
        $msg .= '--' . $boundary . "\r\n";
        $msg .= 'Content-Type: text/html; charset=UTF-8' . "\r\n";
        $msg .= 'Content-Transfer-Encoding: 7bit' . "\r\n\r\n";
        $msg .= $html . "\r\n\r\n";
        $msg .= '--' . $boundary . '--' . "\r\n";

        // If SMTP credentials are configured, try SMTP first, fall back to mail()
        if ($host !== '' && $user !== '' && $pass !== '') {
            try {
                return self::sendSmtp($host, $port, $encryption, $user, $pass, $from, $to, $msg);
            } catch (\Throwable $e) {
                error_log('[mailer_smtp_failed] ' . $e->getMessage() . ' — falling back to mail()');
            }
        }

        // Fallback to PHP mail()
        $headers  = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-Type: multipart/alternative; boundary="' . $boundary . '"' . "\r\n";
        $headers .= 'From: ' . self::encodeHeader($fromName) . ' <' . $from . '>' . "\r\n";

        $body = '--' . $boundary . "\r\n";
        $body .= 'Content-Type: text/plain; charset=UTF-8' . "\r\n\r\n";
        $body .= $plainText . "\r\n\r\n";
        $body .= '--' . $boundary . "\r\n";
        $body .= 'Content-Type: text/html; charset=UTF-8' . "\r\n\r\n";
        $body .= $html . "\r\n\r\n";
        $body .= '--' . $boundary . '--';

        return mail($to, $subject, $body, $headers);
    }

    private static function sendSmtp(string $host, int $port, string $encryption, string $user, string $pass, string $from, string $to, string $message): bool
    {
        $prefix = ($encryption === 'ssl' || $port === 465) ? 'ssl://' : '';
        $errno = 0;
        $errstr = '';
        $socket = @fsockopen($prefix . $host, $port, $errno, $errstr, 15);

        if (!$socket) {
            throw new \RuntimeException("SMTP connect failed: {$errstr} ({$errno})");
        }

        // Set a stream timeout so we don't hang forever
        stream_set_timeout($socket, 30);

        $greeting = self::smtpRead($socket);
        if (self::smtpCode($greeting) !== 220) {
            fclose($socket);
            throw new \RuntimeException("SMTP greeting error: {$greeting}");
        }

        // EHLO
        self::smtpWrite($socket, 'EHLO ' . gethostname());
        $ehloReply = self::smtpRead($socket);

        // STARTTLS for port 587
        if ($encryption === 'tls' && $port !== 465) {
            self::smtpWrite($socket, 'STARTTLS');
            $tlsReply = self::smtpRead($socket);
            if (self::smtpCode($tlsReply) !== 220) {
                fclose($socket);
                throw new \RuntimeException("STARTTLS failed: {$tlsReply}");
            }
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                fclose($socket);
                throw new \RuntimeException('STARTTLS crypto handshake failed');
            }
            self::smtpWrite($socket, 'EHLO ' . gethostname());
            self::smtpRead($socket);
        }

        // AUTH LOGIN
        self::smtpWrite($socket, 'AUTH LOGIN');
        $authReply = self::smtpRead($socket);
        if (self::smtpCode($authReply) !== 334) {
            fclose($socket);
            throw new \RuntimeException("AUTH LOGIN rejected: {$authReply}");
        }

        self::smtpWrite($socket, base64_encode($user));
        $userReply = self::smtpRead($socket);
        if (self::smtpCode($userReply) !== 334) {
            fclose($socket);
            throw new \RuntimeException("SMTP username rejected: {$userReply}");
        }

        self::smtpWrite($socket, base64_encode($pass));
        $passReply = self::smtpRead($socket);
        if (self::smtpCode($passReply) !== 235) {
            fclose($socket);
            throw new \RuntimeException("SMTP auth failed: {$passReply}");
        }

        // MAIL FROM
        self::smtpWrite($socket, 'MAIL FROM:<' . $from . '>');
        $fromReply = self::smtpRead($socket);
        if (self::smtpCode($fromReply) !== 250) {
            fclose($socket);
            throw new \RuntimeException("MAIL FROM rejected: {$fromReply}");
        }

        // RCPT TO
        self::smtpWrite($socket, 'RCPT TO:<' . $to . '>');
        $rcptReply = self::smtpRead($socket);
        if (self::smtpCode($rcptReply) !== 250 && self::smtpCode($rcptReply) !== 251) {
            fclose($socket);
            throw new \RuntimeException("RCPT TO rejected: {$rcptReply}");
        }

        // DATA
        self::smtpWrite($socket, 'DATA');
        $dataReply = self::smtpRead($socket);
        if (self::smtpCode($dataReply) !== 354) {
            fclose($socket);
            throw new \RuntimeException("DATA rejected: {$dataReply}");
        }

        // Send message body (dot-stuff lines starting with '.')
        $message = str_replace("\r\n.\r\n", "\r\n..\r\n", $message);
        fwrite($socket, $message . "\r\n.\r\n");
        $sendReply = self::smtpRead($socket);
        if (self::smtpCode($sendReply) !== 250) {
            fclose($socket);
            throw new \RuntimeException("Message rejected: {$sendReply}");
        }

        // QUIT
        self::smtpWrite($socket, 'QUIT');
        @fgets($socket);
        fclose($socket);

        return true;
    }

    private static function smtpWrite($socket, string $cmd): void
    {
        fwrite($socket, $cmd . "\r\n");
    }

    private static function smtpRead($socket): string
    {
        $response = '';
        while (true) {
            $line = @fgets($socket, 512);
            if ($line === false) break;
            $response .= $line;
            // SMTP multi-line responses have '-' at position 3; last line has ' '
            if (isset($line[3]) && $line[3] !== '-') break;
        }
        return trim($response);
    }

    private static function smtpCode(string $response): int
    {
        return (int) substr($response, 0, 3);
    }

    private static function encodeHeader(string $value): string
    {
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }

    private static function htmlToText(string $html): string
    {
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }
}
