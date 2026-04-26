<?php
/**
 * Core Functions & Utilities
 */

/**
 * Generate a unique ID
 */
function generateId(string $prefix = ''): string {
    return ($prefix ? $prefix . '_' : '') . uniqid('', true);
}

/**
 * Get client IP address
 */
function getClientIp(): string {
    $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = trim(explode(',', $_SERVER[$header])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return '0.0.0.0';
}

/**
 * Sanitize string input
 */
function sanitize(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize filename
 */
function sanitizeFilename(string $name): string {
    $name = preg_replace('/[^a-zA-Z0-9_\-.]/', '_', $name);
    $name = preg_replace('/\.{2,}/', '.', $name);
    return trim($name, '._');
}

/**
 * Sanitize username (alphanumeric + underscores/hyphens)
 */
function sanitizeUsername(string $username): string {
    return preg_replace('/[^a-zA-Z0-9_\-]/', '', strtolower($username));
}

/**
 * Format bytes to human-readable
 */
function formatBytes(int $bytes, int $decimals = 2): string {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    return round($bytes / pow(1024, $pow), $decimals) . ' ' . $units[$pow];
}

/**
 * Format date to human-readable
 */
function formatDate(string $date, string $format = 'd M Y'): string {
    return date($format, strtotime($date));
}

/**
 * Get time ago string
 */
function timeAgo(string $date): string {
    $diff = time() - strtotime($date);
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff/60) . ' min ago';
    if ($diff < 86400) return floor($diff/3600) . ' hrs ago';
    if ($diff < 604800) return floor($diff/86400) . ' days ago';
    return formatDate($date);
}

/**
 * Check if user/account has expired
 */
function checkExpiry(?string $expiresAt): bool {
    if (empty($expiresAt)) return false; // no expiry
    return strtotime($expiresAt) < time();
}

/**
 * Calculate expiry date from plan
 */
function calculateExpiry(int $days): string {
    return date('c', strtotime("+{$days} days"));
}

/**
 * Hash password
 */
function hashPassword(string $password): string {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
}

/**
 * Validate email
 */
function isValidEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate password strength
 */
function isStrongPassword(string $password): bool {
    return strlen($password) >= 8;
}

/**
 * Extract ZIP file to directory
 */
function extractZip(string $zipPath, string $destPath): bool {
    if (!class_exists('ZipArchive')) return false;
    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) return false;
    // Security: check for directory traversal in zip entries
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if (strpos($name, '../') !== false || strpos($name, '..\\') !== false) {
            $zip->close();
            return false;
        }
    }
    $zip->extractTo($destPath);
    $zip->close();
    return true;
}

/**
 * Recursively delete a directory
 */
function deleteFolderRecursive(string $path): bool {
    if (!is_dir($path)) return false;
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($items as $item) {
        if ($item->isDir()) {
            rmdir($item->getRealPath());
        } else {
            unlink($item->getRealPath());
        }
    }
    return rmdir($path);
}

/**
 * Get directory size in bytes
 */
function getDirSize(string $path): int {
    if (!is_dir($path)) return 0;
    $size = 0;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $size += $file->getSize();
        }
    }
    return $size;
}

/**
 * Create a user site directory
 */
function createSiteDir(string $username, string $siteName): string {
    $path = USERS_PATH . '/' . $username . '/' . $siteName;
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
    return $path;
}

/**
 * Deploy a script to a site
 */
