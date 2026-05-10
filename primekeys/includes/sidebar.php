<?php
$baseUrl = primeGetBaseUrl();
$currentUser = primeGetCurrentUser();
$self = basename($_SERVER['PHP_SELF'] ?? '');
$locked = primePortalLockedFor($currentUser);
?>
<div class="sidebar text-white">
    <nav class="pt-2">
        <ul class="nav flex-column">
            <li class="nav-item"><a class="nav-link text-white <?php echo $self === 'dashboard.php' ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>/dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
            <li class="nav-item"><a class="nav-link text-white <?php echo $self === 'keys.php' ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>/keys.php"><i class="bi bi-key"></i> Keys</a></li>
            <li class="nav-item"><a class="nav-link text-white <?php echo $self === 'generator.php' ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>/generator.php"><i class="bi bi-magic"></i> Generator</a></li>
            <li class="nav-item"><a class="nav-link text-white <?php echo $self === 'users.php' ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>/users.php"><i class="bi bi-people"></i> Users</a></li>
            <?php if (($currentUser['role'] ?? '') === 'owner'): ?>
                <li class="nav-item"><a class="nav-link text-white <?php echo $self === 'resellers.php' ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>/resellers.php"><i class="bi bi-person-badge"></i> Resellers</a></li>
                <li class="nav-item"><a class="nav-link text-white <?php echo $self === 'settings.php' ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>/settings.php"><i class="bi bi-sliders"></i> Settings</a></li>
            <?php endif; ?>
            <li class="nav-item"><a class="nav-link text-white <?php echo $self === 'profile.php' ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>/profile.php"><i class="bi bi-shield-lock"></i> Profile</a></li>
        </ul>
        <?php if ($locked): ?>
            <div class="m-3 p-3 rounded maintenance-banner bg-dark border border-danger-subtle">
                <div class="fw-semibold small text-danger mb-1"><i class="bi bi-exclamation-triangle"></i> Maintenance Mode</div>
                <div class="small text-white-50">Owner access is live. Reseller write actions are temporarily blocked.</div>
            </div>
        <?php endif; ?>
    </nav>
</div>
