<?php
require_once __DIR__ . '/auth.php';

function hostingLayoutStart(string $title): void {
    $base = hostingGetBaseUrl();
    $user = hostingGetCurrentUser();
    $isLoggedIn = hostingIsLoggedIn();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo hostingSanitize($title); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background:#f7f9fc; }
        .brand-gradient { background: linear-gradient(135deg,#0d6efd,#6610f2); }
        .hero { background: linear-gradient(135deg,#0d6efd,#6610f2); color:#fff; border-radius: 1rem; }
        .kpi-card { border: 0; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
        .table td,.table th { vertical-align: middle; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark brand-gradient">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?php echo $base; ?>/index.php"><i class="bi bi-hdd-network"></i> CodexHost</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="<?php echo $base; ?>/index.php">Home</a></li>
                <?php if ($isLoggedIn && ($user['role'] ?? '') === 'user'): ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $base; ?>/user/dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $base; ?>/user/my_services.php">My Services</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $base; ?>/user/buy_service.php">Buy New Service</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $base; ?>/user/tickets.php">Tickets</a></li>
                <?php endif; ?>
                <?php if ($isLoggedIn && ($user['role'] ?? '') === 'admin'): ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $base; ?>/admin/dashboard.php">Admin Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $base; ?>/admin/requests.php">Requests</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $base; ?>/admin/users.php">Users</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $base; ?>/admin/tickets.php">Tickets</a></li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav ms-auto">
                <?php if (!$isLoggedIn): ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $base; ?>/login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $base; ?>/register.php">Register</a></li>
                <?php else: ?>
                    <li class="nav-item"><span class="nav-link">Hi, <?php echo hostingSanitize($user['name']); ?></span></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $base; ?>/logout.php">Logout</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<main class="container py-4">
    <?php echo hostingRenderFlash(); ?>
    <?php
}

function hostingLayoutEnd(): void {
    ?>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
}
