<?php
/**
 * Amrit Web Panel - Core Configuration
 */

define('BASE_PATH', dirname(__DIR__));
define('DATA_PATH', BASE_PATH . '/data');
define('USERS_PATH', BASE_PATH . '/users');
define('SCRIPTS_PATH', BASE_PATH . '/scripts');
define('UPLOADS_PATH', BASE_PATH . '/uploads');
define('ASSETS_PATH', BASE_PATH . '/assets');

// Base URL - auto-detect
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = dirname(dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$baseUrl = $protocol . '://' . $host . rtrim($scriptDir, '/');
define('BASE_URL', $baseUrl);
define('PANEL_URL', $protocol . '://' . $host . '/panel');

// Session configuration
define('SESSION_NAME', 'awp_session');
define('SESSION_LIFETIME', 3600);

// Security
define('CSRF_TOKEN_NAME', 'awp_csrf_token');
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 30 * 60);

// Upload limits
define('MAX_UPLOAD_SIZE', 50 * 1024 * 1024); // 50MB
define('ALLOWED_UPLOAD_TYPES', ['application/zip', 'application/x-zip-compressed']);

// Version
define('PANEL_VERSION', '1.0.0');
define('PANEL_NAME', 'Amrit Web Panel');

// Initialize session
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path' => '/',
        'secure' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Error handling (production: suppress display, log errors)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', BASE_PATH . '/data/php_errors.log');

// Load settings
function getSettings(): array {
    static $settings = null;
    if ($settings === null) {
        $settings = readJson('settings');
        if (empty($settings)) {
            $settings = ['site_name' => PANEL_NAME, 'theme_color' => '#4f46e5'];
        }
    }
    return $settings;
}
