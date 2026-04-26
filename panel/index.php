<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/json_db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// Check maintenance mode
$settings = getSettings();
if (!empty($settings['maintenance_mode'])) {
    // Allow admin to bypass via ?bypass=1
    if (empty($_GET['bypass'])) {
        include __DIR__ . '/maintenance.php';
        exit;
    }
}

// Redirect if already logged in
if (isLoggedIn()) {
    $role = getCurrentRole();
    redirect('/panel/' . $role . '/index.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $result = attemptLogin($username, $password, getClientIp());
        if ($result['success']) {
            startUserSession($result['user']);
            $role = $result['user']['role'];
            // Handle redirect
            $redirect = $_GET['redirect'] ?? '/panel/' . $role . '/index.php';
            // Security: only allow redirects within /panel
            if (!preg_match('#^/panel/#', $redirect)) {
                $redirect = '/panel/' . $role . '/index.php';
            }
            redirect($redirect);
        } else {
            $error = $result['message'];
        }
    }
}

$pageTitle = ($settings['meta_title'] ?? $settings['site_name'] ?? 'Amrit Web Panel') . ' - Login';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($settings['meta_description'] ?? '') ?>">
    <link rel="icon" href="/panel/assets/img/favicon.png" type="image/png">
    <meta name="theme-color" content="<?= htmlspecialchars($settings['theme_color'] ?? '#4f46e5') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/panel/assets/css/main.css">
    <style>
        :root { --primary: <?= htmlspecialchars($settings['theme_color'] ?? '#4f46e5') ?>; }
        .login-page { background: linear-gradient(135deg, var(--primary) 0%, color-mix(in srgb, var(--primary) 60%, #7c3aed) 50%, #2563eb 100%); }
        .show-password-btn { position: absolute; right: 0.875rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-muted); cursor: pointer; padding: 0; }
        .password-field { position: relative; }
        .password-field .form-control { padding-right: 2.5rem; }
    </style>
</head>
<body>
<div class="login-page">
    <div class="login-card fade-in">
        <div class="login-logo">
            <img src="/panel/<?= htmlspecialchars($settings['logo'] ?? 'assets/img/logo.png') ?>" alt="Logo" onerror="this.style.display='none'">
            <h1><?= htmlspecialchars($settings['site_name'] ?? 'Amrit Web Panel') ?></h1>
            <p>Sign in to your account</p>
        </div>
        
        <?php if ($error): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" data-validate>
            <?= csrfField() ?>
            <div class="form-group">
                <label class="form-label" for="username">Username or Email</label>
                <div style="position:relative;">
                    <input type="text" id="username" name="username" class="form-control" 
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           placeholder="Enter username or email" required autocomplete="username"
                           style="padding-left: 2.5rem;">
                    <i class="fas fa-user" style="position:absolute;left:.875rem;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:.875rem;"></i>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <div class="password-field">
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="Enter password" required autocomplete="current-password"
                           style="padding-left: 2.5rem;">
                    <i class="fas fa-lock" style="position:absolute;left:.875rem;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:.875rem;pointer-events:none;"></i>
                    <button type="button" class="show-password-btn" onclick="togglePassword()">
                        <i class="fas fa-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100" style="margin-top:.5rem;padding:.75rem;font-size:1rem;">
                <i class="fas fa-sign-in-alt"></i> Sign In
            </button>
        </form>
        
        <div style="text-align:center;margin-top:1.5rem;font-size:.8125rem;color:var(--text-muted);">
            <p>Default login: <strong>admin</strong> / <strong>password</strong></p>
            <p style="margin-top:.5rem;">
                Powered by <a href="#" style="color:var(--primary);"><?= htmlspecialchars($settings['site_name'] ?? 'Amrit Web Panel') ?></a>
            </p>
        </div>
    </div>
</div>

<script src="/panel/assets/js/main.js"></script>
<script>
function togglePassword() {
    const field = document.getElementById('password');
    const icon = document.getElementById('eyeIcon');
    if (field.type === 'password') {
        field.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        field.type = 'password';
        icon.className = 'fas fa-eye';
    }
}
const savedTheme = localStorage.getItem('awp-theme') || 'light';
document.documentElement.setAttribute('data-theme', savedTheme);
</script>
</body>
</html>
