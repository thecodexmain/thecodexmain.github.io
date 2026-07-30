<?php
function renderHead(string $title = '', bool $includeFM = false): void {
    $siteName = getSetting('site_name', 'Prime Webs');
    $themeColor = getSetting('theme_color', '#6366f1');
    $favicon = getSetting('site_favicon', '');
    $metaTitle = $title ? "{$title} - {$siteName}" : getSetting('meta_title', $siteName);
    $metaDesc = getSetting('meta_description', 'Professional web hosting panel');
    ?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($metaTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDesc) ?>">
    <meta name="theme-color" content="<?= htmlspecialchars($themeColor) ?>">
    <?php if ($favicon): ?><link rel="icon" href="<?= htmlspecialchars($favicon) ?>"><?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <script>
      // Apply saved theme before render to prevent flash
      (function(){var t=localStorage.getItem('pw_theme')||'light';document.documentElement.setAttribute('data-theme',t);})();
    </script>
    <style>:root{--primary:<?= htmlspecialchars($themeColor) ?>;--primary-dark:<?= adjustColor($themeColor, -20) ?>;}</style>
</head>
<?php
}

function adjustColor(string $hex, int $amount): string {
    $hex = ltrim($hex, '#');
    if (strlen($hex) !== 6) return '#4f46e5';
    $r = max(0, min(255, hexdec(substr($hex, 0, 2)) + $amount));
    $g = max(0, min(255, hexdec(substr($hex, 2, 2)) + $amount));
    $b = max(0, min(255, hexdec(substr($hex, 4, 2)) + $amount));
    return sprintf('#%02x%02x%02x', $r, $g, $b);
}