function deployScript(string $scriptId, string $username, string $siteName, bool $preserveConfig = false): array {
    $script = getScriptById($scriptId);
    if (!$script) {
        return ['success' => false, 'message' => 'Script not found.'];
    }
    if (empty($script['file'])) {
        return ['success' => false, 'message' => 'Script file not uploaded yet.'];
    }
    
    $zipPath = SCRIPTS_PATH . '/' . $script['file'];
    if (!file_exists($zipPath)) {
        return ['success' => false, 'message' => 'Script file missing on server.'];
    }
    
    $sitePath = USERS_PATH . '/' . $username . '/' . $siteName;
    
    // Backup config files if preserving
    $configFiles = [];
    if ($preserveConfig && is_dir($sitePath)) {
        $configPatterns = ['config.php', 'config.json', '.env', 'settings.json'];
        foreach ($configPatterns as $pattern) {
            $file = $sitePath . '/' . $pattern;
            if (file_exists($file)) {
                $configFiles[$pattern] = file_get_contents($file);
            }
        }
    }
    
    // Delete old files
    if (is_dir($sitePath)) {
        deleteFolderRecursive($sitePath);
    }
    
    // Create directory and extract
    mkdir($sitePath, 0755, true);
    if (!extractZip($zipPath, $sitePath)) {
        return ['success' => false, 'message' => 'Failed to extract script.'];
    }
    
    // Restore config files
    foreach ($configFiles as $filename => $content) {
        safeFileWrite($sitePath . '/' . $filename, $content);
    }
    
    // Create .htaccess if custom domain is set
    addLog('script', 'deploy', 'system', ['script' => $scriptId, 'user' => $username, 'site' => $siteName]);
    
    return ['success' => true, 'message' => 'Script deployed successfully.'];
}

/**
 * Create ZIP backup of a site
 */
function backupSite(string $username, string $siteName): ?string {
    if (!class_exists('ZipArchive')) return null;
    
    $sitePath = USERS_PATH . '/' . $username . '/' . $siteName;
    if (!is_dir($sitePath)) return null;
    
    $backupDir = USERS_PATH . '/' . $username . '/backups';
    if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);
    
    $filename = $siteName . '_' . date('Y-m-d_H-i-s') . '.zip';
    $zipPath = $backupDir . '/' . $filename;
    
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) return null;
    
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sitePath, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($files as $file) {
        $filePath = $file->getRealPath();
        $relativePath = substr($filePath, strlen($sitePath) + 1);
        if ($file->isDir()) {
            $zip->addEmptyDir($relativePath);
        } else {
            $zip->addFile($filePath, $relativePath);
        }
    }
    
    $zip->close();
    return $filename;
}

/**
 * Map custom domain to site via .htaccess
 */
function mapCustomDomain(string $domain, string $username, string $siteName): bool {
    $sitePath = USERS_PATH . '/' . $username . '/' . $siteName;
    if (!is_dir($sitePath)) return false;
    
    $htaccess = "# Custom Domain Mapping\n";
    $htaccess .= "Options -Indexes\n";
    $htaccess .= "RewriteEngine On\n";
    $htaccess .= "RewriteBase /\n";
    $htaccess .= "# Domain: {$domain}\n";
    
    return safeFileWrite($sitePath . '/.htaccess', $htaccess);
}

/**
 * Get flash message from session
 */
function getFlash(?string $key = null): mixed {
    if ($key) {
        $msg = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    $all = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $all;
}

/**
 * Set flash message
 */
function setFlash(string $key, mixed $value): void {
    $_SESSION['flash'][$key] = $value;
}

/**
 * JSON response helper
 */
function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Redirect helper
 */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

/**
 * Check file upload for security
 */
function validateUpload(array $file, array $allowedTypes = ['application/zip', 'application/x-zip-compressed'], int $maxSize = 0): array {
    if ($maxSize === 0) $maxSize = MAX_UPLOAD_SIZE;
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds server limit.',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds form limit.',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
        ];
        return ['valid' => false, 'message' => $errors[$file['error']] ?? 'Upload error.'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['valid' => false, 'message' => 'File size exceeds limit of ' . formatBytes($maxSize) . '.'];
    }
    
    // Check MIME type using finfo
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return ['valid' => false, 'message' => 'Invalid file type. Only ZIP files are allowed.'];
    }
    
    return ['valid' => true, 'message' => 'Valid file.'];
}

/**
 * Get device type from user agent
 */
function getDeviceType(string $ua): string {
    if (preg_match('/mobile|android|iphone|ipad/i', $ua)) return 'Mobile';
    if (preg_match('/tablet/i', $ua)) return 'Tablet';
    return 'Desktop';
}

