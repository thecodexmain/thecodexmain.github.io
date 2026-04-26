<?php
/**
 * HTML Header Template
 * @var string $pageTitle
 * @var string $activePage
 */
$settings = getSettings();
$currentUser = getCurrentUser();
$unreadNotifs = 0;
if ($currentUser) {
    $notifs = getNotifications($currentUser['id']);
    $unreadNotifs = count(array_filter($notifs, fn($n) => empty($n['read_by'][$currentUser['id']])));
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? $settings['meta_title'] ?? 'Amrit Web Panel') ?></title>
    <meta name="description" content="<?= htmlspecialchars($settings['meta_description'] ?? '') ?>">
    <link rel="icon" href="/panel/assets/img/favicon.png" type="image/png">
    <link rel="manifest" href="/panel/manifest.json">
    <meta name="theme-color" content="<?= htmlspecialchars($settings['theme_color'] ?? '#4f46e5') ?>">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- Main CSS -->
    <link rel="stylesheet" href="/panel/assets/css/main.css">
    
    <style>
        :root {
            --primary: <?= htmlspecialchars($settings['theme_color'] ?? '#4f46e5') ?>;
            --primary-dark: color-mix(in srgb, <?= htmlspecialchars($settings['theme_color'] ?? '#4f46e5') ?> 80%, black);
            --primary-light: color-mix(in srgb, <?= htmlspecialchars($settings['theme_color'] ?? '#4f46e5') ?> 10%, white);
        }
    </style>
</head>
<body>
<?php if (!empty($settings['maintenance_mode']) && ($currentUser['role'] ?? '') !== 'admin'): ?>
<div class="maintenance-banner">
    <i class="fas fa-tools"></i>
    <?= htmlspecialchars($settings['maintenance_message'] ?? 'Maintenance in progress.') ?>
</div>
<?php endif; ?>
