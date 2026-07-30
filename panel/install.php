<?php
// Install/setup wizard — only runs when admin account needs initial setup
$dataDir = __DIR__ . '/data/';
$usersFile = $dataDir . 'users.json';

// If users.json already has an admin, redirect to panel
if (file_exists($usersFile)) {
    $users = json_decode(file_get_contents($usersFile), true) ?? [];
    foreach ($users as $u) {
        if (($u['role'] ?? '') === 'admin') {
            header('Location: index.php');
            exit;
        }
    }
}

$step = (int)($_GET['step'] ?? 1);
$error = '';
$success = '';

$settingsFile = $dataDir . 'settings.json';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'setup_admin') {
        $siteName = trim($_POST['site_name'] ?? 'Prime Webs');
        $adminUser = trim($_POST['admin_user'] ?? '');
        $adminEmail = trim($_POST['admin_email'] ?? '');
        $adminPass = $_POST['admin_pass'] ?? '';
        $adminPass2 = $_POST['admin_pass2'] ?? '';

        if (!$adminUser || !$adminEmail || !$adminPass) {
            $error = 'All fields are required.';
        } elseif (strlen($adminPass) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif ($adminPass !== $adminPass2) {
            $error = 'Passwords do not match.';
        } elseif (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address.';
        } else {
            // Create directories
            $dirs = ['uploads/', 'uploads/scripts/', 'uploads/backups/', 'users/', 'data/'];
            foreach ($dirs as $d) {
                $path = __DIR__ . '/' . $d;
                if (!is_dir($path)) mkdir($path, 0755, true);
            }

            // Initialize all JSON files
            $jsonFiles = [
                'users.json' => [], 'resellers.json' => [], 'plans.json' => [],
                'scripts.json' => [], 'sites.json' => [], 'notifications.json' => [],
                'tickets.json' => [], 'links.json' => [], 'logs.json' => [],
                'api_keys.json' => []
            ];
            foreach ($jsonFiles as $fname => $empty) {
                $path = $dataDir . $fname;
                if (!file_exists($path)) file_put_contents($path, json_encode($empty));
            }

            // Create admin user
            $adminData = ['id' => 'u001', 'username' => $adminUser, 'email' => $adminEmail,
                'password' => password_hash($adminPass, PASSWORD_BCRYPT), 'role' => 'admin',
                'plan' => 'admin', 'status' => 'active', 'reseller_id' => null, 'credits' => 999999,
                'created_at' => date('c'), 'last_login' => null, 'failed_logins' => 0,
                'locked_until' => null, 'sites' => [], 'expiry' => null];
            file_put_contents($usersFile, json_encode([$adminData], JSON_PRETTY_PRINT));

            // Create default plans
            $plans = [
                ['id' => 'p001', 'name' => 'basic', 'label' => 'Basic', 'price' => 5, 'reseller_price' => 3, 'sites_limit' => 2, 'storage_mb' => 512, 'links_limit' => 20, 'status' => 'active'],
                ['id' => 'p002', 'name' => 'pro', 'label' => 'Pro', 'price' => 15, 'reseller_price' => 10, 'sites_limit' => 10, 'storage_mb' => 2048, 'links_limit' => 100, 'status' => 'active'],
                ['id' => 'p003', 'name' => 'premium', 'label' => 'Premium', 'price' => 30, 'reseller_price' => 20, 'sites_limit' => 50, 'storage_mb' => 10240, 'links_limit' => 0, 'status' => 'active'],
            ];
            file_put_contents($dataDir . 'plans.json', json_encode($plans, JSON_PRETTY_PRINT));

            // Save settings
            $settings = [
                'site_name' => $siteName, 'site_description' => 'Web Selling & Script Deployer Panel',
                'theme_color' => '#6c5ce7', 'logo_url' => '', 'favicon_url' => '',
                'maintenance_mode' => false, 'maintenance_message' => "We'll be back shortly!",
                'allow_registration' => false, 'max_login_attempts' => 5, 'lockout_minutes' => 15,
                'timezone' => 'UTC', 'currency' => 'USD', 'smtp_host' => '', 'smtp_port' => 587,
                'smtp_user' => '', 'smtp_pass' => '', 'smtp_from' => $adminEmail,
            ];
            file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT));

            $success = "Setup complete! Redirecting to login...";
            header('Refresh: 3; URL=index.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prime Webs — Installation</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { min-height: 100vh; background: #0a0a0a; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', system-ui, sans-serif; color: #e0e0e0; }
        .container { width: 100%; max-width: 520px; padding: 20px; }
        .brand { text-align: center; margin-bottom: 32px; }
        .brand-icon { font-size: 56px; margin-bottom: 12px; }
        .brand h1 { font-size: 28px; font-weight: 800; color: #6c5ce7; }
        .brand p { color: #888; font-size: 14px; margin-top: 4px; }
        .card { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; padding: 32px; }
        .card h2 { font-size: 18px; margin-bottom: 20px; color: #fff; }
        .form-group { margin-bottom: 16px; }
        label { display: block; font-size: 13px; color: #aaa; margin-bottom: 6px; }
        input { width: 100%; padding: 12px 14px; background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.12); border-radius: 8px; color: #e0e0e0; font-size: 14px; outline: none; transition: border-color 0.2s; }
        input:focus { border-color: #6c5ce7; }
        .btn { width: 100%; padding: 14px; background: #6c5ce7; color: #fff; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; margin-top: 8px; }
        .btn:hover { background: #5a4bd1; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; }
        .alert-danger { background: rgba(255,77,77,0.15); color: #ff4d4d; border: 1px solid rgba(255,77,77,0.3); }
        .alert-success { background: rgba(0,184,148,0.15); color: #00b894; border: 1px solid rgba(0,184,148,0.3); }
        .req-list { list-style: none; padding: 0; margin-bottom: 20px; }
        .req-list li { padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 13px; display: flex; justify-content: space-between; }
        .ok { color: #00b894; }
        .fail { color: #ff4d4d; }
    </style>
</head>
<body>
<div class="container">
    <div class="brand">
        <div class="brand-icon">🚀</div>
        <h1>Prime Webs</h1>
        <p>Web Selling Panel — Installation Wizard</p>
    </div>

    <?php
    // Check requirements
    $phpOk = version_compare(PHP_VERSION, '7.4', '>=');
    $zipOk = class_exists('ZipArchive');
    $jsonOk = function_exists('json_encode');
    $writable = is_writable($dataDir) || @mkdir($dataDir, 0755, true);
    $allOk = $phpOk && $zipOk && $jsonOk && $writable;
    ?>

    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <div class="card">
        <h2>📋 System Requirements</h2>
        <ul class="req-list">
            <li><span>PHP Version (7.4+)</span><span class="<?= $phpOk ? 'ok' : 'fail' ?>"><?= $phpOk ? '✅ '.PHP_VERSION : '❌ '.PHP_VERSION.' (need 7.4+)' ?></span></li>
            <li><span>ZipArchive Extension</span><span class="<?= $zipOk ? 'ok' : 'fail' ?>"><?= $zipOk ? '✅ Enabled' : '❌ Not found' ?></span></li>
            <li><span>JSON Extension</span><span class="<?= $jsonOk ? 'ok' : 'fail' ?>"><?= $jsonOk ? '✅ Enabled' : '❌ Not found' ?></span></li>
            <li><span>Data Directory Writable</span><span class="<?= $writable ? 'ok' : 'fail' ?>"><?= $writable ? '✅ Yes' : '❌ No — chmod 755 panel/data/' ?></span></li>
        </ul>

        <?php if (!$allOk): ?>
        <div class="alert alert-danger">Please fix the requirements above before proceeding.</div>
        <?php else: ?>
        <h2 style="margin-bottom:20px;">⚙️ Admin Account Setup</h2>
        <form method="POST">
            <input type="hidden" name="action" value="setup_admin">
            <div class="form-group"><label>Site Name</label><input type="text" name="site_name" value="Prime Webs" required></div>
            <div class="form-group"><label>Admin Username</label><input type="text" name="admin_user" placeholder="admin" required></div>
            <div class="form-group"><label>Admin Email</label><input type="email" name="admin_email" required></div>
            <div class="form-group"><label>Password (min 8 chars)</label><input type="password" name="admin_pass" minlength="8" required></div>
            <div class="form-group"><label>Confirm Password</label><input type="password" name="admin_pass2" minlength="8" required></div>
            <button type="submit" class="btn">🚀 Install Prime Webs</button>
        </form>
        <?php endif; ?>
    </div>

    <p style="text-align:center;font-size:12px;color:#555;margin-top:20px;">
        ⚠️ Delete <code>install.php</code> after setup. Prime Webs — made by @PrimeTheOfficial
    </p>
</div>
</body>
</html>
