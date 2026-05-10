<?php
require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function primeIsLoggedIn() {
    return !empty($_SESSION['primekeys_user_id']);
}

function primeGetCurrentUser() {
    if (!primeIsLoggedIn()) {
        return [
            'id' => '',
            'name' => '',
            'username' => '',
            'role' => '',
            'email' => '',
            'balance' => 0,
        ];
    }
    $user = primeFindUserById($_SESSION['primekeys_user_id']);
    return $user ?: [
        'id' => $_SESSION['primekeys_user_id'] ?? '',
        'name' => $_SESSION['primekeys_name'] ?? '',
        'username' => $_SESSION['primekeys_username'] ?? '',
        'role' => $_SESSION['primekeys_role'] ?? '',
        'email' => $_SESSION['primekeys_email'] ?? '',
        'balance' => $_SESSION['primekeys_balance'] ?? 0,
    ];
}

function primeLoginUser($user) {
    session_regenerate_id(true);
    $_SESSION['primekeys_user_id'] = $user['id'];
    $_SESSION['primekeys_name'] = $user['name'];
    $_SESSION['primekeys_username'] = $user['username'];
    $_SESSION['primekeys_role'] = $user['role'];
    $_SESSION['primekeys_email'] = $user['email'];
    $_SESSION['primekeys_balance'] = $user['balance'] ?? 0;

    $users = primeLoadData('users');
    foreach ($users as &$record) {
        if (($record['id'] ?? '') === ($user['id'] ?? '')) {
            $record['last_login_at'] = primeNow();
            break;
        }
    }
    unset($record);
    primeSaveData('users', $users);
    primeAddAuditLog($user, 'login', 'Signed into the PRIME KEYS portal.');
}

function primeLogoutUser() {
    unset(
        $_SESSION['primekeys_user_id'],
        $_SESSION['primekeys_name'],
        $_SESSION['primekeys_username'],
        $_SESSION['primekeys_role'],
        $_SESSION['primekeys_email'],
        $_SESSION['primekeys_balance']
    );
}

function primeRequireLogin() {
    if (!primeIsLoggedIn()) {
        header('Location: ' . primeGetBaseUrl() . '/index.php');
        exit;
    }
}

function primeRequireRole($roles) {
    primeRequireLogin();
    $roles = is_array($roles) ? $roles : [$roles];
    $currentUser = primeGetCurrentUser();
    if (!in_array($currentUser['role'] ?? '', $roles, true)) {
        primeSetFlash('error', 'You are not allowed to access that page.');
        header('Location: ' . primeGetBaseUrl() . '/dashboard.php');
        exit;
    }
}
?>
