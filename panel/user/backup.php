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
$sites = $me['sites'] ?? [];
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST[CSRF_TOKEN_NAME] ?? '')) { $error = 'Invalid token.'; }
    else {
        $action = $_POST['action'] ?? '';
        if ($action === 'create') {
            $siteName = sanitize($_POST['site_name'] ?? '');
            if (!$siteName) { $error = 'Select a site.'; }
            else {
                $result = createBackup($userId, $siteName);
                if ($result['success']) $success = "Backup created for site '{$siteName}'.";
                else $error = $result['error'];
            }
        } elseif ($action === 'delete') {
            $file = sanitize($_POST['file'] ?? '');
            $backupDir = UPLOADS_PATH . 'backups/' . $userId . '/';
            $target = $backupDir . basename($file);
            if (strpos(realpath(dirname($target)), realpath($backupDir)) === 0 && is_file($target)) {
                unlink($target); $success = 'Backup deleted.';
            } else $error = 'Invalid backup file.';
        }
    }
}

// List backups
$backupDir = UPLOADS_PATH . 'backups/' . $userId . '/';
$backups = [];
if (is_dir($backupDir)) {
    foreach (glob($backupDir . '*.zip') as $f) {
        $backups[] = ['file' => basename($f), 'size' => filesize($f), 'time' => filemtime($f)];
    }
    usort($backups, fn($a, $b) => $b['time'] - $a['time']);
}

renderHead('Backups');
renderSidebar('user', 'backup');
renderTopbar('Site Backups');
?>

<?php if ($success) renderAlert('success', $success); ?>
<?php if ($error) renderAlert('danger', $error); ?>

<div class="row">
    <div class="col" style="max-width:360px;">
        <div class="card animate-in">
            <div class="card-header"><span class="card-title">💾 Create Backup</span></div>
            <form method="POST"><div class="card-body">
                <?php csrfField(); ?><input type="hidden" name="action" value="create">
                <?php if (empty($sites)): ?>
                <div class="empty-state" style="padding:16px;"><span class="empty-icon">🌐</span><p>No sites to backup.</p><a href="<?= BASE_URL ?>user/sites.php" class="btn btn-primary">Create a Site</a></div>
                <?php else: ?>
                <div class="form-group">
                    <label>Select Site</label>
                    <select class="form-control" name="site_name" required>
                        <option value="">Choose site...</option>
                        <?php foreach ($sites as $s): ?>
                        <option value="<?= htmlspecialchars($s['name']) ?>"><?= htmlspecialchars($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Create Backup</button>
                <?php endif; ?>
            </div></form>
        </div>
        <div class="card animate-in" style="margin-top:16px;">
            <div class="card-header"><span class="card-title">ℹ️ Info</span></div>
            <div class="card-body">
                <ul style="padding-left:20px;font-size:13px;line-height:1.8;color:var(--text-muted);">
                    <li>Backups are stored as ZIP archives.</li>
                    <li>Download to your computer before deleting.</li>
                    <li>To restore, contact support or re-deploy and upload files.</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card animate-in">
            <div class="card-header"><span class="card-title">📦 My Backups (<?= count($backups) ?>)</span></div>
            <?php if (empty($backups)): ?>
            <div class="empty-state" style="padding:40px;"><span class="empty-icon">📦</span><h3>No backups yet</h3><p>Create your first backup to get started.</p></div>
            <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>File</th><th>Size</th><th>Date</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($backups as $b): ?>
                    <tr>
                        <td style="font-family:monospace;font-size:13px;"><?= htmlspecialchars($b['file']) ?></td>
                        <td class="text-small"><?= humanFileSize($b['size']) ?></td>
                        <td class="text-small text-muted"><?= date('M j, Y H:i', $b['time']) ?></td>
                        <td>
                            <div style="display:flex;gap:4px;">
                                <a href="<?= BASE_URL ?>user/backup.php?download=<?= urlencode($b['file']) ?>" class="btn btn-sm btn-primary">⬇️ Download</a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete backup?')">
                                    <?php csrfField(); ?><input type="hidden" name="action" value="delete"><input type="hidden" name="file" value="<?= htmlspecialchars($b['file']) ?>">
                                    <button class="btn btn-sm btn-danger">🗑️</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Handle download
if (isset($_GET['download'])) {
    $file = basename(sanitize($_GET['download']));
    $path = UPLOADS_PATH . 'backups/' . $userId . '/' . $file;
    if (is_file($path) && preg_match('/\.zip$/', $file)) {
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }
}
?>

<?php renderFooter(); ?>
