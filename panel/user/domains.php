<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';

requireRole('user');

$userId = $_SESSION['user_id'];
$users = readJson(DATA_PATH . 'users.json');
$me = findById($users, $userId);
$sites = $me['sites'] ?? [];
$success = $error = '';
$selectedSite = sanitize($_GET['site'] ?? ($sites[0]['name'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST[CSRF_TOKEN_NAME] ?? '')) { $error = 'Invalid token.'; }
    else {
        $action = $_POST['action'] ?? '';
        $sName = sanitize($_POST['site_name'] ?? '');
        foreach ($users as &$u) {
            if ($u['id'] !== $userId) continue;
            foreach ($u['sites'] ?? [] as &$s) {
                if ($s['name'] !== $sName) continue;
                if ($action === 'set_domain') {
                    $domain = strtolower(trim(sanitize($_POST['domain'] ?? '')));
                    if ($domain && !preg_match('/^[a-z0-9\.\-]+$/', $domain)) {
                        $error = 'Invalid domain format.';
                    } else {
                        $s['custom_domain'] = $domain;
                        $success = "Domain configured: {$domain}";
                    }
                } elseif ($action === 'remove_domain') {
                    $s['custom_domain'] = '';
                    $success = 'Custom domain removed.';
                }
                break 2;
            } unset($s);
        } unset($u);
        if (!$error) writeJson(DATA_PATH . 'users.json', $users);
        $users = readJson(DATA_PATH . 'users.json');
        $me = findById($users, $userId);
        $sites = $me['sites'] ?? [];
    }
}

$currentSite = null;
foreach ($sites as $s) { if ($s['name'] === $selectedSite) { $currentSite = $s; break; } }

renderHead('Domains');
renderSidebar('user', 'sites');
renderTopbar('Custom Domains');
?>

<?php if ($success) renderAlert('success', $success); ?>
<?php if ($error) renderAlert('danger', $error); ?>

<?php if (empty($sites)): ?>
<div class="empty-state"><span class="empty-icon">🌍</span><h3>No sites found</h3><a href="<?= BASE_URL ?>user/sites.php" class="btn btn-primary">Create a Site First</a></div>
<?php else: ?>

<div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;">
    <label>Select Site:</label>
    <?php foreach ($sites as $s): ?>
    <a href="?site=<?= urlencode($s['name']) ?>" class="btn <?= $s['name'] === $selectedSite ? 'btn-primary' : 'btn-secondary' ?>"><?= htmlspecialchars($s['name']) ?></a>
    <?php endforeach; ?>
</div>

<?php if ($currentSite): ?>
<div class="row">
    <div class="col">
        <div class="card animate-in">
            <div class="card-header"><span class="card-title">🌍 Custom Domain for "<?= htmlspecialchars($currentSite['name']) ?>"</span></div>
            <div class="card-body">
                <?php if (!empty($currentSite['custom_domain'])): ?>
                <div class="alert alert-success">✅ Custom domain set: <strong><?= htmlspecialchars($currentSite['custom_domain']) ?></strong></div>
                <form method="POST" style="display:inline;">
                    <?php csrfField(); ?><input type="hidden" name="action" value="remove_domain"><input type="hidden" name="site_name" value="<?= htmlspecialchars($currentSite['name']) ?>">
                    <button type="submit" class="btn btn-danger">Remove Domain</button>
                </form>
                <?php else: ?>
                <form method="POST">
                    <?php csrfField(); ?><input type="hidden" name="action" value="set_domain"><input type="hidden" name="site_name" value="<?= htmlspecialchars($currentSite['name']) ?>">
                    <div class="form-group">
                        <label>Domain Name</label>
                        <input type="text" class="form-control" name="domain" placeholder="example.com" pattern="[a-zA-Z0-9\.\-]+">
                        <div class="text-small text-muted" style="margin-top:6px;">Enter without http:// (e.g., <code>example.com</code> or <code>sub.example.com</code>)</div>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Domain</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col" style="max-width:400px;">
        <div class="card animate-in">
            <div class="card-header"><span class="card-title">📋 DNS Setup Instructions</span></div>
            <div class="card-body">
                <p>Point your domain to our server by creating a DNS record:</p>
                <div style="background:var(--surface2);padding:12px;border-radius:var(--radius-sm);font-family:monospace;font-size:13px;margin:10px 0;">
                    <div><strong>Type:</strong> A</div>
                    <div><strong>Name:</strong> @ (or subdomain)</div>
                    <div><strong>Value:</strong> <?= $_SERVER['SERVER_ADDR'] ?? 'YOUR_SERVER_IP' ?></div>
                    <div><strong>TTL:</strong> 3600</div>
                </div>
                <p class="text-small text-muted">DNS propagation may take up to 24-48 hours. After setup, your site will be accessible via your custom domain.</p>
                <div class="alert alert-warning" style="margin-top:10px;">
                    ⚠️ After adding your domain, contact support to enable SSL (HTTPS) for your domain.
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>

<?php renderFooter(); ?>
