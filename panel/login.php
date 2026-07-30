<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Already logged in
if (isLoggedIn()) {
    redirect(BASE_URL);
}

$error = '';
$msg = '';

if (isset($_GET['msg']) && $_GET['msg'] === 'logged_out') {
    $msg = 'You have been signed out successfully.';
}
if (isset($_GET['error']) && $_GET['error'] === 'unauthorized') {
    $error = 'Access denied. You do not have permission to access that page.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST[CSRF_TOKEN_NAME] ?? '';
    if (!validateCSRF($token)) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Please enter your username and password.';
        } else {
            $result = login($username, $password);
            if ($result['success']) {
                $role = $result['role'];
                $redirect = $_GET['redirect'] ?? '';
                if ($redirect && strpos($redirect, BASE_URL) === 0) {
                    redirect($redirect);
                }
                switch ($role) {
                    case 'admin': redirect(BASE_URL . 'admin/'); break;
                    case 'reseller': redirect(BASE_URL . 'reseller/'); break;
                    default: redirect(BASE_URL . 'user/'); break;
                }
            } else {
                $error = $result['error'];
            }
        }
    }
}

$siteName = getSetting('site_name', 'Prime Webs');
$siteLogo = getSetting('site_logo', '');
$csrf = generateCSRF();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - <?= htmlspecialchars($siteName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <script>(function(){var t=localStorage.getItem('pw_theme')||'light';document.documentElement.setAttribute('data-theme',t);})();</script>
</head>
<body>
<div class="login-page">
    <div class="login-card animate-in">
        <div class="login-logo">
            <?php if ($siteLogo): ?>
                <img src="<?= htmlspecialchars($siteLogo) ?>" alt="Logo" style="width:64px;height:64px;border-radius:12px;margin-bottom:12px;">
            <?php else: ?>
                <div class="logo-icon">P</div>
            <?php endif; ?>
            <h1><?= htmlspecialchars($siteName) ?></h1>
            <p>Web Hosting & Script Deployment Panel</p>
        </div>

        <?php if ($msg): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-danger">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="" autocomplete="on">
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrf ?>">

            <div class="form-group">
                <label for="username">Username or Email</label>
                <input type="text" class="form-control" id="username" name="username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                       placeholder="Enter your username or email"
                       autocomplete="username" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div style="position:relative;">
                    <input type="password" class="form-control" id="password" name="password"
                           placeholder="Enter your password"
                           autocomplete="current-password" required>
                    <button type="button" onclick="togglePwd()" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:16px;color:var(--text-muted);">👁️</button>
                </div>
            </div>

            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-weight:400;">
                    <input type="checkbox" name="remember" value="1"> Remember me
                </label>
            </div>

            <button type="submit" class="btn btn-primary btn-lg" style="width:100%;justify-content:center;">
                🔐 Sign In
            </button>
        </form>

        <div style="text-align:center;margin-top:20px;font-size:13px;color:var(--text-muted);">
            Made by <strong>@PrimeTheOfficial</strong> with ❤️
        </div>

        <div style="text-align:center;margin-top:12px;font-size:12px;color:var(--text-muted);">
            <em>Default: admin / password</em>
        </div>
    </div>
</div>
<script src="<?= BASE_URL ?>assets/js/app.js"></script>
<script>
function togglePwd() {
    const f = document.getElementById('password');
    f.type = f.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>
