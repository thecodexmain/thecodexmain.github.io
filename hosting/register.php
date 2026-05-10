<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/mailer.php';

if (hostingIsLoggedIn()) {
    header('Location: ' . hostingGetBaseUrl() . '/dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    hostingVerifyCsrf();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $password2 = (string)($_POST['password2'] ?? '');

    if ($name === '' || $email === '' || $username === '' || $password === '') {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $password2) {
        $error = 'Passwords do not match.';
    } else {
        $users = hostingLoadData('users');
        foreach ($users as $u) {
            if (($u['username'] ?? '') === $username) $error = 'Username already exists.';
            if (($u['email'] ?? '') === $email) $error = 'Email already exists.';
            if ($error) break;
        }
        if (!$error) {
            $users[] = [
                'id' => 'U-' . hostingGenerateId(),
                'name' => $name,
                'email' => $email,
                'username' => $username,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'role' => 'user',
                'status' => 'active',
                'created_at' => date('Y-m-d')
            ];
            hostingSaveData('users', $users);
            hostingQueueMail($email, 'Welcome to ' . (hostingGetSettings()['brand_name'] ?? 'CodexHost'), "Hi {$name},\n\nYour account is ready. You can now request a hosting service from your dashboard.\n\nLogin: {$username}\n\nThanks,\n" . (hostingGetSettings()['brand_name'] ?? 'CodexHost'));
            hostingDispatchMailQueue(3);
            hostingSetFlash('success', 'Account created. Please sign in.');
            header('Location: ' . hostingGetBaseUrl() . '/login.php');
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
    <title><?php echo htmlspecialchars($settings['brand_name']); ?> - Register</title>
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
        <div class="col-md-7 col-lg-5">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="text-center mb-3">
                        <h1 class="h5 fw-bold mb-1">Create account</h1>
                        <div class="text-muted small">Start requesting services and opening tickets.</div>
                    </div>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <form method="post" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(hostingCsrfToken()); ?>">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold">Full name</label>
                                <input class="form-control" name="name" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Email</label>
                                <input class="form-control" type="email" name="email" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Username</label>
                                <input class="form-control" name="username" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Password</label>
                                <input class="form-control" type="password" name="password" autocomplete="new-password" required>
                                <div class="form-text">Min 8 characters.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Confirm</label>
                                <input class="form-control" type="password" name="password2" autocomplete="new-password" required>
                            </div>
                        </div>
                        <button class="btn btn-primary w-100 mt-3" type="submit"><i class="bi bi-person-check me-1"></i>Create account</button>
                    </form>
                    <div class="text-center mt-3 small">
                        Already have an account? <a href="<?php echo $baseUrl; ?>/login.php" class="fw-semibold">Sign in</a>
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
