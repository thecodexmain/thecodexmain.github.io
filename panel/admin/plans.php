<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';

requireRole('admin');

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $action = $_POST['action'] ?? '';
        $plans = readJson(DATA_PATH . 'plans.json');

        if ($action === 'create' || $action === 'edit') {
            $id = $action === 'edit' ? sanitize($_POST['id'] ?? '') : preg_replace('/[^a-z0-9_]/', '', strtolower(trim($_POST['id'] ?? '')));
            $name = sanitize($_POST['name'] ?? '');
            $price = (float)($_POST['price'] ?? 0);
            $duration = (int)($_POST['duration_days'] ?? 30);
            $maxSites = (int)($_POST['max_sites'] ?? 1);
            $maxScripts = (int)($_POST['max_scripts'] ?? 3);
            $storage = (int)($_POST['storage_mb'] ?? 512);
            $shortLinks = (int)($_POST['short_links'] ?? 10);
            $customDomain = !empty($_POST['custom_domain']);
            $fileManager = !empty($_POST['file_manager']);
            $backup = !empty($_POST['backup']);
            $description = sanitize($_POST['description'] ?? '');

            if (empty($id) || empty($name)) { $error = 'ID and Name are required.'; }
            else {
                $planData = [
                    'id' => $id,
                    'name' => $name,
                    'price' => $price,
                    'duration_days' => $duration,
                    'max_sites' => $maxSites,
                    'max_scripts' => $maxScripts,
                    'storage_mb' => $storage,
                    'custom_domain' => $customDomain,
                    'file_manager' => $fileManager,
                    'short_links' => $shortLinks,
                    'backup' => $backup,
                    'description' => $description,
                ];
                if ($action === 'create') {
                    foreach ($plans as $p) { if ($p['id'] === $id) { $error = 'Plan ID already exists.'; break; } }
                    if (!$error) { $plans[] = $planData; $success = "Plan '{$name}' created."; }
                } else {
                    updateById($plans, $id, $planData);
                    $success = "Plan updated.";
                }
                if (!$error) {
                    writeJson(DATA_PATH . 'plans.json', $plans);
                    appLog('manage_plan', $_SESSION['username'], "{$action} plan: {$id}");
                }
            }

        } elseif ($action === 'delete') {
            $id = sanitize($_POST['id'] ?? '');
            deleteById($plans, $id);
            writeJson(DATA_PATH . 'plans.json', $plans);
            appLog('delete_plan', $_SESSION['username'], "Deleted plan: {$id}");
            $success = 'Plan deleted.';
        }
    }
}

$plans = readJson(DATA_PATH . 'plans.json');

renderHead('Plans');
renderSidebar('admin', 'plans');
renderTopbar('Manage Plans');
?>

<?php if ($success) renderAlert('success', $success); ?>
<?php if ($error) renderAlert('danger', $error); ?>

<div class="section-header">
    <h2 class="section-title">Hosting Plans (<?= count($plans) ?>)</h2>
    <button class="btn btn-primary" onclick="openModal('createPlanModal')">➕ Create Plan</button>
</div>

<div class="plan-grid">
<?php foreach ($plans as $plan): ?>
<div class="plan-card <?= count($plans) > 1 && $plan['id'] === 'pro' ? 'featured' : '' ?>">
    <?php if (count($plans) > 1 && $plan['id'] === 'pro'): ?>
    <div style="position:absolute;top:-12px;right:16px;background:var(--primary);color:white;padding:3px 12px;border-radius:20px;font-size:11px;font-weight:700;">POPULAR</div>
    <?php endif; ?>
    <div style="font-weight:700;font-size:18px;margin-bottom:4px;"><?= htmlspecialchars($plan['name']) ?></div>
    <div class="plan-price">$<?= $plan['price'] ?><small>/<?= $plan['duration_days'] ?>d</small></div>
    <div class="text-small text-muted" style="margin:8px 0;"><?= htmlspecialchars($plan['description']) ?></div>
    <ul class="plan-features">
        <li><?= $plan['max_sites'] ?> Site<?= $plan['max_sites'] > 1 ? 's' : '' ?></li>
        <li><?= $plan['max_scripts'] >= 999 ? 'Unlimited' : $plan['max_scripts'] ?> Script Deploys</li>
        <li><?= $plan['storage_mb'] >= 1024 ? round($plan['storage_mb']/1024,1).'GB' : $plan['storage_mb'].'MB' ?> Storage</li>
        <li><?= $plan['short_links'] >= 999 ? 'Unlimited' : $plan['short_links'] ?> Short Links</li>
        <?php if ($plan['custom_domain']): ?><li>Custom Domain</li><?php endif; ?>
        <?php if ($plan['file_manager']): ?><li>File Manager</li><?php endif; ?>
        <?php if ($plan['backup']): ?><li>Backups</li><?php endif; ?>
    </ul>
    <div style="display:flex;gap:6px;margin-top:auto;">
        <button class="btn btn-sm btn-secondary" style="flex:1;" onclick="editPlan(<?= htmlspecialchars(json_encode($plan)) ?>)">✏️ Edit</button>
        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete plan <?= htmlspecialchars(addslashes($plan['name'])) ?>?')">
            <?php csrfField(); ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= $plan['id'] ?>">
            <button type="submit" class="btn btn-sm btn-danger">🗑️</button>
        </form>
    </div>
