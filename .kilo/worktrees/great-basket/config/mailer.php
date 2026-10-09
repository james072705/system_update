<?php
/**
 * Lightweight Pure-PHP SMTP Mailer (no external dependencies)
 * Supports STARTTLS on port 587 (Gmail SMTP).
 *
 * Usage:
 *   require_once __DIR__ . '/mail.php';
 *
 *   $result = send_email(
 *       'recipient@example.com',
 *       'Your OTP Code',
 *       'Your verification code is: 123456',
 *       '<h1>Your OTP</h1><p>Code: <b>123456</b></p>'
 *   );
 *
 * Returns: true on success, or an error string on failure.
 */
require_once __DIR__ . '/mail.php';

function smtp_connect_socket($host, $port, $timeout = 15) {
    $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
    if (!$socket) {
        throw new Exception("Could not connect to SMTP server: $errstr ($errno)");
    }
    return $socket;
}

function smtp_read_line($socket, &$code = null) {
    $line = '';
    while (!feof($socket)) {
        $char = fgets($socket, 515);
        if ($char === false) break;
        $line .= $char;
        if (isset($char[3]) && $char[3] === ' ') break; // 4th char is space -> end of reply
    }
    $code = (int)substr($line, 0, 3);
    return $line;
}

function smtp_command($socket, $cmd, $expectedCode = 250) {
    fwrite($socket, $cmd . "\r\n");
    $response = smtp_read_line($socket, $code);
    if ($code !== $expectedCode) {
        throw new Exception("SMTP error after '$cmd': " . trim($response));
    }
    return $response;
}

/**
 * Send an email via SMTP (STARTTLS).
 */
function send_email($to, $subject, $textBody, $htmlBody = '') {
    $host = SMTP_HOST;
    $port = SMTP_PORT;
    $user = SMTP_USER;
    $pass = SMTP_PASS;
    $from = SMTP_FROM;
    $fromName = SMTP_FROM_NAME;

    try {
        $socket = smtp_connect_socket($host, $port);

        // Say hello
        smtp_read_line($socket, $code);
        smtp_command($socket, "EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'), 250);

        // Start TLS
        smtp_command($socket, "STARTTLS", 220);
        $crypto = stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        if (!$crypto) {
            throw new Exception('Could not enable TLS encryption.');
        }

        // Re-Hello after TLS
        smtp_command($socket, "EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'), 250);

        // Authenticate via AUTH LOGIN
        smtp_command($socket, "AUTH LOGIN", 334);
        fwrite($socket, base64_encode($user) . "\r\n");
        smtp_read_line($socket, $code);
        if ($code !== 334) {
            throw new Exception('SMTP AUTH username rejected.');
        }
        fwrite($socket, base64_encode($pass) . "\r\n");
        smtp_read_line($socket, $code);
        if ($code !== 235) {
            throw new Exception('SMTP authentication failed. Check your app password.');
        }

        // Sender
        smtp_command($socket, "MAIL FROM:<$from>", 250);

        // Recipient
        smtp_command($socket, "RCPT TO:<$to>", 250);

        // Data
        smtp_command($socket, "DATA", 354);

        // Build message
        $headers = [];
        $headers[] = "From: $fromName <$from>";
        $headers[] = "To: <$to>";
        $headers[] = "Subject: " . mb_encode_mimeheader($subject, 'UTF-8');
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Date: " . date('r');

        if (!empty($htmlBody)) {
            $boundary = "=_b" . md5(uniqid() . microtime());
            $headers[] = "Content-Type: multipart/alternative; boundary=\"$boundary\"";
            $message = "--$boundary\r\n";
            $message .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
            $message .= $textBody . "\r\n\r\n";
            $message .= "--$boundary\r\n";
            $message .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
            $message .= $htmlBody . "\r\n\r\n";
            $message .= "--$boundary--";
        } else {
            $headers[] = "Content-Type: text/plain; charset=UTF-8";
            $message = $textBody;
        }

        $fullMsg = implode("\r\n", $headers) . "\r\n\r\n" . $message . "\r\n.";
        fwrite($socket, $fullMsg . "\r\n");
        smtp_read_line($socket, $code);
        if ($code !== 250) {
            throw new Exception('Message not accepted: ' . trim(smtp_read_line($socket)));
        }

        smtp_command($socket, "QUIT", 221);
        fclose($socket);

        return true;
    } catch (Exception $e) {
        return 'Email sending failed: ' . $e->getMessage();
    }
}

/**
 * Generate a numeric OTP code.
 */
function generate_otp() {
    $len = OTP_LENGTH;
    $otp = '';
    for ($i = 0; $i < $len; $i++) {
        $otp .= random_int(0, 9);
    }
    return $otp;
}

/**
 * Send the OTP email to a user.
 * Returns true on success, or an error string.
 */
function send_otp_email($to, $otp) {
    $subject = 'Your Verification Code - ' . SMTP_FROM_NAME;
    $expiryMinutes = OTP_EXPIRY_MINUTES;

    $textBody = "Hello,\n\n"
        . "Your verification code for " . SMTP_FROM_NAME . " is: $otp\n\n"
        . "This code will expire in $expiryMinutes minutes.\n\n"
        . "If you did not request this, please ignore this email.\n\n"
        . SMTP_FROM_NAME;

    $htmlBody = '<div style="font-family:Arial,sans-serif;background:#f4f6fb;padding:20px;">'
        . '<div style="max-width:420px;margin:auto;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08);">'
        . '<div style="background:linear-gradient(135deg,#e63946,#c1121f);padding:24px;text-align:center;">'
        . '<h2 style="color:#fff;margin:0;">' . SMTP_FROM_NAME . '</h2>'
        . '<p style="color:rgba(255,255,255,.85);margin:4px 0 0;">Email Verification</p>'
        . '</div>'
        . '<div style="padding:28px;text-align:center;">'
        . '<p style="color:#555;">Use the code below to verify your account:</p>'
        . '<div style="font-size:36px;font-weight:800;letter-spacing:10px;color:#e63946;background:#fdecee;padding:16px;border-radius:10px;margin:16px 0;">'
        . htmlspecialchars($otp)
        . '</div>'
        . '<p style="color:#777;font-size:13px;">This code expires in <b>' . $expiryMinutes . ' minutes</b>.</p>'
        . '</div>'
        . '<div style="background:#f8f9fc;padding:12px;text-align:center;color:#999;font-size:12px;">'
        . 'If you did not request this, please ignore this email.</div>'
        . '</div></div>';

    return send_email($to, $subject, $textBody, $htmlBody);
}
