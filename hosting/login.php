<?php
require_once __DIR__ . '/includes/auth.php';

if (hostingIsLoggedIn()) {
    header('Location: ' . hostingGetBaseUrl() . '/dashboard.php');
    exit;
}

$users = hostingLoadData('users');
if (empty($users)) {
    $users = [[
        'id' => 'U-1',
        'name' => 'Admin',
        'email' => 'admin@example.com',
        'username' => 'admin',
        'password' => password_hash('admin123', PASSWORD_DEFAULT),
        'role' => 'admin',
        'status' => 'active',
        'created_at' => date('Y-m-d')
    ]];
    hostingSaveData('users', $users);
    hostingSetFlash('warning', 'Default admin created: admin / admin123 (change after login).');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    hostingVerifyCsrf();
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        $found = null;
        foreach ($users as $u) {
            if (($u['username'] ?? '') === $username && password_verify($password, $u['password'] ?? '')) {
                $found = $u;
                break;
            }
        }
        if (!$found) {
            $error = 'Invalid username or password.';
        } elseif (($found['status'] ?? 'active') !== 'active') {
            $error = 'Your account is disabled. Contact support.';
        } else {
            session_regenerate_id(true);
            $_SESSION['hosting_user_id'] = $found['id'];
            $_SESSION['hosting_name'] = $found['name'];
            $_SESSION['hosting_email'] = $found['email'];
            $_SESSION['hosting_role'] = $found['role'] ?? 'user';
            $_SESSION['hosting_username'] = $found['username'] ?? '';
            header('Location: ' . hostingGetBaseUrl() . '/dashboard.php');
            exit;
        }
    }
}

$settings = hostingGetSettings();
$baseUrl = hostingGetBaseUrl();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($settings['brand_name']); ?> - Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/style.css">
    <style>
        :root { --theme-color: <?php echo htmlspecialchars($settings['theme_color']); ?>; }
        .auth-bg { background: linear-gradient(135deg, var(--theme-color) 0%, #111827 100%); min-height: 100vh; }
    </style>
</head>
<body class="auth-bg d-flex align-items-center">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="text-center mb-3">
                        <div class="mx-auto mb-2 d-flex align-items-center justify-content-center rounded-circle text-white fw-bold" style="width:64px;height:64px;font-size:1.75rem;background-color:var(--theme-color);">
                            <?php echo strtoupper(substr($settings['brand_name'], 0, 1)); ?>
                        </div>
                        <h1 class="h5 fw-bold mb-1">Sign in</h1>
                        <div class="text-muted small"><?php echo htmlspecialchars($settings['tagline']); ?></div>
                    </div>
                    <?php echo hostingRenderFlash(); ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <form method="post" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(hostingCsrfToken()); ?>">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Username</label>
                            <input class="form-control" name="username" autocomplete="username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Password</label>
                            <input class="form-control" type="password" name="password" autocomplete="current-password" required>
                        </div>
                        <button class="btn btn-primary w-100" type="submit"><i class="bi bi-box-arrow-in-right me-1"></i>Login</button>
                    </form>
                    <div class="text-center mt-3 small">
                        Don’t have an account? <a href="<?php echo $baseUrl; ?>/register.php" class="fw-semibold">Create one</a>
                    </div>
                    <div class="text-center mt-2 small">
                        <a href="<?php echo $baseUrl; ?>/index.php" class="text-muted">Back to site</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

