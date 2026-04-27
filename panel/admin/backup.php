<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/deploy.php';
require_once __DIR__ . '/../includes/layout.php';

requireRole('admin');

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create_backup') {
            $userId = sanitize($_POST['user_id'] ?? '');
            $siteId = sanitize($_POST['site_id'] ?? '');
            $result = createBackup($userId, $siteId);
            if ($result['success']) { $success = "Backup created: {$result['backup']}"; }
            else { $error = $result['error']; }

        } elseif ($action === 'delete_backup') {
            $path = sanitize($_POST['backup_path'] ?? '');
            $realUploads = realpath(UPLOADS_PATH);
            $realPath = realpath($path);
            if ($realPath && $realUploads && strpos($realPath, $realUploads) === 0 && file_exists($realPath)) {
                unlink($realPath);
                $success = 'Backup deleted.';
            } else { $error = 'Invalid backup file.'; }
        }
    }
}

$users = readJson(DATA_PATH . 'users.json');
$sites = readJson(DATA_PATH . 'sites.json');

// Build backup list
$backups = [];
$backupDir = UPLOADS_PATH . 'backups/';
if (is_dir($backupDir)) {
    foreach (scandir($backupDir) as $userDir) {
        if ($userDir === '.' || $userDir === '..') continue;
        $userPath = $backupDir . $userDir . '/';
        if (!is_dir($userPath)) continue;
        foreach (scandir($userPath) as $file) {
            if ($file === '.' || $file === '..') continue;
            $fullPath = $userPath . $file;
            if (is_file($fullPath)) {
                $backups[] = [
                    'user_id' => $userDir,
                    'file' => $file,
                    'path' => $fullPath,
                    'size' => filesize($fullPath),
                    'created' => filemtime($fullPath),
                ];
            }
        }
    }
}
usort($backups, fn($a, $b) => $b['created'] - $a['created']);

renderHead('Backups');
renderSidebar('admin', 'backup');
renderTopbar('Backups');
?>

<?php if ($success) renderAlert('success', $success); ?>
<?php if ($error) renderAlert('danger', $error); ?>

<div class="row">
    <div class="col" style="max-width:380px;">
        <div class="card">
            <div class="card-header"><span class="card-title">💾 Create Backup</span></div>
            <div class="card-body">
                <form method="POST" id="backupForm">
                    <?php csrfField(); ?><input type="hidden" name="action" value="create_backup">
                    <div class="form-group">
                        <label>User</label>
                        <select class="form-control" name="user_id" id="backupUser" onchange="loadUserSites()" required>
                            <option value="">Select user...</option>
                            <?php foreach ($users as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['username']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Site</label>
                        <select class="form-control" name="site_id" id="backupSite" required>
                            <option value="">Select site...</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">💾 Create Backup</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col">
        <div class="section-header">
            <h2 class="section-title">All Backups (<?= count($backups) ?>)</h2>
        </div>
        <div class="card">
            <div class="table-wrap">
                <table>
                    <thead><tr><th>File</th><th>User</th><th>Size</th><th>Created</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php if (empty($backups)): ?>
                    <tr><td colspan="5"><div class="empty-state"><span class="empty-icon">💾</span><h3>No backups yet</h3></div></td></tr>
                    <?php else: foreach ($backups as $b): ?>
                    <?php $user = findById($users, $b['user_id']); ?>
                    <tr>
                        <td class="font-mono text-small"><?= htmlspecialchars($b['file']) ?></td>
                        <td><?= htmlspecialchars($user['username'] ?? $b['user_id']) ?></td>
                        <td><?= humanFileSize($b['size']) ?></td>
                        <td class="text-small text-muted"><?= date('M j, Y H:i', $b['created']) ?></td>
                        <td>
                            <div style="display:flex;gap:4px;">
                                <a href="<?= BASE_URL ?>api.php?action=download_backup&path=<?= urlencode($b['path']) ?>&csrf=<?= generateCSRF() ?>" class="btn btn-sm btn-info">⬇️</a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete backup?')">
                                    <?php csrfField(); ?><input type="hidden" name="action" value="delete_backup">
                                    <input type="hidden" name="backup_path" value="<?= htmlspecialchars($b['path']) ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">🗑️</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php renderFooter(); ?>
<script>
const sitesData = <?= json_encode($sites) ?>;
function loadUserSites() {
    const userId = document.getElementById('backupUser').value;
    const sel = document.getElementById('backupSite');
    sel.innerHTML = '<option value="">Select site...</option>';
    sitesData.filter(s => s.user_id === userId).forEach(s => {
        const opt = document.createElement('option');
        opt.value = s.id; opt.textContent = s.name;
        sel.appendChild(opt);
    });
}
</script>
