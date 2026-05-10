<?php
require_once __DIR__ . '/functions.php';

function hostingMailLog(string $line): void {
    $path = __DIR__ . '/../data/mail.log';
    $dir = dirname($path);
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    file_put_contents($path, '[' . hostingNow() . '] ' . $line . "\n", FILE_APPEND | LOCK_EX);
}

function hostingQueueMail(string $to, string $subject, string $body, bool $isHtml = false): string {
    $queue = hostingLoadData('mail_queue');
    $id = 'MAIL-' . hostingGenerateId();
    $queue[] = [
        'id' => $id,
        'to' => $to,
        'subject' => $subject,
        'body' => $body,
        'is_html' => $isHtml,
        'status' => 'queued',
        'attempts' => 0,
        'created_at' => hostingNow(),
        'sent_at' => null,
        'last_error' => null,
    ];
    hostingSaveData('mail_queue', $queue);
    return $id;
}

function hostingSendMail(string $to, string $subject, string $body, bool $isHtml = false): array {
    $settings = hostingGetSettings();
    $mail = $settings['mail'] ?? [];

    $fromEmail = (string)($mail['from_email'] ?? $settings['support_email'] ?? 'noreply@example.com');
    $fromName = (string)($mail['from_name'] ?? $settings['brand_name'] ?? 'CodexHost');
    $useSmtp = !empty($mail['use_smtp']);

    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Invalid recipient email.'];
    }

    $headers = [];
    $headers[] = 'From: ' . sprintf('%s <%s>', $fromName, $fromEmail);
    $headers[] = 'Reply-To: ' . ($settings['support_email'] ?? $fromEmail);
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: ' . ($isHtml ? 'text/html' : 'text/plain') . '; charset=UTF-8';

    if ($useSmtp && !empty($mail['smtp_host'])) {
        return hostingSmtpSend($mail, $fromEmail, $to, $subject, $body, $headers);
    }

    $ok = @mail($to, $subject, $body, implode("\r\n", $headers));
    if ($ok) return ['ok' => true, 'error' => null];

    hostingMailLog("mail() failed to={$to} subject=" . $subject);
    return ['ok' => false, 'error' => 'mail() failed (check server mail configuration).'];
}

function hostingDispatchMailQueue(int $limit = 10): array {
    $queue = hostingLoadData('mail_queue');
    if (empty($queue)) return ['sent' => 0, 'failed' => 0];

    $sent = 0;
    $failed = 0;
    foreach ($queue as &$item) {
        if ($sent + $failed >= $limit) break;
        if (($item['status'] ?? '') !== 'queued') continue;
        if (($item['attempts'] ?? 0) >= 5) continue;

        $item['attempts'] = (int)($item['attempts'] ?? 0) + 1;
        $result = hostingSendMail((string)($item['to'] ?? ''), (string)($item['subject'] ?? ''), (string)($item['body'] ?? ''), !empty($item['is_html']));
        if (!empty($result['ok'])) {
            $item['status'] = 'sent';
            $item['sent_at'] = hostingNow();
            $item['last_error'] = null;
            $sent++;
        } else {
            $item['status'] = 'queued';
            $item['last_error'] = (string)($result['error'] ?? 'Send failed');
            $failed++;
            hostingMailLog("send failed id=" . ($item['id'] ?? '') . " to=" . ($item['to'] ?? '') . " error=" . $item['last_error']);
        }
    }
    unset($item);

    hostingSaveData('mail_queue', $queue);
    return ['sent' => $sent, 'failed' => $failed];
}

function hostingSmtpSend(array $mail, string $from, string $to, string $subject, string $body, array $headers): array {
    $host = (string)($mail['smtp_host'] ?? '');
    $port = (int)($mail['smtp_port'] ?? 587);
    $secure = (string)($mail['smtp_secure'] ?? 'tls');
    $user = (string)($mail['smtp_user'] ?? '');
    $pass = (string)($mail['smtp_pass'] ?? '');
    $timeout = (int)($mail['smtp_timeout'] ?? 10);

    $remote = ($secure === 'ssl') ? "ssl://{$host}:{$port}" : "{$host}:{$port}";
    $fp = @stream_socket_client($remote, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT);
    if (!$fp) return ['ok' => false, 'error' => "SMTP connect failed: {$errstr} ({$errno})"];
    stream_set_timeout($fp, $timeout);

    $read = function () use ($fp): string {
        $data = '';
        while (!feof($fp)) {
            $line = fgets($fp, 512);
            if ($line === false) break;
            $data .= $line;
            if (preg_match('/^\\d{3} /', $line)) break;
        }
        return $data;
    };
    $write = function (string $cmd) use ($fp): void {
        fwrite($fp, $cmd . "\r\n");
    };
    $expect = function (string $resp, array $codes) {
        $code = (int)substr($resp, 0, 3);
        if (!in_array($code, $codes, true)) {
            throw new RuntimeException("SMTP unexpected response: {$resp}");
        }
    };

    try {
        $expect($read(), [220]);
        $write('EHLO localhost');
        $ehlo = $read();
        $expect($ehlo, [250]);

        if ($secure === 'tls') {
            $write('STARTTLS');
            $expect($read(), [220]);
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('SMTP STARTTLS failed.');
            }
            $write('EHLO localhost');
            $expect($read(), [250]);
        }

        if ($user !== '' && $pass !== '') {
            $write('AUTH LOGIN');
            $expect($read(), [334]);
            $write(base64_encode($user));
            $expect($read(), [334]);
            $write(base64_encode($pass));
            $expect($read(), [235]);
        }

        $write('MAIL FROM:<' . $from . '>');
        $expect($read(), [250]);
        $write('RCPT TO:<' . $to . '>');
        $expect($read(), [250, 251]);
        $write('DATA');
        $expect($read(), [354]);

        $msgHeaders = array_merge(
            ['To: ' . $to, 'Subject: ' . $subject],
            $headers
        );
        $data = implode("\r\n", $msgHeaders) . "\r\n\r\n" . $body;
        $data = preg_replace("/\\r?\\n/", "\r\n", $data);
        $data = str_replace("\r\n.\r\n", "\r\n..\r\n", $data);
        fwrite($fp, $data . "\r\n.\r\n");
        $expect($read(), [250]);
        $write('QUIT');
        fclose($fp);
        return ['ok' => true, 'error' => null];
    } catch (Throwable $e) {
        try { $write('QUIT'); } catch (Throwable $ignored) {}
        fclose($fp);
        hostingMailLog('SMTP error: ' . $e->getMessage());
        return ['ok' => false, 'error' => $e->getMessage()];
    }
}

