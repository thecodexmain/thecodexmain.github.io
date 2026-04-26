<?php
/**
 * JSON Database Layer - Thread-safe file operations
 */

/**
 * Read a JSON data file and return decoded array
 */
function readJson(string $file): array {
    $path = DATA_PATH . '/' . $file . '.json';
    if (!file_exists($path)) {
        return [];
    }
    $fp = fopen($path, 'r');
    if (!$fp) return [];
    $lock = flock($fp, LOCK_SH);
    $content = stream_get_contents($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    if (empty($content)) return [];
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

/**
 * Write data to a JSON file (atomic safe write with locking)
 */
function writeJson(string $file, array $data): bool {
    $path = DATA_PATH . '/' . $file . '.json';
    $tmpPath = $path . '.tmp.' . getmypid();
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) return false;
    // Write to temp file first
    $fp = fopen($tmpPath, 'w');
    if (!$fp) return false;
    flock($fp, LOCK_EX);
    fwrite($fp, $json);
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    // Atomic rename
    return rename($tmpPath, $path);
}

/**
 * Safe file write with locking (for non-JSON files)
 */
function safeFileWrite(string $path, string $content): bool {
    $tmpPath = $path . '.tmp.' . getmypid();
    $fp = fopen($tmpPath, 'w');
    if (!$fp) return false;
    flock($fp, LOCK_EX);
    fwrite($fp, $content);
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return rename($tmpPath, $path);
}

/**
 * Get all users
 */
function getUsers(): array {
    $data = readJson('users');
    return $data['users'] ?? [];
}

/**
 * Save all users
 */
function saveUsers(array $users): bool {
    return writeJson('users', ['users' => $users]);
}

/**
 * Find user by ID
 */
function getUserById(string $id): ?array {
    foreach (getUsers() as $user) {
        if ($user['id'] === $id) return $user;
    }
    return null;
}

/**
 * Find user by username
 */
function getUserByUsername(string $username): ?array {
    foreach (getUsers() as $user) {
        if (strtolower($user['username']) === strtolower($username)) return $user;
    }
    return null;
}

/**
 * Find user by email
 */
function getUserByEmail(string $email): ?array {
    foreach (getUsers() as $user) {
        if (strtolower($user['email']) === strtolower($email)) return $user;
    }
    return null;
}

/**
 * Update a user by ID
 */
function updateUser(string $id, array $data): bool {
    $users = getUsers();
    foreach ($users as &$user) {
        if ($user['id'] === $id) {
            $user = array_merge($user, $data);
            return saveUsers($users);
        }
    }
    return false;
}

/**
 * Create a new user
 */
function createUser(array $data): array {
    $users = getUsers();
    $users[] = $data;
    saveUsers($users);
    return $data;
}

/**
 * Delete user by ID
 */
function deleteUser(string $id): bool {
    $users = getUsers();
    $users = array_values(array_filter($users, fn($u) => $u['id'] !== $id));
    return saveUsers($users);
}

/**
 * Get all resellers
 */
function getResellers(): array {
    $data = readJson('resellers');
    return $data['resellers'] ?? [];
}

/**
 * Save all resellers
 */
function saveResellers(array $resellers): bool {
    return writeJson('resellers', ['resellers' => $resellers]);
}

/**
 * Get reseller by ID
 */
function getResellerById(string $id): ?array {
    foreach (getResellers() as $r) {
        if ($r['id'] === $id) return $r;
    }
    return null;
}

/**
 * Get all plans
 */
function getPlans(): array {
    $data = readJson('plans');
    return $data['plans'] ?? [];
}

/**
 * Save all plans
 */
function savePlans(array $plans): bool {
    return writeJson('plans', ['plans' => $plans]);
}

/**
 * Get plan by ID
 */
function getPlanById(string $id): ?array {
    foreach (getPlans() as $plan) {
        if ($plan['id'] === $id) return $plan;
    }
    return null;
}

/**
 * Get all scripts
 */
function getScripts(): array {
    $data = readJson('scripts');
    return $data['scripts'] ?? [];
}

/**
 * Save all scripts
 */
function saveScripts(array $scripts): bool {
    return writeJson('scripts', ['scripts' => $scripts]);
}

/**
 * Get script by ID
 */
function getScriptById(string $id): ?array {
    foreach (getScripts() as $script) {
        if ($script['id'] === $id) return $script;
    }
    return null;
}

/**
 * Get all sites
 */
function getSites(): array {
    $data = readJson('sites');
    return $data['sites'] ?? [];
}

/**
 * Save all sites
 */
function saveSites(array $sites): bool {
    return writeJson('sites', ['sites' => $sites]);
}

/**
 * Get site by ID
 */
function getSiteById(string $id): ?array {
    foreach (getSites() as $site) {
        if ($site['id'] === $id) return $site;
    }
    return null;
}

/**
 * Get sites by user ID
 */
function getSitesByUser(string $userId): array {
    return array_values(array_filter(getSites(), fn($s) => $s['user_id'] === $userId));
}

/**
 * Get all notifications (or for a specific user)
 */
function getNotifications(?string $userId = null): array {
    $data = readJson('notifications');
    $notifs = $data['notifications'] ?? [];
    if ($userId !== null) {
        return array_values(array_filter($notifs, fn($n) => $n['user_id'] === $userId || $n['user_id'] === 'all'));
    }
    return $notifs;
}

/**
 * Add a notification
 */
function addNotification(array $notif): bool {
    $data = readJson('notifications');
    $notifs = $data['notifications'] ?? [];
    $notifs[] = $notif;
    return writeJson('notifications', ['notifications' => $notifs]);
}

/**
 * Get all tickets
 */
function getTickets(): array {
    $data = readJson('tickets');
    return $data['tickets'] ?? [];
}

/**
 * Save all tickets
 */
function saveTickets(array $tickets): bool {
    return writeJson('tickets', ['tickets' => $tickets]);
}

/**
 * Get tickets by user ID
 */
function getTicketsByUser(string $userId): array {
    return array_values(array_filter(getTickets(), fn($t) => $t['user_id'] === $userId));
}

/**
 * Get all short links
 */
function getLinks(): array {
    $data = readJson('links');
    return $data['links'] ?? [];
}

/**
 * Save all links
 */
function saveLinks(array $links): bool {
    return writeJson('links', ['links' => $links]);
}

/**
 * Get links by user ID
 */
function getLinksByUser(string $userId): array {
    return array_values(array_filter(getLinks(), fn($l) => $l['user_id'] === $userId));
}

/**
 * Add a log entry
 */
function addLog(string $type, string $action, string $userId, array $extra = []): bool {
    $data = readJson('logs');
    $logs = $data['logs'] ?? [];
    $logs[] = [
        'id' => 'log_' . uniqid(),
        'type' => $type,
        'action' => $action,
        'user_id' => $userId,
        'ip' => getClientIp(),
        'extra' => $extra,
        'created_at' => date('c')
    ];
    // Keep last 10000 logs
    if (count($logs) > 10000) {
        $logs = array_slice($logs, -10000);
    }
    return writeJson('logs', ['logs' => $logs]);
}

/**
 * Get all logs
 */
function getLogs(int $limit = 100): array {
    $data = readJson('logs');
    $logs = $data['logs'] ?? [];
    return array_reverse(array_slice($logs, -$limit));
}

/**
 * Get all API keys
 */
function getApiKeys(): array {
    $data = readJson('api_keys');
    return $data['keys'] ?? [];
}

/**
 * Save all API keys
 */
function saveApiKeys(array $keys): bool {
    return writeJson('api_keys', ['keys' => $keys]);
}

/**
 * Get API key by key string
 */
function getApiKeyByKey(string $key): ?array {
    foreach (getApiKeys() as $k) {
        if ($k['key'] === $key) return $k;
    }
    return null;
}

/**
 * Get login history
 */
function getLoginHistory(int $limit = 100): array {
    $data = readJson('login_history');
    $history = $data['history'] ?? [];
    return array_reverse(array_slice($history, -$limit));
}

/**
 * Add login history entry
 */
function addLoginHistory(array $entry): bool {
    $data = readJson('login_history');
    $history = $data['history'] ?? [];
    $history[] = $entry;
    if (count($history) > 5000) {
        $history = array_slice($history, -5000);
    }
    return writeJson('login_history', ['history' => $history]);
}
