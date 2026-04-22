<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
$settings   = getSettings();
$pageTitle  = isset($pageTitle) ? $pageTitle . ' - ' . $settings['school_name'] : $settings['school_name'];
$baseUrl    = getBaseUrl();
$currentUser = getCurrentUser();
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
        .btn-primary:hover { filter: brightness(0.88); background-color: var(--theme-color); border-color: var(--theme-color); }
        .text-theme { color: var(--theme-color) !important; }
        .bg-theme { background-color: var(--theme-color) !important; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?php echo $baseUrl; ?>/dashboard.php">
            <?php if (!empty($settings['logo'])): ?>
                <img src="<?php echo $baseUrl; ?>/uploads/logo/<?php echo htmlspecialchars(basename($settings['logo'])); ?>" height="40" alt="Logo" class="rounded">
            <?php else: ?>
                <div class="rounded bg-white text-primary d-flex align-items-center justify-content-center fw-bold" style="width:40px;height:40px;font-size:18px;">
                    <?php echo strtoupper(substr($settings['school_name'], 0, 1)); ?>
                </div>
            <?php endif; ?>
            <div>
                <div class="fw-bold" style="line-height:1.2"><?php echo htmlspecialchars($settings['school_name']); ?></div>
                <small style="font-size:0.7rem;opacity:0.8"><?php echo htmlspecialchars($settings['tagline']); ?></small>
            </div>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center gap-1">
                <li class="nav-item">
                    <span class="nav-link text-white-50"><i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($currentUser['name']); ?></span>
                </li>
                <li class="nav-item">
                    <span class="badge bg-light text-dark"><?php echo ucfirst(str_replace('_', ' ', $currentUser['role'])); ?></span>
                </li>
                <li class="nav-item ms-2">
                    <a class="nav-link btn btn-outline-light btn-sm px-3" href="<?php echo $baseUrl; ?>/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
