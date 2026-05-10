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
    if (!is_array($roles)) $roles = [$roles];
    $role = $_SESSION['hosting_role'] ?? '';
    if (!in_array($role, $roles, true)) {
        hostingSetFlash('error', 'You are not authorized to access that page.');
        header('Location: ' . hostingGetBaseUrl() . '/index.php');
        exit;
    }
}

function hostingCurrentUser(): array {
    return [
        'id' => $_SESSION['hosting_user_id'] ?? '',
        'name' => $_SESSION['hosting_name'] ?? '',
        'email' => $_SESSION['hosting_email'] ?? '',
        'role' => $_SESSION['hosting_role'] ?? 'user'
    ];
}

