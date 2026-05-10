<?php
require_once __DIR__ . '/auth.php';
$settings = primeGetSettings();
$baseUrl = primeGetBaseUrl();
$currentUser = primeGetCurrentUser();
$pageTitle = isset($pageTitle) ? $pageTitle . ' - ' . $settings['brand_name'] : $settings['brand_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/style.css">
    <style>
        :root { --theme-color: <?php echo htmlspecialchars($settings['theme_color']); ?>; }
        .navbar { background-color: var(--theme-color) !important; }
        .sidebar .nav-link.active, .sidebar .nav-link:hover { background-color: var(--theme-color) !important; }
        .btn-primary { background-color: var(--theme-color); border-color: var(--theme-color); }
        .btn-primary:hover { filter: brightness(.92); background-color: var(--theme-color); border-color: var(--theme-color); }
        .text-theme { color: var(--theme-color) !important; }
        .bg-theme { background-color: var(--theme-color) !important; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?php echo $baseUrl; ?>/dashboard.php">
            <div class="rounded bg-white d-flex align-items-center justify-content-center fw-bold" style="width:42px;height:42px;color:var(--theme-color);">
                <?php echo strtoupper(substr($settings['brand_name'], 0, 1)); ?>
            </div>
            <div>
                <div class="fw-bold" style="line-height:1.1"><?php echo htmlspecialchars($settings['brand_name']); ?></div>
                <small style="opacity:.85"><?php echo htmlspecialchars($settings['brand_tagline']); ?></small>
            </div>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#primeNav"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="primeNav">
            <ul class="navbar-nav ms-auto align-items-center gap-2">
                <li class="nav-item"><span class="nav-link text-white-50"><i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($currentUser['name']); ?></span></li>
                <li class="nav-item"><span class="badge bg-light text-dark"><?php echo ucfirst($currentUser['role'] ?? 'guest'); ?></span></li>
                <?php if (($currentUser['role'] ?? '') === 'reseller'): ?>
                    <li class="nav-item"><span class="badge badge-soft">Balance: ₹<?php echo number_format((float)($currentUser['balance'] ?? 0), 2); ?></span></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link btn btn-outline-light btn-sm px-3" href="<?php echo $baseUrl; ?>/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
            </ul>
        </div>
    </div>
</nav>
