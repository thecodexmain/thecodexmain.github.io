<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/json_helper.php';
require_once __DIR__ . '/functions.php';

function login(string $username, string $password): array {
    $settings = readJson(DATA_PATH . 'settings.json');
    $maxAttempts = (int)($settings['max_login_attempts'] ?? 5);
    $lockDuration = (int)($settings['lock_duration'] ?? 30);

    $users = readJson(DATA_PATH . 'users.json');
    $resellers = readJson(DATA_PATH . 'resellers.json');

    $user = null;
    $source = null;
    foreach ($users as $u) {
        if (($u['username'] ?? '') === $username || ($u['email'] ?? '') === $username) {
            $user = $u; $source = 'users'; break;
        }
    }
    if (!$user) {
        foreach ($resellers as $u) {
            if (($u['username'] ?? '') === $username || ($u['email'] ?? '') === $username) {
                $user = $u; $source = 'resellers'; break;
            }
        }
    }

    if (!$user) {
        return ['success' => false, 'error' => 'Invalid credentials'];
    }

    if (!empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
        $remaining = ceil((strtotime($user['locked_until']) - time()) / 60);
        return ['success' => false, 'error' => "Account locked. Try again in {$remaining} minute(s)."];
    }

    if (($user['status'] ?? 'active') !== 'active') {
        return ['success' => false, 'error' => 'Account suspended. Contact support.'];
    }

    if (!password_verify($password, $user['password'])) {
        $attempts = (int)($user['failed_attempts'] ?? 0) + 1;
        $updates = ['failed_attempts' => $attempts];
        if ($attempts >= $maxAttempts) {
            $updates['locked_until'] = date('c', time() + $lockDuration * 60);
        }
        $file = DATA_PATH . $source . '.json';
        $data = readJson($file);
        updateById($data, $user['id'], $updates);
        writeJson($file, $data);
        $remaining = $maxAttempts - $attempts;
        return ['success' => false, 'error' => 'Invalid credentials.' . ($remaining > 0 ? " {$remaining} attempt(s) remaining." : ' Account locked.')];
    }

    $file = DATA_PATH . $source . '.json';
    $data = readJson($file);
    updateById($data, $user['id'], [
        'failed_attempts' => 0,
        'locked_until' => null,
        'last_login' => date('c'),
        'login_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    writeJson($file, $data);

    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['logged_in'] = true;

    appLog('login', $user['username'], 'Logged in from ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

    return ['success' => true, 'role' => $user['role']];
}

function logout(): void {
    if (isset($_SESSION['username'])) {
        appLog('logout', $_SESSION['username'], 'User logged out');
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

function isLoggedIn(): bool {
    return !empty($_SESSION['logged_in']) && !empty($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        redirect(BASE_URL . 'login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? ''));
    }
}

function requireRole(string ...$roles): void {
    requireLogin();
    if (!in_array($_SESSION['user_role'] ?? '', $roles)) {
        redirect(BASE_URL . 'login.php?error=unauthorized');
    }
}

function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    $role = $_SESSION['user_role'] ?? 'user';
    if ($role === 'reseller') {
        $users = readJson(DATA_PATH . 'resellers.json');
    } else {
        $users = readJson(DATA_PATH . 'users.json');
    }
    return findById($users, $_SESSION['user_id']);
}

function hasPermission(string $permission): bool {
    $role = $_SESSION['user_role'] ?? 'user';
    $permissions = [
        'admin' => ['*'],
        'reseller' => ['manage_users', 'view_tickets', 'manage_wallet', 'deploy_scripts'],
        'user' => ['manage_sites', 'file_manager', 'tickets', 'short_links', 'backup'],
    ];
    $userPerms = $permissions[$role] ?? [];
    return in_array('*', $userPerms) || in_array($permission, $userPerms);
}
