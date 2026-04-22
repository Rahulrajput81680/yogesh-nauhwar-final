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
 * @return bool
 */
function send_email(string $to, string $subject, string $htmlBody, string $plainBody = ''): bool
{
  if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
    error_log('send_email aborted: invalid recipient email [' . $to . ']');
    return false;
  }

  $readSetting = static function (string $key, ?string $default = null): ?string {
    if (function_exists('get_admin_setting')) {
      try {
        return get_admin_setting($key, $default);
      } catch (Throwable $e) {
        return $default;
      }
    }

    return $default;
  };

  if ($plainBody === '') {
    $plainBody = trim(strip_tags(preg_replace('/<br\s*\/?>/i', "\n", $htmlBody)));
  }

  $smtpHost = trim((string) $readSetting('smtp_host', defined('MAIL_SMTP_HOST') ? MAIL_SMTP_HOST : ''));
  $smtpPort = (int) $readSetting('smtp_port', (string) (defined('MAIL_SMTP_PORT') ? (int) MAIL_SMTP_PORT : 2525));
  $smtpUser = trim((string) $readSetting('smtp_user', defined('MAIL_SMTP_USER') ? MAIL_SMTP_USER : ''));
  $smtpPass = (string) $readSetting('smtp_pass', defined('MAIL_SMTP_PASS') ? MAIL_SMTP_PASS : '');
  $smtpEncryption = strtolower(trim((string) $readSetting('smtp_encryption', defined('MAIL_SMTP_ENCRYPTION') ? MAIL_SMTP_ENCRYPTION : 'none')));

  $defaultFromName = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : (defined('PROJECT_NAME') ? PROJECT_NAME : 'Website');
  $fromName = trim((string) $readSetting('smtp_from_name', $defaultFromName));
  if ($fromName === '') {
    $fromName = $defaultFromName;
  }

  $defaultFromAddress = defined('MAIL_FROM_ADDRESS') ? MAIL_FROM_ADDRESS : '';
  $fromAddress = trim((string) $readSetting('smtp_from_email', $defaultFromAddress));
  if (!filter_var($fromAddress, FILTER_VALIDATE_EMAIL)) {
    $fromAddress = 'no-reply@example.com';
  }

  if ($smtpHost === '' || $smtpUser === '' || $smtpPass === '') {
    error_log('send_email aborted: SMTP configuration is incomplete.');
    return false;
  }

  if (!in_array($smtpEncryption, ['tls', 'ssl', 'none'], true)) {
    $smtpEncryption = 'none';
  }

  $hasOpenSsl = extension_loaded('openssl');
  if (!$hasOpenSsl) {
    $smtpEncryption = 'none';
    if (stripos($smtpHost, 'sandbox.smtp.mailtrap.io') !== false && in_array($smtpPort, [465, 587], true)) {
      $smtpPort = 2525;
    }
  }

  if ($smtpPort <= 0) {
    $smtpPort = 2525;
  }

  try {
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $smtpHost;
    $mail->Port = $smtpPort;
    $mail->SMTPAuth = true;
    $mail->Username = $smtpUser;
    $mail->Password = $smtpPass;
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';
    $mail->Timeout = 30;
    $mail->SMTPDebug = 0;
    $mail->isHTML(true);

    if ($smtpEncryption === 'tls') {
      $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
      $mail->SMTPAutoTLS = true;
    } elseif ($smtpEncryption === 'ssl') {
      $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
      $mail->SMTPAutoTLS = false;
    } else {
      $mail->SMTPSecure = '';
      $mail->SMTPAutoTLS = false;
    }

    if ($hasOpenSsl) {
      $mail->SMTPOptions = [
        'ssl' => [
          'verify_peer' => false,
          'verify_peer_name' => false,
          'allow_self_signed' => true,
        ],
      ];
    }

    $mail->setFrom($fromAddress, $fromName, false);
    $mail->addAddress($to);
    $mail->addReplyTo($fromAddress, $fromName);
    $mail->Subject = $subject;
    $mail->Body = $htmlBody;
    $mail->AltBody = $plainBody;

    $sent = $mail->send();
    if (!$sent) {
      error_log('PHPMailer send failed: ' . $mail->ErrorInfo);
    }

    return $sent;
  } catch (\PHPMailer\PHPMailer\Exception $e) {
    error_log('PHPMailer exception: ' . $e->getMessage());
    return false;
  } catch (Throwable $e) {
    error_log('Mail send exception: ' . $e->getMessage());
    return false;
  }
}
