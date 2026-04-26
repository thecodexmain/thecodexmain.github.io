<?php
/**
 * Sidebar Navigation Template
 * @var string $activePage
 * @var array $currentUser
 */
$settings = getSettings();
$role = $currentUser['role'] ?? 'user';
$siteName = $settings['site_name'] ?? 'Amrit Web Panel';
$logo = $settings['logo'] ?? 'assets/img/logo.png';
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="/panel/<?= $role ?>/index.php" class="sidebar-brand">
            <img src="/panel/<?= htmlspecialchars($logo) ?>" alt="<?= htmlspecialchars($siteName) ?>" class="brand-logo" onerror="this.style.display='none';this.nextSibling.style.display='block'">
            <span class="brand-text"><?= htmlspecialchars($siteName) ?></span>
        </a>
        <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    
    <div class="sidebar-user">
        <div class="user-avatar">
            <?php if (!empty($currentUser['avatar'])): ?>
            <img src="/panel/<?= htmlspecialchars($currentUser['avatar']) ?>" alt="Avatar">
            <?php else: ?>
            <span><?= strtoupper(substr($currentUser['username'], 0, 1)) ?></span>
            <?php endif; ?>
        </div>
        <div class="user-info">
            <div class="user-name"><?= htmlspecialchars($currentUser['username']) ?></div>
            <div class="user-role badge badge-<?= $role ?>"><?= ucfirst($role) ?></div>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <?php if ($role === 'admin'): ?>
        <div class="nav-section">Main</div>
        <a href="/panel/admin/index.php" class="nav-item <?= ($activePage ?? '') === 'dashboard' ? 'active' : '' ?>">
            <i class="fas fa-home"></i><span>Dashboard</span>
        </a>
        <div class="nav-section">Management</div>
        <a href="/panel/admin/users.php" class="nav-item <?= ($activePage ?? '') === 'users' ? 'active' : '' ?>">
            <i class="fas fa-users"></i><span>Users</span>
        </a>
        <a href="/panel/admin/resellers.php" class="nav-item <?= ($activePage ?? '') === 'resellers' ? 'active' : '' ?>">
            <i class="fas fa-handshake"></i><span>Resellers</span>
        </a>
        <a href="/panel/admin/plans.php" class="nav-item <?= ($activePage ?? '') === 'plans' ? 'active' : '' ?>">
            <i class="fas fa-tags"></i><span>Plans & Pricing</span>
        </a>
        <a href="/panel/admin/scripts.php" class="nav-item <?= ($activePage ?? '') === 'scripts' ? 'active' : '' ?>">
            <i class="fas fa-code"></i><span>Scripts</span>
        </a>
        <a href="/panel/admin/sites.php" class="nav-item <?= ($activePage ?? '') === 'sites' ? 'active' : '' ?>">
            <i class="fas fa-globe"></i><span>All Sites</span>
        </a>
        <div class="nav-section">Tools</div>
        <a href="/panel/admin/tickets.php" class="nav-item <?= ($activePage ?? '') === 'tickets' ? 'active' : '' ?>">
            <i class="fas fa-ticket-alt"></i><span>Support Tickets</span>
        </a>
        <a href="/panel/admin/notifications.php" class="nav-item <?= ($activePage ?? '') === 'notifications' ? 'active' : '' ?>">
            <i class="fas fa-bell"></i><span>Notifications</span>
        </a>
        <a href="/panel/admin/backup.php" class="nav-item <?= ($activePage ?? '') === 'backup' ? 'active' : '' ?>">
            <i class="fas fa-database"></i><span>Backups</span>
        </a>
        <a href="/panel/admin/logs.php" class="nav-item <?= ($activePage ?? '') === 'logs' ? 'active' : '' ?>">
            <i class="fas fa-list-alt"></i><span>Logs</span>
        </a>
        <div class="nav-section">Config</div>
        <a href="/panel/admin/settings.php" class="nav-item <?= ($activePage ?? '') === 'settings' ? 'active' : '' ?>">
            <i class="fas fa-cog"></i><span>Settings</span>
        </a>
        <a href="/panel/admin/maintenance.php" class="nav-item <?= ($activePage ?? '') === 'maintenance' ? 'active' : '' ?>">
            <i class="fas fa-tools"></i><span>Maintenance</span>
        </a>
        
        <?php elseif ($role === 'reseller'): ?>
        <div class="nav-section">Main</div>
        <a href="/panel/reseller/index.php" class="nav-item <?= ($activePage ?? '') === 'dashboard' ? 'active' : '' ?>">
            <i class="fas fa-home"></i><span>Dashboard</span>
        </a>
        <div class="nav-section">Management</div>
        <a href="/panel/reseller/users.php" class="nav-item <?= ($activePage ?? '') === 'users' ? 'active' : '' ?>">
            <i class="fas fa-users"></i><span>My Users</span>
        </a>
        <a href="/panel/reseller/wallet.php" class="nav-item <?= ($activePage ?? '') === 'wallet' ? 'active' : '' ?>">
            <i class="fas fa-wallet"></i><span>Wallet</span>
        </a>
        <a href="/panel/reseller/tickets.php" class="nav-item <?= ($activePage ?? '') === 'tickets' ? 'active' : '' ?>">
            <i class="fas fa-ticket-alt"></i><span>Tickets</span>
        </a>
        <div class="nav-section">Account</div>
        <a href="/panel/reseller/profile.php" class="nav-item <?= ($activePage ?? '') === 'profile' ? 'active' : '' ?>">
            <i class="fas fa-user"></i><span>Profile</span>
        </a>
        
        <?php else: // User ?>
        <div class="nav-section">Main</div>
        <a href="/panel/user/index.php" class="nav-item <?= ($activePage ?? '') === 'dashboard' ? 'active' : '' ?>">
            <i class="fas fa-home"></i><span>Dashboard</span>
        </a>
        <div class="nav-section">Sites</div>
        <a href="/panel/user/sites.php" class="nav-item <?= ($activePage ?? '') === 'sites' ? 'active' : '' ?>">
            <i class="fas fa-globe"></i><span>My Sites</span>
        </a>
        <a href="/panel/user/store.php" class="nav-item <?= ($activePage ?? '') === 'store' ? 'active' : '' ?>">
            <i class="fas fa-store"></i><span>Script Store</span>
        </a>
        <a href="/panel/user/files.php" class="nav-item <?= ($activePage ?? '') === 'files' ? 'active' : '' ?>">
            <i class="fas fa-folder-open"></i><span>File Manager</span>
        </a>
        <a href="/panel/user/domains.php" class="nav-item <?= ($activePage ?? '') === 'domains' ? 'active' : '' ?>">
            <i class="fas fa-link"></i><span>Domains</span>
        </a>
        <div class="nav-section">Tools</div>
        <a href="/panel/user/links.php" class="nav-item <?= ($activePage ?? '') === 'links' ? 'active' : '' ?>">
            <i class="fas fa-compress-alt"></i><span>Short Links</span>
        </a>
        <a href="/panel/user/tickets.php" class="nav-item <?= ($activePage ?? '') === 'tickets' ? 'active' : '' ?>">
            <i class="fas fa-ticket-alt"></i><span>Support</span>
        </a>
        <a href="/panel/user/api.php" class="nav-item <?= ($activePage ?? '') === 'api' ? 'active' : '' ?>">
            <i class="fas fa-key"></i><span>API Keys</span>
        </a>
        <div class="nav-section">Account</div>
        <a href="/panel/user/profile.php" class="nav-item <?= ($activePage ?? '') === 'profile' ? 'active' : '' ?>">
            <i class="fas fa-user"></i><span>Profile</span>
        </a>
        <?php endif; ?>
    </nav>
    
    <div class="sidebar-footer">
        <a href="/panel/logout.php" class="nav-item logout-link">
            <i class="fas fa-sign-out-alt"></i><span>Logout</span>
        </a>
    </div>
</aside>