function renderSidebar(string $role, string $active = ''): void {
    $siteName = getSetting('site_name', 'Prime Webs');
    $siteLogo = getSetting('site_logo', '');
    $username = $_SESSION['username'] ?? 'User';

    $adminNav = [
        ['icon' => '⊞', 'label' => 'Dashboard', 'url' => BASE_URL . 'admin/', 'key' => 'dashboard'],
        ['icon' => '👥', 'label' => 'Users', 'url' => BASE_URL . 'admin/users.php', 'key' => 'users'],
        ['icon' => '🤝', 'label' => 'Resellers', 'url' => BASE_URL . 'admin/resellers.php', 'key' => 'resellers'],
        ['icon' => '📦', 'label' => 'Plans', 'url' => BASE_URL . 'admin/plans.php', 'key' => 'plans'],
        ['icon' => '🚀', 'label' => 'Scripts', 'url' => BASE_URL . 'admin/scripts.php', 'key' => 'scripts'],
        ['icon' => '🛒', 'label' => 'Script Store', 'url' => BASE_URL . 'admin/script_store.php', 'key' => 'script_store'],
        ['icon' => '🔗', 'label' => 'Short Links', 'url' => BASE_URL . 'admin/links.php', 'key' => 'links'],
        ['icon' => '🎫', 'label' => 'Tickets', 'url' => BASE_URL . 'admin/tickets.php', 'key' => 'tickets'],
        ['icon' => '🔔', 'label' => 'Notifications', 'url' => BASE_URL . 'admin/notifications.php', 'key' => 'notifications'],
        ['icon' => '💾', 'label' => 'Backups', 'url' => BASE_URL . 'admin/backup.php', 'key' => 'backup'],
        ['icon' => '🔑', 'label' => 'API Keys', 'url' => BASE_URL . 'admin/api_keys.php', 'key' => 'api_keys'],
        ['icon' => '📋', 'label' => 'Logs', 'url' => BASE_URL . 'admin/logs.php', 'key' => 'logs'],
        ['icon' => '🛠️', 'label' => 'Maintenance', 'url' => BASE_URL . 'admin/maintenance.php', 'key' => 'maintenance'],
        ['icon' => '⚙️', 'label' => 'Settings', 'url' => BASE_URL . 'admin/settings.php', 'key' => 'settings'],
    ];

    $resellerNav = [
        ['icon' => '⊞', 'label' => 'Dashboard', 'url' => BASE_URL . 'reseller/', 'key' => 'dashboard'],
        ['icon' => '👥', 'label' => 'My Users', 'url' => BASE_URL . 'reseller/users.php', 'key' => 'users'],
        ['icon' => '🎫', 'label' => 'Tickets', 'url' => BASE_URL . 'reseller/tickets.php', 'key' => 'tickets'],
        ['icon' => '💰', 'label' => 'Wallet', 'url' => BASE_URL . 'reseller/wallet.php', 'key' => 'wallet'],
    ];

    $userNav = [
        ['icon' => '⊞', 'label' => 'Dashboard', 'url' => BASE_URL . 'user/', 'key' => 'dashboard'],
        ['icon' => '🌐', 'label' => 'My Sites', 'url' => BASE_URL . 'user/sites.php', 'key' => 'sites'],
        ['icon' => '📁', 'label' => 'File Manager', 'url' => BASE_URL . 'user/filemanager.php', 'key' => 'filemanager'],
        ['icon' => '🔗', 'label' => 'Short Links', 'url' => BASE_URL . 'user/links.php', 'key' => 'links'],
        ['icon' => '🌍', 'label' => 'Domains', 'url' => BASE_URL . 'user/domains.php', 'key' => 'domains'],
        ['icon' => '💾', 'label' => 'Backups', 'url' => BASE_URL . 'user/backup.php', 'key' => 'backup'],
        ['icon' => '🎫', 'label' => 'Support', 'url' => BASE_URL . 'user/tickets.php', 'key' => 'tickets'],
    ];

    $navItems = match($role) {
        'admin' => $adminNav,
        'reseller' => $resellerNav,
        default => $userNav,
    };

    $roleLabel = match($role) {
        'admin' => '👑 Admin',
        'reseller' => '🤝 Reseller',
        default => '👤 User',
    };
    ?>
<body>
<div class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <?php if ($siteLogo): ?>
            <img src="<?= htmlspecialchars($siteLogo) ?>" alt="Logo">
        <?php else: ?>
            <div style="width:36px;height:36px;background:var(--primary);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:800;color:#fff;">P</div>
        <?php endif; ?>
        <span><?= htmlspecialchars($siteName) ?></span>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section"><?= $roleLabel ?></div>
        <?php foreach ($navItems as $item): ?>
        <a href="<?= $item['url'] ?>" class="nav-item <?= $active === $item['key'] ? 'active' : '' ?>">
            <span><?= $item['icon'] ?></span>
            <span><?= htmlspecialchars($item['label']) ?></span>
        </a>
        <?php endforeach; ?>
    </nav>
    <div class="sidebar-footer">
        <div style="margin-bottom:4px;font-weight:600;color:rgba(255,255,255,0.7);"><?= htmlspecialchars($username) ?></div>
        <a href="<?= BASE_URL ?>logout.php" style="color:rgba(255,255,255,0.4);font-size:12px;">🚪 Sign Out</a>
    </div>
</div>
<div class="main-wrapper" id="mainWrapper">
<?php
}

