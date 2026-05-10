<?php
$settings = hostingGetSettings();
$baseUrl = hostingGetBaseUrl();
$user = hostingCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($settings['brand_name']); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/style.css">
    <style>:root{--theme-color:<?php echo htmlspecialchars($settings['theme_color']); ?>;}</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark navbar-theme">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?php echo $baseUrl; ?>/index.php">
            <i class="bi bi-cloud-lightning-rain-fill me-2"></i><?php echo htmlspecialchars($settings['brand_name']); ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="<?php echo $baseUrl; ?>/index.php#plans">Plans</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo $baseUrl; ?>/index.php#features">Features</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo $baseUrl; ?>/index.php#support">Support</a></li>
            </ul>
            <ul class="navbar-nav ms-auto">
                <?php if (hostingIsLoggedIn()): ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $baseUrl; ?>/dashboard.php"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $baseUrl; ?>/logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $baseUrl; ?>/login.php">Login</a></li>
                    <li class="nav-item"><a class="btn btn-sm btn-light ms-lg-2" href="<?php echo $baseUrl; ?>/register.php">Create Account</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<main class="container my-4">
    <?php echo hostingRenderFlash(); ?>

