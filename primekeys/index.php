<?php
require_once __DIR__ . '/includes/auth.php';
primeBootstrapData();

if (primeIsLoggedIn()) {
    header('Location: ' . primeGetBaseUrl() . '/dashboard.php');
    exit;
}

$settings = primeGetSettings();
$baseUrl = primeGetBaseUrl();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = primeRaw($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $user = primeFindUserByUsername($username);

    if (!$user || !password_verify($password, $user['password'] ?? '')) {
        $error = 'Invalid username or password.';
    } elseif (($user['status'] ?? 'active') !== 'active') {
        $error = 'This account is suspended. Contact the owner.';
    } else {
        primeLoginUser($user);
        header('Location: ' . $baseUrl . '/dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($settings['brand_name']); ?> - Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/style.css">
    <style>
        :root { --theme-color: <?php echo htmlspecialchars($settings['theme_color']); ?>; }
        .btn-login { background-color: var(--theme-color); border-color: var(--theme-color); }
    </style>
</head>
<body>
<div class="login-wrapper">
    <div class="container">
        <div class="row justify-content-center align-items-center" style="min-height:100vh">
            <div class="col-md-5 col-lg-4">
                <div class="card login-card border-0">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle text-white fw-bold" style="width:78px;height:78px;font-size:2rem;background-color:var(--theme-color);">
                                <?php echo strtoupper(substr($settings['brand_name'], 0, 1)); ?>
                            </div>
                            <h4 class="fw-bold mb-1"><?php echo htmlspecialchars($settings['brand_name']); ?></h4>
                            <p class="text-muted small mb-1"><?php echo htmlspecialchars($settings['brand_tagline']); ?></p>
                            <span class="badge bg-<?php echo !empty($settings['maintenance_mode']) ? 'danger' : 'success'; ?>">
                                <?php echo !empty($settings['maintenance_mode']) ? 'Maintenance On' : 'System Online'; ?>
                            </span>
                        </div>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control" name="username" required autofocus>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" name="password" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-login btn-primary text-white w-100 fw-semibold py-2">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                            </button>
                        </form>
                        <div class="mt-4 p-3 bg-light rounded">
                            <div class="small text-muted fw-bold mb-2">Demo Credentials</div>
                            <div class="small text-muted"><strong>Owner:</strong> owner / owner123</div>
                            <div class="small text-muted"><strong>Reseller:</strong> reseller / reseller123</div>
                        </div>
                    </div>
                </div>
                <p class="text-center text-white-50 mt-3 small mb-0">Brand, pricing, maintenance, referrals, and feature toggles are controlled from the owner panel.</p>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
