<?php
require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function hostingIsLoggedIn(): bool {
    return !empty($_SESSION['hosting_user_id']);
}

function hostingRequireLogin(): void {
    if (!hostingIsLoggedIn()) {
        header('Location: ' . hostingGetBaseUrl() . '/login.php');
        exit;
    }
}

function hostingRequireRole($roles): void {
    hostingRequireLogin();
    $roles = is_array($roles) ? $roles : [$roles];
    if (!in_array($_SESSION['hosting_role'] ?? '', $roles, true)) {
        hostingSetFlash('danger', 'You are not authorized to access this area.');
        header('Location: ' . hostingGetBaseUrl() . '/dashboard.php');
        exit;
    }
}
