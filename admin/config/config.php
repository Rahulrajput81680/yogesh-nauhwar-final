<?php

// Prevent direct access
if (!defined('ADMIN_INIT')) {
  die('Direct access not permitted');
}

define('DB_HOST', 'localhost');
define('DB_NAME', 'shared-admin-panel');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_CHARSET', 'utf8mb4');

// Update with your project URL (no trailing slash)
define('BASE_URL', '');
define('ADMIN_URL', BASE_URL . '/admin');

// Project name (displayed in admin panel header and titles)
define('PROJECT_NAME', 'Yogesh Nauhwar');

// Admin session name (change for each project for security)
define('SESSION_NAME', 'admin_session_' . md5(BASE_URL));

// Session timeout in seconds
define('SESSION_TIMEOUT', 3600);

// Upload directory (absolute path)
define('UPLOAD_DIR', dirname(dirname(__DIR__)) . '/uploads');

// Upload URL (for displaying images)
define('UPLOAD_URL', BASE_URL . '/uploads');

define('MAX_UPLOAD_SIZE', 1 * 1024 * 1024);

define('ALLOWED_IMAGE_TYPES', ['webp']);

define('THUMB_WIDTH', 300);
define('THUMB_HEIGHT', 300);

// Number of items to display per page in listings
define('ITEMS_PER_PAGE', 10);

// CSRF token expiration time in seconds
define('CSRF_TOKEN_EXPIRE', 3600);

// Minimum password length for admin users
define('MIN_PASSWORD_LENGTH', 8);

// ---------------------------------------------------------------------------
// SMTP Configuration — currently using Mailtrap sandbox for testing
// ---------------------------------------------------------------------------
// Mailtrap credentials (sandbox.smtp.mailtrap.io)
//   Port: 587 (STARTTLS) — use MAIL_SMTP_ENCRYPTION = 'tls'
//   Port: 465 (SMTPS)   — use MAIL_SMTP_ENCRYPTION = 'ssl'
//   Port: 2525 (plain)  — use MAIL_SMTP_ENCRYPTION = 'none'
//
// ⚠️  'ssl' (SMTPS) only works on port 465, NOT 587.
//     We are using port 587, so encryption MUST be 'tls' (STARTTLS).
//
// To switch to real Gmail later:
//   Host : smtp.gmail.com | Port: 587 | Encryption: tls
//   User : your-gmail@gmail.com
//   Pass : 16-char Google App Password (requires 2FA enabled)
//   See  : https://myaccount.google.com/apppasswords
// ---------------------------------------------------------------------------
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: PROJECT_NAME);
define('MAIL_FROM_ADDRESS', getenv('MAIL_FROM_ADDRESS') ?: 'vidhayakyogeshnauhwar@gmail.com');
define('MAIL_CONTACT_RECIPIENT', getenv('MAIL_CONTACT_RECIPIENT') ?: MAIL_FROM_ADDRESS);
define('MAIL_SMTP_HOST', getenv('MAIL_SMTP_HOST') ?: 'sandbox.smtp.mailtrap.io');
// Port 587 + 'tls' (STARTTLS) — same combination Gmail uses. Requires OpenSSL (now enabled).
define('MAIL_SMTP_PORT', (int) (getenv('MAIL_SMTP_PORT') ?: 2525));
define('MAIL_SMTP_USER', getenv('MAIL_SMTP_USER') ?: '120b154f30d18f');
define('MAIL_SMTP_PASS', getenv('MAIL_SMTP_PASS') ?: '793e5ec90f9768');
// 'none' = no TLS — required for port 2525 (Mailtrap plain-text port, no OpenSSL needed).
// When switching to Gmail: change to 'tls', port 587, and real Gmail credentials.
define('MAIL_SMTP_ENCRYPTION', getenv('MAIL_SMTP_ENCRYPTION') ?: 'none');
// Set to false once emails are confirmed working in Mailtrap.
define('MAIL_SMTP_DEBUG', (bool) (getenv('MAIL_SMTP_DEBUG') ?: false));


date_default_timezone_set('Asia/Kolkata');

// Development: Show all errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
$pdoOptions = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES => false,
];

try {
  $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdoOptions);
} catch (PDOException $firstError) {
  // Local fallback for common MySQL setup where root has an empty password.
  if (DB_USER === 'root' && DB_PASS !== '') {
    try {
      $pdo = new PDO($dsn, DB_USER, '', $pdoOptions);
    } catch (PDOException $secondError) {
      die('Database connection failed: ' . $secondError->getMessage());
    }
  } else {
    die('Database connection failed: ' . $firstError->getMessage());
  }
}
