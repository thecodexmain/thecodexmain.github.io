<?php
define('ROOT_PATH', dirname(__DIR__));
define('DATA_PATH', ROOT_PATH . '/data/');
define('UPLOADS_PATH', ROOT_PATH . '/uploads/');
define('USERS_PATH', ROOT_PATH . '/users/');
define('SCRIPTS_PATH', ROOT_PATH . '/uploads/scripts/');

// Detect base URL dynamically
$_scriptDir = dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/panel/x'));
$_basePath = rtrim($_scriptDir, '/') . '/panel/';
define('BASE_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $_basePath);

define('SESSION_NAME', 'PRIMEWEBS_SESS');
define('MAX_UPLOAD_SIZE', 50 * 1024 * 1024);
define('CSRF_TOKEN_NAME', '_csrf_token');

ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_name(SESSION_NAME);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$settings = [];
$_settingsFile = DATA_PATH . 'settings.json';
if (file_exists($_settingsFile)) {
    $settings = json_decode(file_get_contents($_settingsFile), true) ?? [];
}

// Maintenance mode check
if (!empty($settings['maintenance_mode']) && $settings['maintenance_mode'] === true) {
    $isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    $currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $isLoginPage = $currentScript === 'login.php';
    $isMaintenancePage = $currentScript === 'maintenance.php';
    if (!$isAdmin && !$isLoginPage && !$isMaintenancePage) {
        http_response_code(503);
        $mMsg = htmlspecialchars($settings['maintenance_message'] ?? 'Under maintenance.');
        $siteName = htmlspecialchars($settings['site_name'] ?? 'Prime Webs');
        echo "<!DOCTYPE html><html><head><title>{$siteName} - Maintenance</title>
        <style>body{font-family:sans-serif;background:#1e1b4b;color:#fff;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;text-align:center;}
        .box{max-width:500px;padding:40px;}.icon{font-size:64px;margin-bottom:20px;}h1{font-size:32px;margin-bottom:16px;}p{color:#c7d2fe;font-size:16px;}</style>
        </head><body><div class='box'><div class='icon'>🔧</div><h1>{$siteName}</h1><p>{$mMsg}</p></div></body></html>";
        exit;
    }
}

date_default_timezone_set($settings['default_timezone'] ?? 'UTC');

function getSetting(string $key, $default = '') {
    global $settings;
    return $settings[$key] ?? $default;
}