function renderTopbar(string $pageTitle): void {
    $userId = $_SESSION['user_id'] ?? '';
    $username = $_SESSION['username'] ?? 'User';
    $role = $_SESSION['user_role'] ?? 'user';
    $unread = getUnreadNotificationCount($userId);

    $notifications = readJson(DATA_PATH . 'notifications.json');
    $userNotifs = array_filter($notifications, fn($n) => $n['user_id'] === $userId);
    $userNotifs = array_slice(array_reverse(array_values($userNotifs)), 0, 5);
    ?>
    <header class="topbar">
        <div class="topbar-left">
            <button class="topbar-btn sidebar-toggle" id="sidebarToggle" title="Menu">☰</button>
            <h1 class="page-title"><?= htmlspecialchars($pageTitle) ?></h1>
        </div>
        <div class="topbar-right">
            <button class="topbar-btn" id="themeToggle" title="Toggle Theme">🌙</button>

            <div class="dropdown">
                <button class="topbar-btn" id="notifBtn" title="Notifications">
                    🔔
                    <?php if ($unread > 0): ?>
                    <span class="badge"><?= min($unread, 99) ?></span>
                    <?php endif; ?>
                </button>
                <div class="dropdown-menu" id="notifMenu">
                    <div style="padding:12px 16px;font-weight:600;border-bottom:1px solid var(--border);font-size:13px;">
                        Notifications <?php if ($unread > 0): ?><span class="badge-status badge-open" style="margin-left:8px;"><?= $unread ?> new</span><?php endif; ?>
                    </div>
                    <?php if (empty($userNotifs)): ?>
                    <div style="padding:20px;text-align:center;color:var(--text-muted);font-size:13px;">No notifications</div>
                    <?php else: foreach ($userNotifs as $notif): ?>
                    <div class="dropdown-item" onclick="markNotifRead('<?= $notif['id'] ?>')">
                        <div class="dropdown-item-icon">
                            <?= $notif['type'] === 'success' ? '✅' : ($notif['type'] === 'danger' ? '❌' : ($notif['type'] === 'warning' ? '⚠️' : 'ℹ️')) ?>
                        </div>
                        <div>
                            <div style="font-size:13px;font-weight:<?= $notif['read'] ? '400' : '600' ?>;"><?= htmlspecialchars($notif['title']) ?></div>
                            <div style="font-size:12px;color:var(--text-muted);margin-top:2px;"><?= htmlspecialchars(substr($notif['message'], 0, 60)) ?></div>
                            <div style="font-size:11px;color:var(--text-muted);margin-top:2px;"><?= timeAgo($notif['created_at']) ?></div>
                        </div>
                    </div>
                    <?php endforeach; endif; ?>
                    <div style="padding:10px 16px;text-align:center;border-top:1px solid var(--border);">
                        <a href="<?= BASE_URL . $role ?>/notifications.php" style="font-size:13px;">View all</a>
                    </div>
                </div>
            </div>

            <div class="dropdown">
                <div class="user-avatar" id="userDropBtn" title="Account"><?= strtoupper(substr($username, 0, 1)) ?></div>
                <div class="dropdown-menu" id="userDropMenu" style="min-width:180px;">
                    <div style="padding:12px 16px;border-bottom:1px solid var(--border);">
                        <div style="font-weight:600;font-size:14px;"><?= htmlspecialchars($username) ?></div>
                        <div style="font-size:12px;color:var(--text-muted);text-transform:capitalize;"><?= $role ?></div>
                    </div>
                    <a href="<?= BASE_URL . $role ?>/" class="dropdown-item" style="text-decoration:none;">⊞ Dashboard</a>
                    <a href="<?= BASE_URL ?>logout.php" class="dropdown-item" style="text-decoration:none;color:var(--danger);">🚪 Sign Out</a>
                </div>
            </div>
        </div>
    </header>
    <main class="content">
    <?php
}

function renderFooter(): void {
    $footerText = getSetting('footer_text', 'Prime Webs - Made by @PrimeTheOfficial with love');
    ?>
    </main>
    <footer style="padding:16px 24px;border-top:1px solid var(--border);font-size:12px;color:var(--text-muted);background:var(--surface);text-align:center;">
        <?= htmlspecialchars($footerText) ?>
    </footer>
</div>
<script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/app.js"></script>
<script>
function markNotifRead(id) {
    fetch('<?= BASE_URL ?>api.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'mark_notif_read', id: id, csrf: '<?= generateCSRF() ?>'})
    }).then(()=>{window.location.reload();});
}
</script>
</body>
</html>
<?php
}

function renderAlert(string $type, string $message): void {
    $icons = ['success' => '✅', 'danger' => '❌', 'warning' => '⚠️', 'info' => 'ℹ️'];
    $icon = $icons[$type] ?? 'ℹ️';
    echo "<div class='alert alert-{$type}'><span>{$icon}</span><span>" . htmlspecialchars($message) . "</span><button class='alert-dismiss' style='margin-left:auto;background:none;border:none;cursor:pointer;font-size:16px;'>✕</button></div>";
}

function csrfField(): void {
    echo '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . generateCSRF() . '">';
}