/**
 * Get country from IP (basic - returns Unknown without GeoIP)
 */
function getCountryFromIp(string $ip): string {
    // For production, integrate MaxMind GeoLite2 or similar
    return 'Unknown';
}

/**
 * Generate short code for links
 */
function generateShortCode(int $length = 6): string {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $code;
}

/**
 * Send email notification (basic SMTP via PHPMailer or mail())
 */
function sendEmail(string $to, string $subject, string $body): bool {
    $settings = getSettings();
    $from = $settings['smtp_from'] ?? 'noreply@localhost';
    $fromName = $settings['smtp_from_name'] ?? 'Amrit Web Panel';
    
    $headers = "From: {$fromName} <{$from}>\r\n";
    $headers .= "Reply-To: {$from}\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    
    return @mail($to, $subject, $body, $headers);
}

/**
 * Get plan feature for user
 */
function getUserPlanFeature(array $user, string $feature): mixed {
    $plan = getPlanById($user['plan'] ?? 'basic');
    if (!$plan) return null;
    return $plan['features'][$feature] ?? null;
}

/**
 * Check if user can perform an action based on plan limits
 */
function checkPlanLimit(string $userId, string $limitType): array {
    $user = getUserById($userId);
    if (!$user) return ['allowed' => false, 'message' => 'User not found.'];
    
    $plan = getPlanById($user['plan'] ?? 'basic');
    if (!$plan) return ['allowed' => false, 'message' => 'Plan not found.'];
    
    $features = $plan['features'];
    
    switch ($limitType) {
        case 'sites':
            $max = $features['max_sites'] ?? 1;
            if ($max === -1) return ['allowed' => true, 'message' => 'Unlimited'];
            $current = count(getSitesByUser($userId));
            return ['allowed' => $current < $max, 'message' => "You can create up to {$max} sites on your plan."];
        
        case 'links':
            $max = $features['short_links'] ?? 0;
            if ($max === -1) return ['allowed' => true, 'message' => 'Unlimited'];
            $current = count(getLinksByUser($userId));
            return ['allowed' => $current < $max, 'message' => "You can create up to {$max} short links on your plan."];
        
        case 'custom_domain':
            $allowed = $features['custom_domain'] ?? false;
            return ['allowed' => $allowed, 'message' => $allowed ? 'Allowed' : 'Upgrade to Pro or Premium to use custom domains.'];
        
        case 'api':
            $allowed = $features['api_access'] ?? false;
            return ['allowed' => $allowed, 'message' => $allowed ? 'Allowed' : 'Upgrade to Pro or Premium for API access.'];
        
        case 'backup':
            $allowed = $features['backup'] ?? false;
            return ['allowed' => $allowed, 'message' => $allowed ? 'Allowed' : 'Upgrade your plan to use backups.'];
    }
    
    return ['allowed' => false, 'message' => 'Unknown limit type.'];
}

/**
 * Get all breadcrumb parts for page title
 */
function pageBreadcrumb(array $parts): string {
    $settings = getSettings();
    $siteName = $settings['site_name'] ?? PANEL_NAME;
    array_push($parts, $siteName);
    return implode(' | ', $parts);
}

/**
 * Render pagination HTML
 */
function paginate(array $items, int $page, int $perPage = 20): array {
    $total = count($items);
    $totalPages = max(1, ceil($total / $perPage));
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $perPage;
    return [
        'items' => array_slice($items, $offset, $perPage),
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'total_pages' => $totalPages,
    ];
}

/**
 * Output pagination HTML
 */
function paginationHtml(array $paging, string $baseUrl): string {
    if ($paging['total_pages'] <= 1) return '';
    $html = '<nav class="pagination"><ul class="pagination-list">';
    for ($i = 1; $i <= $paging['total_pages']; $i++) {
        $active = $i === $paging['page'] ? ' active' : '';
        $html .= "<li><a href=\"{$baseUrl}&page={$i}\" class=\"page-link{$active}\">{$i}</a></li>";
    }
    $html .= '</ul></nav>';
    return $html;
}
