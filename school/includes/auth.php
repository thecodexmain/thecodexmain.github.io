<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . getBaseUrl() . '/index.php');
        exit;
    }
}

function requireRole($roles) {
    requireLogin();
    if (!is_array($roles)) $roles = [$roles];
    if (!in_array($_SESSION['role'], $roles)) {
        header('Location: ' . getBaseUrl() . '/index.php?error=unauthorized');
        exit;
    }
}

function getCurrentUser() {
    return [
        'id'    => $_SESSION['user_id'] ?? '',
        'name'  => $_SESSION['name']    ?? '',
        'role'  => $_SESSION['role']    ?? '',
        'email' => $_SESSION['email']   ?? ''
    ];
}

function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    // Walk up from current script dir to find 'school'
    $parts = explode('/', trim($scriptDir, '/'));
    $schoolIdx = false;
    foreach (array_reverse($parts, true) as $i => $part) {
        if ($part === 'school') { $schoolIdx = $i; break; }
    }
    if ($schoolIdx !== false) {
        $baseParts = array_slice($parts, 0, $schoolIdx + 1);
        return $protocol . '://' . $host . '/' . implode('/', $baseParts);
    }
    return $protocol . '://' . $host . '/' . implode('/', $parts);
}
