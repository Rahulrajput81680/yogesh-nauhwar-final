<?php

if (!defined('ADMIN_INIT')) {
  die('Direct access not permitted');
}

require_once __DIR__ . '/../vendor/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/src/SMTP.php';

/**
 * Send an email using PHPMailer over SMTP.
 *
 * @param string $to         Recipient address
 * @param string $subject    Subject line
 * @param string $htmlBody   HTML body
 * @param string $plainBody  Optional plain-text body (auto-stripped from HTML if empty)
 * @param array  $options    Optional keys: debug (bool), replyToEmail (string), replyToName (string), toName (string), fromEmail (string), fromName (string)
 * @return bool
 */
function send_email(string $to, string $subject, string $htmlBody, string $plainBody = '', array $options = []): bool
{
  if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
    error_log('send_email aborted: invalid recipient email [' . $to . ']');
    return false;
  }

  if ($plainBody === '') {
    $plainBody = trim(strip_tags(preg_replace('/<br\s*\/?>/', "\n", $htmlBody)));
  }

  $smtpHost = defined('MAIL_SMTP_HOST') ? trim((string) MAIL_SMTP_HOST) : '';
  $smtpPort = defined('MAIL_SMTP_PORT') ? (int) MAIL_SMTP_PORT : 587;
  $smtpUser = defined('MAIL_SMTP_USER') ? trim((string) MAIL_SMTP_USER) : '';

  // IMPORTANT: Do NOT trim() the SMTP password.
  // Google App Passwords (16 chars) are often written with spaces, e.g. "lgqa xesz mjbw gvdo".
  // Gmail accepts them with or without spaces, but we must preserve the raw string from config.
  $smtpPass = defined('MAIL_SMTP_PASS') ? (string) MAIL_SMTP_PASS : '';

  $smtpEncryption = defined('MAIL_SMTP_ENCRYPTION') ? strtolower(trim((string) MAIL_SMTP_ENCRYPTION)) : 'tls';
  $smtpDebug      = !empty($options['debug']) || (defined('MAIL_SMTP_DEBUG') && MAIL_SMTP_DEBUG);

  $fromName = defined('MAIL_FROM_NAME') ? trim((string) MAIL_FROM_NAME) : '';
  if ($fromName === '') {
    $fromName = defined('PROJECT_NAME') ? PROJECT_NAME : 'Website';
  }

  $fromAddress = defined('MAIL_FROM_ADDRESS') ? trim((string) MAIL_FROM_ADDRESS) : '';
  $fromNameOverride = trim((string) ($options['fromName'] ?? ''));
  if ($fromNameOverride !== '') {
    $fromName = $fromNameOverride;
  }

  $fromAddressOverride = trim((string) ($options['fromEmail'] ?? ''));
  if ($fromAddressOverride !== '') {
    $fromAddress = $fromAddressOverride;
  }

  if (!filter_var($fromAddress, FILTER_VALIDATE_EMAIL)) {
    error_log('send_email aborted: MAIL_FROM_ADDRESS is invalid [' . $fromAddress . ']');
    return false;
  }

  $toName = trim((string) ($options['toName'] ?? ''));
  $replyToEmail = trim((string) ($options['replyToEmail'] ?? ''));
  $replyToName  = trim((string) ($options['replyToName']  ?? ''));

  if ($smtpHost === '' || $smtpUser === '' || $smtpPass === '') {
    error_log(
      'send_email aborted: SMTP configuration is incomplete.'
      . ' Host=[' . $smtpHost . ']'
      . ' User=[' . $smtpUser . ']'
      . ' Pass=[' . (strlen($smtpPass) > 0 ? 'set' : 'empty') . ']'
    );
    return false;
  }

  if (!in_array($smtpEncryption, ['tls', 'ssl', 'none'], true)) {
    $smtpEncryption = 'tls';
  }

  // -----------------------------------------------------------------------
  // CRITICAL FIX (was inverted in previous version):
  // The old code checked "if (!$hasOpenSsl) { $smtpEncryption = 'none'; }"
  // which was WRONG — it downgraded to 'none' whenever OpenSSL IS loaded.
  // Gmail REQUIRES STARTTLS on port 587 and refuses plain-text connections,
  // so that caused all Gmail sends to silently fail.
  //
  // Correct behaviour: abort only when encryption is requested but OpenSSL
  // is NOT available. If OpenSSL is loaded (the normal case) we proceed with TLS.
  // -----------------------------------------------------------------------
  $hasOpenSsl = extension_loaded('openssl');
  if (!$hasOpenSsl && $smtpEncryption !== 'none') {
    error_log(
      'send_email aborted: OpenSSL PHP extension is required for ' . strtoupper($smtpEncryption)
      . ' encryption but is not loaded.'
      . ' Enable "extension=openssl" in php.ini and restart your web server.'
    );
    return false;
  }

  if ($smtpPort <= 0) {
    $smtpPort = 587;
  }

  try {
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

    // --- Transport ---
    $mail->isSMTP();
    $mail->Host     = $smtpHost;
    $mail->Port     = $smtpPort;
    $mail->SMTPAuth = true;
    $mail->Username = $smtpUser;
    $mail->Password = $smtpPass;
    $mail->CharSet  = 'UTF-8';
    $mail->Encoding = 'base64';
    // Higher timeout helps on slow XAMPP/localhost → Gmail first connections
    $mail->Timeout  = 30;

    // --- Debug: always pipe output to error_log so issues are never lost ---
    // Set MAIL_SMTP_DEBUG=true in config.php to see the full SMTP conversation
    $mail->SMTPDebug  = $smtpDebug
      ? \PHPMailer\PHPMailer\SMTP::DEBUG_SERVER
      : \PHPMailer\PHPMailer\SMTP::DEBUG_OFF;
    $mail->Debugoutput = static function (string $str, int $level): void {
      error_log('PHPMailer[' . $level . '] ' . rtrim($str));
    };

    // --- Encryption ---
    if ($smtpEncryption === 'tls') {
      // STARTTLS: connect plain on port 587, then negotiate TLS — correct for Gmail
      $mail->SMTPSecure  = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
      $mail->SMTPAutoTLS = true;
    } elseif ($smtpEncryption === 'ssl') {
      // SMTPS: full SSL wrapper on port 465 from byte zero
      $mail->SMTPSecure  = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
      $mail->SMTPAutoTLS = false;
    } else {
      // 'none': no encryption (only safe for local test SMTP such as Mailtrap sandbox)
      $mail->SMTPSecure  = '';
      $mail->SMTPAutoTLS = false;
    }

    // --- SSL context (relaxed peer verification) ---
    // Required on XAMPP/WAMP where PHP's CA bundle may not include Gmail's cert.
    // On a production server with a proper CA cert you can set verify_peer => true.
    $mail->SMTPOptions = [
      'ssl' => [
        'verify_peer'       => false,
        'verify_peer_name'  => false,
        'allow_self_signed' => true,
      ],
    ];

    // --- Message ---
    $mail->isHTML(true);
    $mail->setFrom($fromAddress, $fromName, false);
    $mail->addAddress($to, $toName !== '' ? $toName : $to);
    if ($replyToEmail !== '' && filter_var($replyToEmail, FILTER_VALIDATE_EMAIL)) {
      $mail->addReplyTo($replyToEmail, $replyToName !== '' ? $replyToName : $replyToEmail);
    }
    $mail->Subject = $subject;
    $mail->Body    = $htmlBody;
    $mail->AltBody = $plainBody;

    $sent = $mail->send();
    if (!$sent) {
      error_log('PHPMailer send() returned false. Error: ' . $mail->ErrorInfo);
    }

    return $sent;

  } catch (\PHPMailer\PHPMailer\Exception $e) {
    error_log('PHPMailer exception: ' . $e->getMessage());
    return false;
  } catch (\Throwable $e) {
    error_log('Mail send unexpected exception: ' . $e->getMessage());
    return false;
  } finally {
    if (isset($mail) && $mail instanceof \PHPMailer\PHPMailer\PHPMailer) {
      try {
        $mail->clearAddresses();
        $mail->clearAttachments();
      } catch (\Throwable $cleanupError) {
        // Silently ignore cleanup errors — they must not affect the return value.
      }
    }
  }
}

