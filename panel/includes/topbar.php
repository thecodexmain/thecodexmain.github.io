<?php
/**
 * Top Navigation Bar Template
 * @var string $pageTitle
 * @var array $currentUser
 * @var int $unreadNotifs
 */
$settings = getSettings();
?>
<nav class="topbar">
    <div class="topbar-left">
        <button class="btn-icon" id="sidebarCollapseBtn" aria-label="Toggle sidebar">
            <i class="fas fa-bars"></i>
        </button>
        <h1 class="page-title"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></h1>
    </div>
    <div class="topbar-right">
        <!-- Dark mode toggle -->
        <button class="btn-icon theme-toggle" id="themeToggle" aria-label="Toggle dark mode" title="Toggle dark mode">
            <i class="fas fa-moon" id="themeIcon"></i>
        </button>
        
        <!-- Notifications -->
        <div class="dropdown">
            <button class="btn-icon notif-btn" id="notifBtn" aria-label="Notifications">
                <i class="fas fa-bell"></i>
                <?php if ($unreadNotifs > 0): ?>
                <span class="badge-dot"><?= $unreadNotifs > 99 ? '99+' : $unreadNotifs ?></span>
                <?php endif; ?>
            </button>
            <div class="dropdown-menu notif-dropdown" id="notifMenu">
                <div class="dropdown-header">
                    <span>Notifications</span>
                    <?php if ($unreadNotifs > 0): ?>
                    <a href="<?= '/panel/' . ($currentUser['role'] ?? 'user') ?>/notifications.php" class="mark-read-btn">Mark all read</a>
                    <?php endif; ?>
                </div>
                <?php
                $notifs = array_slice(getNotifications($currentUser['id']), 0, 5);
                if (empty($notifs)): ?>
                <div class="notif-empty"><i class="fas fa-check-circle"></i> You're all caught up!</div>
                <?php else: foreach ($notifs as $n): ?>
                <div class="notif-item <?= empty($n['read_by'][$currentUser['id']]) ? 'unread' : '' ?>">
                    <div class="notif-icon">
                        <i class="fas fa-<?= $n['icon'] ?? 'info-circle' ?>"></i>
                    </div>
                    <div class="notif-content">
                        <div class="notif-title"><?= htmlspecialchars($n['title'] ?? '') ?></div>
                        <div class="notif-time"><?= timeAgo($n['created_at']) ?></div>
                    </div>
                </div>
                <?php endforeach; endif; ?>
                <div class="dropdown-footer">
                    <a href="/panel/<?= $currentUser['role'] ?? 'user' ?>/notifications.php">View all</a>
                </div>
            </div>
        </div>
        
        <!-- User menu -->
        <div class="dropdown">
            <button class="btn-avatar" id="userMenuBtn">
                <?php if (!empty($currentUser['avatar'])): ?>
                <img src="/panel/<?= htmlspecialchars($currentUser['avatar']) ?>" alt="Avatar" class="avatar-img">
                <?php else: ?>
                <span class="avatar-letter"><?= strtoupper(substr($currentUser['username'], 0, 1)) ?></span>
                <?php endif; ?>
                <span class="user-name-topbar"><?= htmlspecialchars($currentUser['username']) ?></span>
                <i class="fas fa-chevron-down"></i>
            </button>
            <div class="dropdown-menu user-menu" id="userMenu">
                <a href="/panel/<?= $currentUser['role'] ?? 'user' ?>/profile.php"><i class="fas fa-user"></i> Profile</a>
                <?php if ($currentUser['role'] === 'admin'): ?>
                <a href="/panel/admin/settings.php"><i class="fas fa-cog"></i> Settings</a>
                <?php endif; ?>
                <div class="dropdown-divider"></div>
                <a href="/panel/logout.php" class="text-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>
</nav>
