<?php
function hostingDataPath(string $file): string {
    return __DIR__ . '/../data/' . $file . '.json';
}

function loadHostingData(string $file): array {
    $path = hostingDataPath($file);
    if (!file_exists($path)) return [];
    $raw = file_get_contents($path);
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function saveHostingData(string $file, array $data): bool {
    $path = hostingDataPath($file);
    return file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT)) !== false;
}

function hostingGenerateId(string $prefix = ''): string {
    return $prefix . strtoupper(substr(bin2hex(random_bytes(8)), 0, 10));
}

function hostingSanitize(string $value): string {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

function hostingSetFlash(string $type, string $message): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['hosting_flash'] = ['type' => $type, 'message' => $message];
}

function hostingGetFlash(): ?array {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['hosting_flash'])) return null;
    $flash = $_SESSION['hosting_flash'];
    unset($_SESSION['hosting_flash']);
    return $flash;
}

function hostingGetBaseUrl(): string {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $parts = explode('/', trim($scriptDir, '/'));
    $idx = false;
    foreach (array_reverse($parts, true) as $i => $part) {
        if ($part === 'hosting') {
            $idx = $i;
            break;
        }
    }
    if ($idx !== false) {
        $baseParts = array_slice($parts, 0, $idx + 1);
        return $protocol . '://' . $host . '/' . implode('/', $baseParts);
    }
    return $protocol . '://' . $host;
}

function hostingGetCurrentUser(): array {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return [
        'id' => $_SESSION['hosting_user_id'] ?? '',
        'name' => $_SESSION['hosting_name'] ?? '',
        'email' => $_SESSION['hosting_email'] ?? '',
        'role' => $_SESSION['hosting_role'] ?? '',
    ];
}

function hostingFindById(array $rows, string $id): ?array {
    foreach ($rows as $row) {
        if (($row['id'] ?? '') === $id) return $row;
    }
    return null;
}

function hostingEnsureSeedData(): void {
    static $initialized = false;
    if ($initialized) return;
    $initialized = true;

    $users = loadHostingData('users');
    if (empty($users)) {
        $users[] = [
            'id' => 'UADMIN001',
            'name' => 'Hosting Admin',
            'email' => 'admin@codexhost.local',
            'password' => password_hash('admin123', PASSWORD_DEFAULT),
            'role' => 'admin',
            'created_at' => date('Y-m-d H:i:s')
        ];
        saveHostingData('users', $users);
    }

    $plans = loadHostingData('service_plans');
    if (empty($plans)) {
        $plans = [
            [
                'id' => 'PLAN-STARTER',
                'name' => 'Starter Hosting',
                'price_monthly' => 3.99,
                'storage' => '10 GB SSD',
                'bandwidth' => '100 GB',
                'emails' => '5 Email Accounts',
                'ssl' => 'Free SSL Included',
                'features' => ['cPanel Access', 'Weekly Backups', '1 Website']
            ],
            [
                'id' => 'PLAN-BUSINESS',
                'name' => 'Business Hosting',
                'price_monthly' => 8.99,
                'storage' => '50 GB SSD',
                'bandwidth' => 'Unlimited',
                'emails' => 'Unlimited Emails',
                'ssl' => 'Free SSL + WAF',
                'features' => ['Priority Support', 'Daily Backups', '10 Websites']
            ],
            [
                'id' => 'PLAN-RESELLER',
                'name' => 'Reseller Pro',
                'price_monthly' => 24.99,
                'storage' => '150 GB SSD',
                'bandwidth' => 'Unlimited',
                'emails' => 'Unlimited Emails',
                'ssl' => 'Wildcard SSL Ready',
                'features' => ['WHM Panel', 'White-label', 'Reseller Accounts']
            ]
        ];
        saveHostingData('service_plans', $plans);
    }

    foreach (['services', 'service_requests', 'tickets', 'mails'] as $table) {
        $rows = loadHostingData($table);
        if (!is_array($rows)) {
            saveHostingData($table, []);
        }
    }
}

function hostingAdminEmails(): array {
    $admins = array_filter(loadHostingData('users'), fn($u) => ($u['role'] ?? '') === 'admin');
    return array_values(array_map(fn($u) => $u['email'], $admins));
}

function hostingSendAutoMail(string $to, string $subject, string $message, array $meta = []): bool {
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/plain;charset=UTF-8\r\n";
    $headers .= "From: no-reply@codexhost.local\r\n";

    $sent = false;
    if (function_exists('mail')) {
        $sent = @mail($to, $subject, $message, $headers);
    }

    $mails = loadHostingData('mails');
    $mails[] = [
        'id' => hostingGenerateId('MAIL-'),
        'to' => $to,
        'subject' => $subject,
        'message' => $message,
        'meta' => $meta,
        'status' => $sent ? 'sent' : 'logged_fallback',
        'created_at' => date('Y-m-d H:i:s')
    ];
    saveHostingData('mails', $mails);

    return $sent;
}

function hostingRenderFlash(): string {
    $flash = hostingGetFlash();
    if (!$flash) return '';
    $type = in_array($flash['type'], ['success', 'danger', 'warning', 'info'], true) ? $flash['type'] : 'info';
    $msg = htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8');
    return "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>{$msg}<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
}

function hostingSafeUrl(string $url): string {
    $url = trim($url);
    if ($url === '') return '';
    if (!filter_var($url, FILTER_VALIDATE_URL)) return '';
    $parts = parse_url($url);
    $scheme = strtolower($parts['scheme'] ?? '');
    if (!in_array($scheme, ['http', 'https'], true)) return '';
    return $url;
}

hostingEnsureSeedData();
