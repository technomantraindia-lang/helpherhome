<?php
/**
 * Lightweight SMTP client (Gmail TLS / STARTTLS).
 */
class SimpleSmtpMailer
{
    private string $host;
    private int $port;
    private string $user;
    private string $pass;
    private $socket;

    public function __construct(string $host, int $port, string $user, string $pass)
    {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->pass = $pass;
    }

    public function send(
        string $fromEmail,
        string $fromName,
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        string $textBody = '',
        ?string $replyToEmail = null,
        ?string $replyToName = null
    ): void {
        $this->connect();
        $this->expect(220);

        $this->command('EHLO helperhome.local');
        $this->expect(250);

        $this->command('STARTTLS');
        $this->expect(220);

        if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw new RuntimeException('Unable to enable TLS encryption.');
        }

        $this->command('EHLO helperhome.local');
        $this->expect(250);

        $this->command('AUTH LOGIN');
        $this->expect(334);
        $this->command(base64_encode($this->user));
        $this->expect(334);
        $this->command(base64_encode($this->pass));
        $this->expect(235);

        $this->command('MAIL FROM:<' . $fromEmail . '>');
        $this->expect(250);
        $this->command('RCPT TO:<' . $toEmail . '>');
        $this->expect(250);
        $this->command('DATA');
        $this->expect(354);

        $boundary = 'b_' . bin2hex(random_bytes(12));
        $headers = [];
        $headers[] = 'Date: ' . date('r');
        $headers[] = 'From: ' . $this->formatAddress($fromName, $fromEmail);
        $headers[] = 'To: ' . $this->formatAddress($toName, $toEmail);
        if ($replyToEmail) {
            $headers[] = 'Reply-To: ' . $this->formatAddress($replyToName ?: $replyToEmail, $replyToEmail);
        }
        $headers[] = 'Subject: ' . $this->encodeHeader($subject);
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';
        $headers[] = 'X-Mailer: HelperHome-SMTP';

        if ($textBody === '') {
            $textBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], ["\n", "\n", "\n", "\n\n"], $htmlBody));
        }

        $body  = '--' . $boundary . "\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($textBody)) . "\r\n";
        $body .= '--' . $boundary . "\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($htmlBody)) . "\r\n";
        $body .= '--' . $boundary . "--\r\n";

        $message = implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.";
        $this->command($message);
        $this->expect(250);

        $this->command('QUIT');
        $this->close();
    }

    private function connect(): void
    {
        $errno = 0;
        $errstr = '';
        $this->socket = @stream_socket_client(
            'tcp://' . $this->host . ':' . $this->port,
            $errno,
            $errstr,
            30,
            STREAM_CLIENT_CONNECT
        );

        if (!$this->socket) {
            throw new RuntimeException('SMTP connection failed: ' . $errstr . ' (' . $errno . ')');
        }

        stream_set_timeout($this->socket, 30);
    }

    private function command(string $cmd): void
    {
        fwrite($this->socket, $cmd . "\r\n");
    }

    private function expect(int $code): void
    {
        $response = '';
        while (($line = fgets($this->socket, 515)) !== false) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        $got = (int) substr($response, 0, 3);
        if ($got !== $code) {
            throw new RuntimeException('SMTP unexpected reply. Expected ' . $code . ', got: ' . trim($response));
        }
    }

    private function close(): void
    {
        if (is_resource($this->socket)) {
            fclose($this->socket);
        }
        $this->socket = null;
    }

    private function formatAddress(string $name, string $email): string
    {
        $safeName = trim(str_replace(["\r", "\n"], '', $name));
        if ($safeName === '') {
            return '<' . $email . '>';
        }
        return '"' . addcslashes($safeName, '"\\') . '" <' . $email . '>';
    }

    private function encodeHeader(string $value): string
    {
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }
}
