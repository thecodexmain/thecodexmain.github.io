<?php
require_once __DIR__ . '/json_helper.php';
require_once __DIR__ . '/functions.php';

function validateApiKey(string $key): ?array {
    if (empty($key)) return null;
    $keys = readJson(DATA_PATH . 'api_keys.json');
    foreach ($keys as $k) {
        if (($k['key'] ?? '') === $key && ($k['status'] ?? '') === 'active') {
            $now = time();
            $windowStart = $now - 60;
            $recent = array_filter($k['requests'] ?? [], fn($r) => $r > $windowStart);
            if (count($recent) >= ($k['rate_limit'] ?? 60)) {
                return null;
            }
            // Update usage stats
            $keys2 = readJson(DATA_PATH . 'api_keys.json');
            foreach ($keys2 as &$key2) {
                if ($key2['key'] === $key) {
                    $key2['requests'] = array_values(array_filter($key2['requests'] ?? [], fn($r) => $r > $windowStart));
                    $key2['requests'][] = $now;
                    $key2['last_used'] = date('c');
                    break;
                }
            }
            unset($key2);
            writeJson(DATA_PATH . 'api_keys.json', $keys2);
            return $k;
        }
    }
    return null;
}

function generateApiKey(string $userId, string $name, int $rateLimit = 60): array {
    if (empty($name)) return ['success' => false, 'error' => 'Name required'];
    $keys = readJson(DATA_PATH . 'api_keys.json');
    $key = 'pw_' . bin2hex(random_bytes(20));
    $keys[] = [
        'id' => generateId('apk'),
        'user_id' => $userId,
        'name' => sanitize($name),
        'key' => $key,
        'rate_limit' => $rateLimit,
        'requests' => [],
        'last_used' => null,
        'status' => 'active',
        'created_at' => date('c'),
    ];
    writeJson(DATA_PATH . 'api_keys.json', $keys);
    return ['success' => true, 'key' => $key];
}

function revokeApiKey(string $keyId, string $userId, string $role = 'user'): array {
    $keys = readJson(DATA_PATH . 'api_keys.json');
    $key = findById($keys, $keyId);
    if (!$key) return ['success' => false, 'error' => 'Key not found'];
    if ($role !== 'admin' && $key['user_id'] !== $userId) {
        return ['success' => false, 'error' => 'Access denied'];
    }
    deleteById($keys, $keyId);
    writeJson(DATA_PATH . 'api_keys.json', $keys);
    return ['success' => true];
}
