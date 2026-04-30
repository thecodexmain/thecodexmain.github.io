<?php
// ─────────────────────────────────────────────────────────────
//  Keymaster – Central Configuration
// ─────────────────────────────────────────────────────────────

// ── Admin credentials ────────────────────────────────────────
// IMPORTANT: Change ADMIN_PASS and ADMIN_TOKEN before deploying.
// Use a strong, unique password (12+ chars, mixed case, digits, symbols).
// Use a long random string for ADMIN_TOKEN (e.g. output of: openssl rand -hex 32).
define('ADMIN_USER',     'admin');
define('ADMIN_PASS',     'changeme123');   // plain-text; bcrypt-hashed on first run
define('ADMIN_TOKEN',    'supersecretadmintoken123'); // protect all admin API endpoints

// ── SQLite database path (writable by web server) ────────────
define('DB_PATH', __DIR__ . '/data/keymaster.sqlite');

// ── App settings ─────────────────────────────────────────────
define('APP_NAME', 'Keymaster');
define('APP_VERSION', '1.0.0');

// ── Timezone ─────────────────────────────────────────────────
date_default_timezone_set('UTC');

// ─────────────────────────────────────────────────────────────
//  Database bootstrap
// ─────────────────────────────────────────────────────────────
function get_db(): PDO
{
    static $db = null;
    if ($db !== null) {
        return $db;
    }

    $dir = dirname(DB_PATH);
    if (!is_dir($dir)) {
        mkdir($dir, 0700, true);
    }

    $db = new PDO('sqlite:' . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db->exec('PRAGMA journal_mode=WAL;');

    // Keys table
    $db->exec("
        CREATE TABLE IF NOT EXISTS keys (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            device_id   TEXT    UNIQUE NOT NULL,
            api_key     TEXT    UNIQUE NOT NULL,
            plan        TEXT    NOT NULL DEFAULT 'basic',
            days        INTEGER NOT NULL DEFAULT 30,
            created_at  TEXT    NOT NULL DEFAULT (datetime('now')),
            expires_at  TEXT    NOT NULL,
            status      TEXT    NOT NULL DEFAULT 'active'
        );
    ");

    // Settings table (key/value store)
    $db->exec("
        CREATE TABLE IF NOT EXISTS settings (
            key   TEXT PRIMARY KEY,
            value TEXT NOT NULL DEFAULT ''
        );
    ");

    // Pending registration requests table
    $db->exec("
        CREATE TABLE IF NOT EXISTS requests (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            device_id    TEXT    UNIQUE NOT NULL,
            plan         TEXT    NOT NULL DEFAULT 'basic',
            note         TEXT    NOT NULL DEFAULT '',
            requested_at TEXT    NOT NULL DEFAULT (datetime('now')),
            status       TEXT    NOT NULL DEFAULT 'pending'
        );
    ");

    // Default settings
    $defaults = [
        'registrations_open' => '1',
        'admin_pass_hash'    => password_hash(ADMIN_PASS, PASSWORD_DEFAULT),
    ];
    $ins = $db->prepare(
        "INSERT OR IGNORE INTO settings (key, value) VALUES (:k, :v)"
    );
    foreach ($defaults as $k => $v) {
        $ins->execute([':k' => $k, ':v' => $v]);
    }

    return $db;
}

// ─────────────────────────────────────────────────────────────
//  Helper utilities
// ─────────────────────────────────────────────────────────────

/** Generate a cryptographically random API key. */
function generate_api_key(): string
{
    return strtoupper(bin2hex(random_bytes(16)));
}

/** Fetch a single setting value. */
function get_setting(string $key, string $default = ''): string
{
    $db  = get_db();
    $stm = $db->prepare("SELECT value FROM settings WHERE key = :k");
    $stm->execute([':k' => $key]);
    $row = $stm->fetch();
    return $row ? $row['value'] : $default;
}

/** Update a setting. */
function set_setting(string $key, string $value): void
{
    $db  = get_db();
    $stm = $db->prepare(
        "INSERT INTO settings (key, value) VALUES (:k, :v)
         ON CONFLICT(key) DO UPDATE SET value = :v"
    );
    $stm->execute([':k' => $key, ':v' => $value]);
}

/** Return key row with computed days_left and live status. */
function get_key_status(string $device_id): ?array
{
    $db  = get_db();
    $stm = $db->prepare(
        "SELECT * FROM keys WHERE device_id = :d COLLATE NOCASE"
    );
    $stm->execute([':d' => $device_id]);
    $row = $stm->fetch();
    if (!$row) {
        return null;
    }

    $now        = new DateTime('now');
    $expires    = new DateTime($row['expires_at']);
    $diff       = $now->diff($expires);
    $days_left  = ($expires > $now) ? (int)$diff->days : 0;
    $is_expired = ($expires <= $now);

    // Auto-update status in DB if it has expired
    if ($is_expired && $row['status'] === 'active') {
        $upd = $db->prepare(
            "UPDATE keys SET status = 'expired' WHERE device_id = :d"
        );
        $upd->execute([':d' => $device_id]);
        $row['status'] = 'expired';
    }

    $row['days_left']  = $days_left;
    $row['is_expired'] = $is_expired;
    return $row;
}

/** Admin session check (returns true if logged in). */
function admin_logged_in(): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return !empty($_SESSION['admin_ok']);
}

/** Require admin login – redirect if not. */
function require_admin(): void
{
    if (!admin_logged_in()) {
        header('Location: admin_login.php');
        exit;
    }
}

/** JSON response helper. */
function json_out(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

/** Sanitise a device-ID string (alphanumeric + hyphens/underscores). */
function sanitise_device_id(string $raw): string
{
    return preg_replace('/[^A-Za-z0-9_\-]/', '', trim($raw));
}

/** Fetch all pending registration requests. */
function get_pending_requests(): array
{
    $db  = get_db();
    $stm = $db->query("SELECT * FROM requests WHERE status = 'pending' ORDER BY requested_at ASC");
    return $stm->fetchAll();
}

/** Count pending requests. */
function count_pending_requests(): int
{
    $db  = get_db();
    $stm = $db->query("SELECT COUNT(*) FROM requests WHERE status = 'pending'");
    return (int)$stm->fetchColumn();
}
