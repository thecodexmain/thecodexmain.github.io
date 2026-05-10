<?php

function hostingDataPath(string $file): string {
    return __DIR__ . '/../data/' . $file . '.json';
}

function hostingLoadData(string $file): array {
    $path = hostingDataPath($file);
    if (!file_exists($path)) return [];
    $json = file_get_contents($path);
    if ($json === false || $json === '') return [];
    return json_decode($json, true) ?? [];
}

function hostingSaveData(string $file, array $data): bool {
    $path = hostingDataPath($file);
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    return file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX) !== false;
}

function hostingGenerateId(string $prefix = ''): string {
    return $prefix . strtoupper(bin2hex(random_bytes(4)));
}

function hostingNow(): string {
    return date('Y-m-d H:i:s');
}

function hostingSanitize(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function hostingSetFlash(string $type, string $message): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function hostingGetFlash(): ?array {
    if (!isset($_SESSION['flash'])) return null;
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function hostingRenderFlash(): string {
    $flash = hostingGetFlash();
    if (!$flash) return '';
    $type = $flash['type'] === 'success' ? 'success' : ($flash['type'] === 'error' ? 'danger' : $flash['type']);
    $msg = htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8');
    return "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>{$msg}<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
}

function hostingCsrfToken(): string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf_token'];
}

function hostingVerifyCsrf(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $token = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        hostingSetFlash('error', 'Security check failed. Please try again.');
        header('Location: ' . hostingGetBaseUrl() . '/index.php');
        exit;
    }
}

function hostingGetSettings(): array {
    $path = hostingDataPath('settings');
    $defaults = [
        'brand_name' => 'CodexHost',
        'tagline' => 'Fast, secure, and managed hosting',
        'support_email' => 'support@example.com',
        'theme_color' => '#4f46e5',
        'mail' => [
            'use_smtp' => false,
            'smtp_host' => '',
            'smtp_port' => 587,
            'smtp_user' => '',
            'smtp_pass' => '',
            'smtp_secure' => 'tls',
            'smtp_timeout' => 10,
            'from_email' => 'noreply@example.com',
            'from_name' => 'CodexHost'
        ]
    ];
    if (!file_exists($path)) {
        hostingSaveData('settings', $defaults);
        return $defaults;
    }
    $data = json_decode(file_get_contents($path) ?: '', true);
    if (!$data) return $defaults;
    return array_replace_recursive($defaults, $data);
}

function hostingGetBaseUrl(): string {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $parts = explode('/', trim($scriptDir, '/'));
    $idx = false;
    foreach (array_reverse($parts, true) as $i => $part) {
        if ($part === 'hosting') { $idx = $i; break; }
    }
    if ($idx !== false) {
        $baseParts = array_slice($parts, 0, $idx + 1);
        return $protocol . '://' . $host . '/' . implode('/', $baseParts);
    }
    return $protocol . '://' . $host . ($scriptDir === '/' ? '' : $scriptDir);
}

function hostingEnsureDefaultServices(): array {
    $services = hostingLoadData('services');
    if (!empty($services)) return $services;
    $services = [
        [
            'id' => 'S-' . hostingGenerateId(),
            'name' => 'Starter Hosting',
            'price' => 2.99,
            'billing_cycle' => 'monthly',
            'active' => true,
            'features' => ['1 Website', '10GB SSD', 'Free SSL', 'Email Support']
        ],
        [
            'id' => 'S-' . hostingGenerateId(),
            'name' => 'Pro Hosting',
            'price' => 6.99,
            'billing_cycle' => 'monthly',
            'active' => true,
            'features' => ['Unlimited Websites', '50GB SSD', 'Daily Backups', 'Priority Support']
        ],
        [
            'id' => 'S-' . hostingGenerateId(),
            'name' => 'Managed VPS',
            'price' => 24.99,
            'billing_cycle' => 'monthly',
            'active' => true,
            'features' => ['2 vCPU / 4GB RAM', '80GB NVMe', 'Root Access', 'Managed Updates']
        ]
    ];
    hostingSaveData('services', $services);
    return $services;
}

function hostingFindService(string $serviceId): ?array {
    $services = hostingEnsureDefaultServices();
    foreach ($services as $s) {
        if (($s['id'] ?? '') === $serviceId) return $s;
    }
    return null;
}

function hostingFindUser(string $userId): ?array {
    $users = hostingLoadData('users');
    foreach ($users as $u) {
        if (($u['id'] ?? '') === $userId) return $u;
    }
    return null;
}
