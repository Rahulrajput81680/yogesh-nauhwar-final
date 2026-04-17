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
define('BASE_URL', 'http://localhost:5002');
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

// Mail settings (localhost-safe defaults)
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: PROJECT_NAME);
define('MAIL_FROM_ADDRESS', getenv('MAIL_FROM_ADDRESS') ?: '');
define('MAIL_SMTP_HOST', getenv('MAIL_SMTP_HOST') ?: '');
define('MAIL_SMTP_PORT', 587);
define('MAIL_SMTP_USER', getenv('MAIL_SMTP_USER') ?: '');
define('MAIL_SMTP_PASS', getenv('MAIL_SMTP_PASS') ?: '');

date_default_timezone_set('UTC');

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