/**
 * SMTP Diagnostic — performs a full SMTP handshake (connect + EHLO + STARTTLS + AUTH)
 * without sending any actual message, and returns a detailed human-readable report.
 *
 * IMPORTANT: Only call this function from admin pages protected by login checks.
 * Never expose it on a public route.
 *
 * @return array{ok: bool, messages: string[]}
 */
function test_smtp_connection(): array
{
  $report = [];
  $ok     = true;

  $smtpHost       = defined('MAIL_SMTP_HOST')       ? (string) MAIL_SMTP_HOST       : '';
  $smtpPort       = defined('MAIL_SMTP_PORT')       ? (int)    MAIL_SMTP_PORT       : 587;
  $smtpUser       = defined('MAIL_SMTP_USER')       ? (string) MAIL_SMTP_USER       : '';
  $smtpPass       = defined('MAIL_SMTP_PASS')       ? (string) MAIL_SMTP_PASS       : '';
  $smtpEncryption = defined('MAIL_SMTP_ENCRYPTION') ? strtolower(trim((string) MAIL_SMTP_ENCRYPTION)) : 'tls';
  $fromAddress    = defined('MAIL_FROM_ADDRESS')    ? (string) MAIL_FROM_ADDRESS    : '';

  $report[] = '=== PHPMailer SMTP Diagnostic Report ===';
  $report[] = 'Timestamp  : ' . date('Y-m-d H:i:s T');
  $report[] = 'Host       : ' . ($smtpHost ?: '(not set)');
  $report[] = 'Port       : ' . $smtpPort;
  $report[] = 'Encryption : ' . ($smtpEncryption ?: 'none');
  $report[] = 'Username   : ' . ($smtpUser ?: '(not set)');
  $report[] = 'Password   : ' . (
    strlen($smtpPass) > 0
      ? str_repeat('*', min(strlen(trim($smtpPass)), 8))
        . ' (raw_len=' . strlen($smtpPass)
        . ' trimmed_len=' . strlen(trim($smtpPass)) . ')'
      : '(not set)'
  );
  $report[] = 'From Addr  : ' . ($fromAddress ?: '(not set)');
  $report[] = 'PHP OpenSSL: ' . (extension_loaded('openssl') ? 'loaded ✓' : 'NOT LOADED ✗');
  $report[] = '';

  if ($smtpHost === '' || $smtpUser === '' || $smtpPass === '') {
    $report[] = 'ERROR: SMTP credentials are incomplete.';
    $report[] = 'Fix  : Edit MAIL_SMTP_HOST / MAIL_SMTP_USER / MAIL_SMTP_PASS in admin/config/config.php.';
    return ['ok' => false, 'messages' => $report];
  }

  if (!extension_loaded('openssl') && $smtpEncryption !== 'none') {
    $report[] = 'ERROR: OpenSSL PHP extension is NOT loaded; cannot negotiate TLS/SSL with Gmail.';
    $report[] = 'Fix  : Enable "extension=openssl" in php.ini and restart Apache/Nginx.';
    return ['ok' => false, 'messages' => $report];
  }

  $debugLines = [];

  try {
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host      = $smtpHost;
    $mail->Port      = $smtpPort;
    $mail->SMTPAuth  = true;
    $mail->Username  = $smtpUser;
    $mail->Password  = $smtpPass;
    $mail->Timeout   = 15;
    $mail->SMTPDebug = \PHPMailer\PHPMailer\SMTP::DEBUG_SERVER;
    $mail->Debugoutput = static function (string $str, int $level) use (&$debugLines): void {
      $debugLines[] = '[' . $level . '] ' . rtrim($str);
    };

    if ($smtpEncryption === 'tls') {
      $mail->SMTPSecure  = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
      $mail->SMTPAutoTLS = true;
    } elseif ($smtpEncryption === 'ssl') {
      $mail->SMTPSecure  = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
      $mail->SMTPAutoTLS = false;
    } else {
      $mail->SMTPSecure  = '';
      $mail->SMTPAutoTLS = false;
    }

    $mail->SMTPOptions = [
      'ssl' => [
        'verify_peer'       => false,
        'verify_peer_name'  => false,
        'allow_self_signed' => true,
      ],
    ];

    // SmtpConnect() performs EHLO + STARTTLS + AUTH without sending any message.
    if ($mail->SmtpConnect()) {
      $mail->SmtpClose();
      $report[] = 'SMTP connection  : SUCCESS ✓';
      $report[] = 'Authentication   : SUCCESS ✓ (App Password accepted)';
    } else {
      $ok       = false;
      $report[] = 'SMTP connection  : FAILED ✗';
      $report[] = 'PHPMailer error  : ' . $mail->ErrorInfo;

      // Common Gmail error hints
      if (strpos($mail->ErrorInfo, '535') !== false) {
        $report[] = '';
        $report[] = 'HINT: Error 535 = Authentication failed.';
        $report[] = '  • You MUST use a Google App Password, NOT your normal Gmail password.';
        $report[] = '  • Enable 2-Step Verification on the account first.';
        $report[] = '  • Then generate an App Password at https://myaccount.google.com/apppasswords';
        $report[] = '  • The App Password is 16 characters; spaces are optional and ignored by Gmail.';
      } elseif (strpos($mail->ErrorInfo, 'Connection refused') !== false || strpos($mail->ErrorInfo, 'timed out') !== false) {
        $report[] = '';
        $report[] = 'HINT: Connection refused or timed out.';
        $report[] = '  • Your firewall or ISP may block outbound port 587.';
        $report[] = '  • Try with Port 465 + MAIL_SMTP_ENCRYPTION=ssl in config.php.';
        $report[] = '  • Some shared hosting providers block all outbound SMTP — check with your host.';
      } elseif (strpos($mail->ErrorInfo, 'certificate') !== false || strpos($mail->ErrorInfo, 'SSL') !== false) {
        $report[] = '';
        $report[] = 'HINT: SSL/TLS certificate issue.';
        $report[] = '  • This is usually caused by an outdated CA certificate bundle on localhost.';
        $report[] = '  • SMTPOptions with verify_peer=false should bypass this \u2014 check php.ini openssl.cafile.';
      }
    }
  } catch (\PHPMailer\PHPMailer\Exception $e) {
    $ok       = false;
    $report[] = 'PHPMailer exception: ' . $e->getMessage();
  } catch (\Throwable $e) {
    $ok       = false;
    $report[] = 'Unexpected exception: ' . $e->getMessage();
  }

  if (!empty($debugLines)) {
    $report[] = '';
    $report[] = '--- Raw SMTP conversation (server ↔ client) ---';
    foreach ($debugLines as $line) {
      $report[] = $line;
    }
  }

  return ['ok' => $ok, 'messages' => $report];
}
