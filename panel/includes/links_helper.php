<?php
require_once __DIR__ . '/json_helper.php';
require_once __DIR__ . '/functions.php';

function createShortLink(string $userId, string $targetUrl, array $options = []): array {
    if (empty($targetUrl) || !filter_var($targetUrl, FILTER_VALIDATE_URL)) {
        return ['success' => false, 'error' => 'Invalid URL'];
    }

    $links = readJson(DATA_PATH . 'links.json');

    // Check user link quota
    $users = readJson(DATA_PATH . 'users.json');
    $user = findById($users, $userId);
    if ($user) {
        $plan = getUserPlan($user['plan'] ?? 'basic');
        $maxLinks = (int)($plan['short_links'] ?? 10);
        $userLinks = array_filter($links, fn($l) => $l['user_id'] === $userId);
        if (count($userLinks) >= $maxLinks) {
            return ['success' => false, 'error' => 'Short link limit reached for your plan (' . $maxLinks . ' max)'];
        }
    }

    $code = !empty($options['code']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $options['code']) : substr(bin2hex(random_bytes(3)), 0, 6);
    if (empty($code)) $code = substr(bin2hex(random_bytes(3)), 0, 6);

    // Ensure unique code
    $existing = array_column($links, 'code');
    while (in_array($code, $existing)) {
        $code = substr(bin2hex(random_bytes(3)), 0, 6);
    }

    $link = [
        'id' => generateId('lnk'),
        'user_id' => $userId,
        'code' => $code,
        'target' => $targetUrl,
        'title' => sanitize($options['title'] ?? ''),
        'password' => !empty($options['password']) ? password_hash($options['password'], PASSWORD_BCRYPT) : null,
        'expires_at' => !empty($options['expires_at']) ? $options['expires_at'] : null,
        'clicks' => 0,
        'analytics' => [],
        'created_at' => date('c'),
        'status' => 'active',
    ];

    $links[] = $link;
    writeJson(DATA_PATH . 'links.json', $links);
    appLog('create_link', $userId, "Created short link: {$code} -> {$targetUrl}");
    return ['success' => true, 'code' => $code];
}

function trackClick(string $code): ?array {
    $links = readJson(DATA_PATH . 'links.json');
    foreach ($links as &$link) {
        if ($link['code'] === $code && ($link['status'] ?? 'active') === 'active') {
            if (!empty($link['expires_at']) && strtotime($link['expires_at']) < time()) {
                return null;
            }
            $link['clicks'] = ($link['clicks'] ?? 0) + 1;
            $link['analytics'][] = [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                'ua' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200),
                'referer' => $_SERVER['HTTP_REFERER'] ?? '',
                'time' => date('c'),
                'device' => detectDevice($_SERVER['HTTP_USER_AGENT'] ?? ''),
                'country' => '',
            ];
            if (count($link['analytics']) > 1000) {
                $link['analytics'] = array_slice($link['analytics'], -1000);
            }
            writeJson(DATA_PATH . 'links.json', $links);
            return ['target' => $link['target'], 'password' => $link['password'] ?? null];
        }
    }
    return null;
}

function detectDevice(string $ua): string {
    $ua = strtolower($ua);
    if (strpos($ua, 'mobile') !== false || strpos($ua, 'android') !== false || strpos($ua, 'iphone') !== false) return 'mobile';
    if (strpos($ua, 'tablet') !== false || strpos($ua, 'ipad') !== false) return 'tablet';
    return 'desktop';
}

function deleteShortLink(string $linkId, string $userId, string $role = 'user'): array {
    $links = readJson(DATA_PATH . 'links.json');
    $link = findById($links, $linkId);
    if (!$link) return ['success' => false, 'error' => 'Link not found'];
    if ($role !== 'admin' && $link['user_id'] !== $userId) {
        return ['success' => false, 'error' => 'Access denied'];
    }
    deleteById($links, $linkId);
    writeJson(DATA_PATH . 'links.json', $links);
    return ['success' => true];
}
