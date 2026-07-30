<?php
require_once __DIR__ . '/json_helper.php';

function appLog(string $action, string $user = '', string $details = '', string $level = 'info'): void {
    $logs = readJson(DATA_PATH . 'logs.json');
    $logs[] = [
        'id' => generateId('log'),
        'action' => $action,
        'user' => $user,
        'details' => $details,
        'level' => $level,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'timestamp' => date('c')
    ];
    if (count($logs) > 5000) {
        $logs = array_slice($logs, -5000);
    }
    writeJson(DATA_PATH . 'logs.json', $logs);
}

function addNotification(string $userId, string $title, string $message, string $type = 'info'): void {
    $notifications = readJson(DATA_PATH . 'notifications.json');
    $notifications[] = [
        'id' => generateId('notif'),
        'user_id' => $userId,
        'title' => $title,
        'message' => $message,
        'type' => $type,
        'read' => false,
        'created_at' => date('c')
    ];
    writeJson(DATA_PATH . 'notifications.json', $notifications);
}

function getUnreadNotificationCount(string $userId): int {
    $notifications = readJson(DATA_PATH . 'notifications.json');
    return count(array_filter($notifications, fn($n) => $n['user_id'] === $userId && !$n['read']));
}

function checkExpiry(string $userId): bool {
    $users = readJson(DATA_PATH . 'users.json');
    $user = findById($users, $userId);
    if (!$user) return false;
    if (empty($user['expires_at'])) return true;
    return strtotime($user['expires_at']) > time();
}

function getUserPlan(string $planId): array {
    $plans = readJson(DATA_PATH . 'plans.json');
    return findById($plans, $planId) ?? [];
}

function sanitize(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function generateCSRF(): string {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function validateCSRF(string $token): bool {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function isAjax(): bool {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function humanFileSize(int $bytes): string {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < 3) { $bytes /= 1024; $i++; }
    return round($bytes, 2) . ' ' . $units[$i];
}

function getFolderSize(string $path): int {
    $size = 0;
    if (!is_dir($path)) return 0;
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $file) {
        $size += $file->getSize();
    }
    return $size;
}

function getFolderSizeMB(string $path): float {
    return round(getFolderSize($path) / 1048576, 2);
}

function getDashboardStats(string $role, string $userId = ''): array {
    $stats = [];
    if ($role === 'admin') {
        $users = readJson(DATA_PATH . 'users.json');
        $resellers = readJson(DATA_PATH . 'resellers.json');
        $sites = readJson(DATA_PATH . 'sites.json');
        $tickets = readJson(DATA_PATH . 'tickets.json');
        $scripts = readJson(DATA_PATH . 'scripts.json');
        $stats = [
            'total_users' => count($users),
            'active_users' => count(array_filter($users, fn($u) => ($u['status'] ?? '') === 'active')),
            'total_resellers' => count($resellers),
            'total_sites' => count($sites),
            'open_tickets' => count(array_filter($tickets, fn($t) => ($t['status'] ?? '') === 'open')),
            'total_scripts' => count($scripts),
        ];
    } elseif ($role === 'reseller') {
        $users = readJson(DATA_PATH . 'users.json');
        $myUsers = array_filter($users, fn($u) => ($u['reseller_id'] ?? '') === $userId);
        $tickets = readJson(DATA_PATH . 'tickets.json');
        $myTickets = array_filter($tickets, fn($t) => ($t['reseller_id'] ?? '') === $userId);
        $resellers = readJson(DATA_PATH . 'resellers.json');
        $me = findById($resellers, $userId);
        $stats = [
            'my_users' => count($myUsers),
            'active_users' => count(array_filter($myUsers, fn($u) => ($u['status'] ?? '') === 'active')),
            'open_tickets' => count(array_filter($myTickets, fn($t) => ($t['status'] ?? '') === 'open')),
            'credits' => (int)($me['credits'] ?? 0),
        ];
    } elseif ($role === 'user') {
        $sites = readJson(DATA_PATH . 'sites.json');
        $mySites = array_filter($sites, fn($s) => $s['user_id'] === $userId);
        $links = readJson(DATA_PATH . 'links.json');
        $myLinks = array_filter($links, fn($l) => $l['user_id'] === $userId);
        $users = readJson(DATA_PATH . 'users.json');
        $user = findById($users, $userId);
        $plan = getUserPlan($user['plan'] ?? 'basic');
        $stats = [
            'sites_used' => count($mySites),
            'sites_max' => (int)($plan['max_sites'] ?? 1),
            'links_used' => count($myLinks),
            'links_max' => (int)($plan['short_links'] ?? 10),
            'plan_name' => $plan['name'] ?? 'Basic',
            'expires_at' => $user['expires_at'] ?? '',
        ];
    }
    return $stats;
}

function timeAgo(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return $diff . 's ago';
    if ($diff < 3600) return floor($diff/60) . 'm ago';
    if ($diff < 86400) return floor($diff/3600) . 'h ago';
    return floor($diff/86400) . 'd ago';
}

function getStatusBadge(string $status): string {
    $map = [
        'active' => 'badge-active',
        'inactive' => 'badge-inactive',
        'suspended' => 'badge-inactive',
        'pending' => 'badge-pending',
        'open' => 'badge-open',
        'closed' => 'badge-closed',
        'answered' => 'badge-active',
    ];
    $cls = $map[$status] ?? 'badge-pending';
    return '<span class="badge-status ' . $cls . '">' . ucfirst($status) . '</span>';
}