</div>
<?php endforeach; ?>
</div>

<!-- Create Plan Modal -->
<div class="modal-overlay" id="createPlanModal">
    <div class="modal">
        <div class="modal-header"><span class="modal-title">➕ Create Plan</span><button class="modal-close" data-modal-close>✕</button></div>
        <form method="POST"><div class="modal-body">
            <?php csrfField(); ?><input type="hidden" name="action" value="create">
            <div class="form-row">
                <div class="form-group"><label>Plan ID <small class="text-muted">(lowercase, no spaces)</small></label><input type="text" class="form-control" name="id" placeholder="pro_plus" required></div>
                <div class="form-group"><label>Name</label><input type="text" class="form-control" name="name" placeholder="Pro Plus" required></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Price (USD)</label><input type="number" step="0.01" class="form-control" name="price" value="10" min="0"></div>
                <div class="form-group"><label>Duration (days)</label><input type="number" class="form-control" name="duration_days" value="30" min="1"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Max Sites</label><input type="number" class="form-control" name="max_sites" value="1" min="1"></div>
                <div class="form-group"><label>Max Scripts</label><input type="number" class="form-control" name="max_scripts" value="5" min="1"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Storage (MB)</label><input type="number" class="form-control" name="storage_mb" value="1024" min="1"></div>
                <div class="form-group"><label>Short Links</label><input type="number" class="form-control" name="short_links" value="20" min="0"></div>
            </div>
            <div class="form-group"><label>Description</label><input type="text" class="form-control" name="description" placeholder="Plan description..."></div>
            <div style="display:flex;gap:16px;flex-wrap:wrap;">
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;"><input type="checkbox" name="custom_domain" value="1"> Custom Domain</label>
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;"><input type="checkbox" name="file_manager" value="1" checked> File Manager</label>
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;"><input type="checkbox" name="backup" value="1"> Backups</label>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
            <button type="submit" class="btn btn-primary">Create Plan</button>
        </div></form>
    </div>
</div>

<!-- Edit Plan Modal -->
<div class="modal-overlay" id="editPlanModal">
    <div class="modal">
        <div class="modal-header"><span class="modal-title">✏️ Edit Plan</span><button class="modal-close" data-modal-close>✕</button></div>
        <form method="POST"><div class="modal-body">
            <?php csrfField(); ?><input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="epId">
            <div class="form-row">
                <div class="form-group"><label>Name</label><input type="text" class="form-control" name="name" id="epName" required></div>
                <div class="form-group"><label>Price</label><input type="number" step="0.01" class="form-control" name="price" id="epPrice" min="0"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Duration (days)</label><input type="number" class="form-control" name="duration_days" id="epDuration" min="1"></div>
                <div class="form-group"><label>Max Sites</label><input type="number" class="form-control" name="max_sites" id="epSites" min="1"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Max Scripts</label><input type="number" class="form-control" name="max_scripts" id="epScripts" min="1"></div>
                <div class="form-group"><label>Storage (MB)</label><input type="number" class="form-control" name="storage_mb" id="epStorage" min="1"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Short Links</label><input type="number" class="form-control" name="short_links" id="epLinks" min="0"></div>
                <div class="form-group"><label>Description</label><input type="text" class="form-control" name="description" id="epDesc"></div>
            </div>
            <div style="display:flex;gap:16px;flex-wrap:wrap;">
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;"><input type="checkbox" name="custom_domain" id="epDomain"> Custom Domain</label>
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;"><input type="checkbox" name="file_manager" id="epFM"> File Manager</label>
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;"><input type="checkbox" name="backup" id="epBackup"> Backups</label>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div></form>
    </div>
</div>

<?php renderFooter(); ?>
<script>
function editPlan(p) {
    document.getElementById('epId').value = p.id;
    document.getElementById('epName').value = p.name;
    document.getElementById('epPrice').value = p.price;
    document.getElementById('epDuration').value = p.duration_days;
    document.getElementById('epSites').value = p.max_sites;
    document.getElementById('epScripts').value = p.max_scripts;
    document.getElementById('epStorage').value = p.storage_mb;
    document.getElementById('epLinks').value = p.short_links;
    document.getElementById('epDesc').value = p.description || '';
    document.getElementById('epDomain').checked = !!p.custom_domain;
    document.getElementById('epFM').checked = !!p.file_manager;
    document.getElementById('epBackup').checked = !!p.backup;
    openModal('editPlanModal');
}
</script>
