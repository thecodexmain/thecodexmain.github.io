<?php
require_once __DIR__ . '/includes/auth.php';
if (hostingIsLoggedIn()) {
    header('Location: ' . hostingGetBaseUrl() . '/dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = trim($_POST['password'] ?? '');
    $users = loadHostingData('users');
    $found = null;

    foreach ($users as $u) {
        if (strtolower($u['email']) === $email && password_verify($password, $u['password'])) {
            $found = $u;
            break;
        }
    }

    if (!$found) {
        hostingSetFlash('danger', 'Invalid email or password.');
    } else {
        session_regenerate_id(true);
        $_SESSION['hosting_user_id'] = $found['id'];
        $_SESSION['hosting_name'] = $found['name'];
        $_SESSION['hosting_email'] = $found['email'];
        $_SESSION['hosting_role'] = $found['role'];
        header('Location: ' . hostingGetBaseUrl() . '/dashboard.php');
        exit;
    }
}

require_once __DIR__ . '/includes/layout.php';
hostingLayoutStart('Login - CodexHost');
?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h3 class="fw-bold mb-3">Client Login</h3>
                <p class="text-muted small">Demo admin: <code>admin@codexhost.local</code> / <code>admin123</code></p>
                <form method="POST">
                    <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
                    <button class="btn btn-primary w-100">Login</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php hostingLayoutEnd(); ?>
