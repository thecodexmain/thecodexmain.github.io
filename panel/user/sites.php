<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/deploy.php';
require_once __DIR__ . '/../includes/layout.php';

requireRole('user');

$userId = $_SESSION['user_id'];
$users = readJson(DATA_PATH . 'users.json');
$me = findById($users, $userId);
$plan = getUserPlan($userId);
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST[CSRF_TOKEN_NAME] ?? '')) { $error = 'Invalid token.'; }
    else {
        $action = $_POST['action'] ?? '';
        if ($action === 'create') {
            $name = sanitize($_POST['name'] ?? '');
            $scriptId = sanitize($_POST['script_id'] ?? '');
            if (empty($name)) { $error = 'Site name required.'; }
            else {
                $currentSites = $me['sites'] ?? [];
                if (($plan['sites_limit'] ?? 0) > 0 && count($currentSites) >= $plan['sites_limit']) {
                    $error = 'Site limit reached for your plan.';
                } else {
                    $result = createSite($userId, $name, $scriptId);
                    if ($result['success']) { $success = "Site '{$name}' created!"; }
                    else { $error = $result['error']; }
                }
            }
        } elseif ($action === 'delete') {
            $siteName = sanitize($_POST['site_name'] ?? '');
            $result = deleteSite($userId, $siteName);
            if ($result['success']) $success = "Site deleted.";
            else $error = $result['error'];
        } elseif ($action === 'reset') {
            $siteName = sanitize($_POST['site_name'] ?? '');
            $scriptId = sanitize($_POST['script_id'] ?? '');
            $result = deployScript($userId, $siteName, $scriptId);
            if ($result['success']) $success = "Site reset with new script.";
            else $error = $result['error'];
        }
        $users = readJson(DATA_PATH . 'users.json');
        $me = findById($users, $userId);
    }
}

$scripts = array_filter(readJson(DATA_PATH . 'scripts.json'), fn($s) => ($s['status'] ?? 'active') === 'active');

renderHead('My Sites');
renderSidebar('user', 'sites');
renderTopbar('My Sites');
?>

<?php if ($success) renderAlert('success', $success); ?>
<?php if ($error) renderAlert('danger', $error); ?>

<div class="section-header">
    <h2 class="section-title">Sites (<?= count($me['sites'] ?? []) ?> / <?= $plan['sites_limit'] ?? '∞' ?>)</h2>
    <?php if (!($plan['sites_limit'] ?? 0) || count($me['sites'] ?? []) < $plan['sites_limit']): ?>
    <button class="btn btn-primary" onclick="openModal('createSiteModal')">➕ New Site</button>
    <?php else: ?>
    <span class="badge badge-warning">Site limit reached</span>
    <?php endif; ?>
</div>

<?php $sites = $me['sites'] ?? []; ?>
<?php if (empty($sites)): ?>
<div class="empty-state"><span class="empty-icon">🌐</span><h3>No sites yet</h3><p>Create your first site and deploy a script.</p></div>
<?php else: ?>
<div class="script-grid">
    <?php foreach ($sites as $site): ?>
    <div class="script-card animate-in">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <div style="font-size:32px;">🌐</div>
            <?= getStatusBadge($site['status'] ?? 'active') ?>
        </div>
        <div>
            <div style="font-weight:700;font-size:16px;"><?= htmlspecialchars($site['name']) ?></div>
            <div class="text-small text-muted"><?= date('M j, Y', strtotime($site['created_at'])) ?></div>
        </div>
        <?php if (!empty($site['script_name'])): ?>
        <div class="text-small" style="background:var(--surface2);padding:6px 10px;border-radius:var(--radius-sm);">
            📦 <?= htmlspecialchars($site['script_name']) ?> v<?= htmlspecialchars($site['script_version'] ?? '') ?>
        </div>
        <?php endif; ?>
        <div class="text-small text-muted">💾 <?= humanFileSize($site['storage_bytes'] ?? 0) ?></div>
        <div style="display:flex;gap:6px;flex-wrap:wrap;">
            <a href="<?= BASE_URL ?>user/filemanager.php?site=<?= urlencode($site['name']) ?>" class="btn btn-sm btn-secondary" style="flex:1;">📁 Files</a>
            <a href="<?= BASE_URL ?>user/domains.php?site=<?= urlencode($site['name']) ?>" class="btn btn-sm btn-secondary" style="flex:1;">🌍 Domain</a>
            <button class="btn btn-sm btn-warning" onclick="openResetModal('<?= addslashes($site['name']) ?>')">🔄</button>
            <button class="btn btn-sm btn-danger" onclick="openDeleteSiteModal('<?= addslashes($site['name']) ?>')">🗑️</button>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Create Site Modal -->
<div class="modal-overlay" id="createSiteModal">
    <div class="modal">
        <div class="modal-header"><span class="modal-title">➕ New Site</span><button class="modal-close" data-modal-close>✕</button></div>
        <form method="POST"><div class="modal-body">
            <?php csrfField(); ?><input type="hidden" name="action" value="create">
            <div class="form-group"><label>Site Name <small class="text-muted">(letters, numbers, hyphens only)</small></label><input type="text" class="form-control" name="name" pattern="[a-zA-Z0-9\-]+" required></div>
            <div class="form-group">
                <label>Script to Deploy <small class="text-muted">(optional)</small></label>
                <select class="form-control" name="script_id">
                    <option value="">— No script, just empty site —</option>
                    <?php foreach ($scripts as $s): ?>
                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?> v<?= htmlspecialchars($s['version']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
            <button type="submit" class="btn btn-primary">Create Site</button>
        </div></form>
    </div>
</div>

<!-- Reset/Deploy Modal -->
<div class="modal-overlay" id="resetSiteModal">
    <div class="modal">
        <div class="modal-header"><span class="modal-title">🔄 Deploy / Reset Site</span><button class="modal-close" data-modal-close>✕</button></div>
        <form method="POST"><div class="modal-body">
            <?php csrfField(); ?><input type="hidden" name="action" value="reset">
            <input type="hidden" name="site_name" id="resetSiteName">
            <div class="alert alert-warning">⚠️ Deploying a new script will overwrite existing site files (config files are preserved).</div>
            <div class="form-group">
                <label>Select Script</label>
                <select class="form-control" name="script_id" required>
                    <option value="">Select script...</option>
                    <?php foreach ($scripts as $s): ?>
                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?> v<?= htmlspecialchars($s['version']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
            <button type="submit" class="btn btn-primary">Deploy Script</button>
        </div></form>
    </div>
</div>

<!-- Delete Site Modal -->
<div class="modal-overlay" id="deleteSiteModal">
    <div class="modal">
        <div class="modal-header"><span class="modal-title">🗑️ Delete Site</span><button class="modal-close" data-modal-close>✕</button></div>
        <form method="POST"><div class="modal-body">
            <?php csrfField(); ?><input type="hidden" name="action" value="delete">
            <input type="hidden" name="site_name" id="deleteSiteName">
            <div class="alert alert-danger">⚠️ This will permanently delete the site and all its files. This cannot be undone.</div>
            <p>Are you sure you want to delete site <strong id="deleteSiteNameDisplay"></strong>?</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
            <button type="submit" class="btn btn-danger">Delete Site</button>
        </div></form>
    </div>
</div>

<script>
function openResetModal(name) {
    document.getElementById('resetSiteName').value = name;
    openModal('resetSiteModal');
}
function openDeleteSiteModal(name) {
    document.getElementById('deleteSiteName').value = name;
    document.getElementById('deleteSiteNameDisplay').textContent = name;
    openModal('deleteSiteModal');
}
</script>

<?php renderFooter(); ?>
