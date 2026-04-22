<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Already logged in → go to dashboard
if (isLoggedIn()) {
    header('Location: ' . getBaseUrl() . '/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $users = loadData('users');

        // Auto-create default admin if users.json is empty
        if (empty($users)) {
            $users = [[
                'id'         => '1',
                'name'       => 'Super Admin',
                'username'   => 'admin',
                'password'   => password_hash('admin123', PASSWORD_DEFAULT),
                'role'       => 'super_admin',
                'email'      => 'admin@amritpublic.edu',
                'created_at' => date('Y-m-d')
            ]];
            saveData('users', $users);
        }

        $found = null;
        foreach ($users as $u) {
            if ($u['username'] === $username && password_verify($password, $u['password'])) {
                $found = $u;
                break;
            }
        }

        if ($found) {
            session_regenerate_id(true);
            $_SESSION['user_id']    = $found['id'];
            $_SESSION['name']       = $found['name'];
            $_SESSION['role']       = $found['role'];
            $_SESSION['email']      = $found['email'];
            $_SESSION['username']   = $found['username'];
            if (isset($found['student_id'])) $_SESSION['student_id'] = $found['student_id'];
            if (isset($found['teacher_id'])) $_SESSION['teacher_id'] = $found['teacher_id'];
            header('Location: ' . getBaseUrl() . '/dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}

$settings = getSettings();
$baseUrl  = getBaseUrl();
$errorFromUrl = isset($_GET['error']) ? sanitize($_GET['error']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($settings['school_name']); ?> - Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/style.css">
    <style>
        :root { --theme-color: <?php echo htmlspecialchars($settings['theme_color']); ?>; }
        .login-wrapper { background: linear-gradient(135deg, var(--theme-color) 0%, #1a1a2e 100%); }
        .btn-login { background-color: var(--theme-color); border-color: var(--theme-color); }
        .btn-login:hover { filter: brightness(0.88); background-color: var(--theme-color); }
    </style>
</head>
<body>
<div class="login-wrapper">
    <div class="container">
        <div class="row justify-content-center align-items-center" style="min-height:100vh">
            <div class="col-md-5 col-lg-4">
                <div class="card login-card border-0">
                    <div class="card-body p-5">
                        <!-- School Header -->
                        <div class="text-center mb-4">
                            <?php if (!empty($settings['logo'])): ?>
                                <img src="<?php echo $baseUrl; ?>/uploads/logo/<?php echo htmlspecialchars(basename($settings['logo'])); ?>" height="70" alt="Logo" class="mb-3 rounded">
                            <?php else: ?>
                                <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle text-white fw-bold" style="width:70px;height:70px;font-size:2rem;background-color:var(--theme-color);">
                                    <?php echo strtoupper(substr($settings['school_name'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                            <h4 class="fw-bold mb-1"><?php echo htmlspecialchars($settings['school_name']); ?></h4>
                            <p class="text-muted small"><?php echo htmlspecialchars($settings['tagline']); ?></p>
                            <hr>
                            <h5 class="fw-semibold">Sign In</h5>
                            <p class="text-muted small">Enter your credentials to access the portal</p>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        <?php if ($errorFromUrl === 'unauthorized'): ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-shield-exclamation me-2"></i>You are not authorized to access that page.
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control" name="username" placeholder="Enter username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required autofocus>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" name="password" placeholder="Enter password" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-login text-white w-100 fw-semibold py-2">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                            </button>
                        </form>

                        <div class="mt-4 p-3 bg-light rounded">
                            <p class="small text-muted mb-2 fw-bold">Demo Credentials:</p>
                            <div class="small text-muted">
                                <div><strong>Admin:</strong> admin / admin123</div>
                                <div><strong>Teacher:</strong> teacher1 / teacher123</div>
                                <div><strong>Student:</strong> student1 / student123</div>
                                <div><strong>Parent:</strong> parent1 / parent123</div>
                                <div><strong>Accountant:</strong> accountant1 / accountant123</div>
                            </div>
                        </div>
                    </div>
                </div>
                <p class="text-center text-white-50 mt-3 small">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($settings['school_name']); ?></p>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo $baseUrl; ?>/assets/js/main.js"></script>
</body>
</html>
