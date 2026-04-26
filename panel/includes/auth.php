<?php
/**
 * Authentication & Session Management
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/json_db.php';
require_once __DIR__ . '/functions.php';

/**
 * Attempt login
 * @return array ['success' => bool, 'message' => string, 'user' => array|null]
 */
function attemptLogin(string $username, string $password, string $ip): array {
    $settings = getSettings();
    $lockoutKey = 'login_attempts_' . md5($ip . '_' . $username);
    
    // Check lockout
    $attempts = $_SESSION[$lockoutKey . '_count'] ?? 0;
    $lockoutTime = $_SESSION[$lockoutKey . '_time'] ?? 0;
    $maxAttempts = (int)($settings['login_attempts_limit'] ?? MAX_LOGIN_ATTEMPTS);
    $lockoutDuration = (int)($settings['login_lockout_minutes'] ?? 30) * 60;
    
    if ($attempts >= $maxAttempts && (time() - $lockoutTime) < $lockoutDuration) {
        $remaining = ceil(($lockoutDuration - (time() - $lockoutTime)) / 60);
        return ['success' => false, 'message' => "Account locked. Try again in {$remaining} minutes.", 'user' => null];
    }
    
    // Reset attempts if lockout expired
    if ((time() - $lockoutTime) >= $lockoutDuration) {
        $_SESSION[$lockoutKey . '_count'] = 0;
    }
    
    // Find user
    $user = getUserByUsername($username);
    if (!$user) {
        $user = getUserByEmail($username);
    }
    
    if (!$user) {
        $_SESSION[$lockoutKey . '_count'] = ($attempts + 1);
        $_SESSION[$lockoutKey . '_time'] = time();
        addLoginHistory([
            'id' => 'lh_' . uniqid(),
            'username' => $username,
            'ip' => $ip,
            'status' => 'failed',
            'reason' => 'User not found',
            'created_at' => date('c')
        ]);
        return ['success' => false, 'message' => 'Invalid username or password.', 'user' => null];
    }
    
    // Check password
    if (!password_verify($password, $user['password'])) {
        $_SESSION[$lockoutKey . '_count'] = ($attempts + 1);
        $_SESSION[$lockoutKey . '_time'] = time();
        addLoginHistory([
            'id' => 'lh_' . uniqid(),
            'username' => $username,
            'ip' => $ip,
            'user_id' => $user['id'],
            'status' => 'failed',
            'reason' => 'Wrong password',
            'created_at' => date('c')
        ]);
        return ['success' => false, 'message' => 'Invalid username or password.', 'user' => null];
    }
    
    // Check status
    if ($user['status'] !== 'active') {
        return ['success' => false, 'message' => 'Your account has been suspended. Please contact support.', 'user' => null];
    }
    
    // Check expiry
    if ($user['role'] !== 'admin' && !empty($user['expires_at'])) {
        if (strtotime($user['expires_at']) < time()) {
            return ['success' => false, 'message' => 'Your account has expired. Please renew your subscription.', 'user' => null];
        }
    }
    
    // Success - reset attempts
    $_SESSION[$lockoutKey . '_count'] = 0;
    
    // Update last login
    updateUser($user['id'], [
        'last_login' => date('c'),
        'login_ip' => $ip
    ]);
    
    // Log successful login
    addLoginHistory([
        'id' => 'lh_' . uniqid(),
        'username' => $username,
        'ip' => $ip,
        'user_id' => $user['id'],
        'status' => 'success',
        'reason' => null,
        'created_at' => date('c')
    ]);
    
    addLog('auth', 'login', $user['id'], ['ip' => $ip]);
    
    return ['success' => true, 'message' => 'Login successful.', 'user' => $user];
}

/**
 * Start user session
 */
function startUserSession(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['logged_in']) && !empty($_SESSION['user_id']);
}

/**
 * Get current logged-in user
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    return getUserById($_SESSION['user_id']);
}

/**
 * Get current user role
 */
function getCurrentRole(): string {
    return $_SESSION['role'] ?? '';
}

/**
 * Require login - redirect if not logged in
 */
function requireLogin(string $redirectTo = '/panel/index.php'): void {
    if (!isLoggedIn()) {
        header('Location: ' . $redirectTo . '?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? ''));
        exit;
    }
}

/**
 * Require a specific role
 */
function requireRole(string ...$roles): void {
    requireLogin();
    if (!in_array(getCurrentRole(), $roles)) {
        http_response_code(403);
        // Redirect to their own dashboard
        $role = getCurrentRole();
        if ($role === 'admin') {
            header('Location: /panel/admin/index.php');
        } elseif ($role === 'reseller') {
            header('Location: /panel/reseller/index.php');
        } else {
            header('Location: /panel/user/index.php');
        }
        exit;
    }
}

/**
 * Logout current user
 */
function logout(): void {
    if (isLoggedIn()) {
        addLog('auth', 'logout', $_SESSION['user_id'] ?? 'unknown');
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

/**
 * Generate CSRF token
 */
function getCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCsrf(): bool {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * CSRF token input field HTML
 */
function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(getCsrfToken()) . '">';
}

/**
 * Require CSRF verification - die if invalid
 */
function requireCsrf(): void {
    if (!verifyCsrf()) {
        http_response_code(403);
        die(json_encode(['error' => 'Invalid CSRF token']));
    }
}

/**
 * Authenticate via API key
 */
function authenticateApiKey(string $key): ?array {
    $keyData = getApiKeyByKey($key);
    if (!$keyData) return null;
    if ($keyData['status'] !== 'active') return null;
    if (!empty($keyData['expires_at']) && strtotime($keyData['expires_at']) < time()) return null;
    
    // Check rate limit
    $rateLimitKey = 'api_rate_' . md5($key);
    $requests = $_SESSION[$rateLimitKey . '_count'] ?? 0;
    $windowStart = $_SESSION[$rateLimitKey . '_window'] ?? time();
    
    if ((time() - $windowStart) > 60) {
        $_SESSION[$rateLimitKey . '_count'] = 1;
        $_SESSION[$rateLimitKey . '_window'] = time();
    } elseif ($requests >= ($keyData['rate_limit'] ?? 100)) {
        return null;
    } else {
        $_SESSION[$rateLimitKey . '_count'] = $requests + 1;
    }
    
    // Update last used
    $keys = getApiKeys();
    foreach ($keys as &$k) {
        if ($k['key'] === $key) {
            $k['last_used'] = date('c');
            $k['requests'] = ($k['requests'] ?? 0) + 1;
        }
    }
    saveApiKeys($keys);
    
    return getUserById($keyData['user_id']);
}
