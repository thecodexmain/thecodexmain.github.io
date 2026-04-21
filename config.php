<?php
/**
 * config.php – Application constants and admin credentials
 *
 * SETUP:
 *  1. Change ADMIN_PASSWORD_HASH to the output of:
 *       php -r "echo password_hash('yourpassword', PASSWORD_DEFAULT);"
 *  2. Set a strong, random SESSION_SECRET (16+ chars).
 *  3. Ensure /data and /uploads are writable by the web-server user:
 *       chmod 755 data uploads
 */

// ──────────────────────────────────────────────
//  Admin credentials  (bcrypt hash)
// ──────────────────────────────────────────────
// IMPORTANT: Change this before going live!
// Generate a new hash with:
//   php -r "echo password_hash('YourStrongPassword', PASSWORD_DEFAULT);"
// Default password: admin123  ← CHANGE THIS before going live
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD_HASH', '$2y$12$EixZaYVK1fsbw1ZfbX3OXePaWxn96p36WQoeG6Lruj3vjPGga31lW'); // admin123

// ──────────────────────────────────────────────
//  Paths  (relative to public_html root)
// ──────────────────────────────────────────────
define('DATA_DIR',     __DIR__ . '/data/');
define('UPLOADS_DIR',  __DIR__ . '/uploads/');
define('SETTINGS_FILE', DATA_DIR . 'settings.json');
define('CLICKS_FILE',   DATA_DIR . 'clicks.json');

// ──────────────────────────────────────────────
//  Session security
// ──────────────────────────────────────────────
define('SESSION_NAME', 'codex_admin');

// ──────────────────────────────────────────────
//  Allowed upload MIME types
// ──────────────────────────────────────────────
define('ALLOWED_VIDEO_MIME', ['video/mp4']);
define('ALLOWED_IMAGE_MIME', ['image/jpeg', 'image/png', 'image/webp']);
define('MAX_UPLOAD_BYTES',    52428800);   // 50 MB

// ──────────────────────────────────────────────
//  Helpers
// ──────────────────────────────────────────────

/**
 * Read settings.json with a shared lock.
 */
function read_settings(): array {
    if (!file_exists(SETTINGS_FILE)) {
        return default_settings();
    }
    $fp = fopen(SETTINGS_FILE, 'r');
    if (!$fp) return default_settings();
    if (!flock($fp, LOCK_SH)) {
        fclose($fp);
        return default_settings();
    }
    $data = stream_get_contents($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    $decoded = json_decode($data, true);
    return is_array($decoded) ? array_merge(default_settings(), $decoded) : default_settings();
}

/**
 * Write settings.json with an exclusive lock.
 */
function write_settings(array $settings): bool {
    if (!is_dir(DATA_DIR)) mkdir(DATA_DIR, 0755, true);
    $fp = fopen(SETTINGS_FILE, 'c');
    if (!$fp) return false;
    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        return false;
    }
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    flock($fp, LOCK_UN);
    fclose($fp);
    return true;
}

/**
 * Log a CTA click.
 */
function log_click(): void {
    if (!is_dir(DATA_DIR)) mkdir(DATA_DIR, 0755, true);
    $fp = fopen(CLICKS_FILE, 'c+');
    if (!$fp) return;
    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        return;
    }
    $data = stream_get_contents($fp);
    $clicks = json_decode($data, true);
    if (!is_array($clicks)) $clicks = [];
    $today = date('Y-m-d');
    $clicks[$today] = ($clicks[$today] ?? 0) + 1;
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($clicks, JSON_PRETTY_PRINT));
    flock($fp, LOCK_UN);
    fclose($fp);
}

/**
 * Sanitise a string for HTML output.
 */
function esc(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Default settings used when settings.json is missing / corrupt.
 */
function default_settings(): array {
    return [
        'headline'       => 'Your App Title',
        'subheadline'    => 'Catchy subtitle here 🔥',
        'video'          => 'uploads/bg.mp4',
        'fallback_image' => 'uploads/bg.jpg',
        'download_link'  => '#',
        'countdown'      => 10,
        'slots'          => 24,
        'animations'     => true,
        'btn_primary'    => 'Download Now',
        'btn_secondary'  => 'Learn More',
        'badge_text'     => 'Limited slots available!',
    ];
}
