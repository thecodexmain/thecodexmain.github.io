<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/filemanager_helper.php';
require_once __DIR__ . '/../includes/layout.php';

requireRole('user');

$userId = $_SESSION['user_id'];
$users = readJson(DATA_PATH . 'users.json');
$me = findById($users, $userId);
$sites = $me['sites'] ?? [];
$siteName = sanitize($_GET['site'] ?? '');

if (!$siteName) {
    if (empty($sites)) redirect(BASE_URL . 'user/sites.php');
    $siteName = $sites[0]['name'];
    redirect(BASE_URL . 'user/filemanager.php?site=' . urlencode($siteName));
}

// Verify site belongs to user
$siteData = null;
foreach ($sites as $s) { if ($s['name'] === $siteName) { $siteData = $s; break; } }
if (!$siteData) redirect(BASE_URL . 'user/sites.php');

$siteRoot = USERS_PATH . $userId . '/' . $siteName;
$relPath = sanitize($_GET['path'] ?? '');
$currentPath = $siteRoot . '/' . ltrim($relPath, '/');

// Validate path
$pathOk = validatePath($currentPath, $siteRoot);
if (!$pathOk) { $currentPath = $siteRoot; $relPath = ''; }

$success = $error = '';
$action = $_REQUEST['action'] ?? '';
$isAjaxReq = isAjax();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST[CSRF_TOKEN_NAME] ?? '')) { $error = 'Invalid token.'; goto render; }

    if ($action === 'upload') {
        if (!empty($_FILES['file']['tmp_name'])) {
            $fname = basename($_FILES['file']['name']);
            $dest = rtrim($currentPath, '/') . '/' . $fname;
            if (validatePath($dest, $siteRoot)) {
                if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) $success = 'File uploaded.';
                else $error = 'Upload failed.';
            } else $error = 'Invalid path.';
        }
    } elseif ($action === 'mkdir') {
        $dname = sanitize($_POST['name'] ?? '');
        if ($dname && preg_match('/^[a-zA-Z0-9_\-\.]+$/', $dname)) {
            $newDir = rtrim($currentPath, '/') . '/' . $dname;
            if (validatePath($newDir, $siteRoot)) {
                @mkdir($newDir, 0755); $success = 'Folder created.';
            } else $error = 'Invalid name.';
        } else $error = 'Invalid folder name.';
    } elseif ($action === 'save') {
        $file = sanitize($_POST['file'] ?? '');
        $content = $_POST['content'] ?? '';
        $target = $siteRoot . '/' . ltrim($file, '/');
        if (validatePath($target, $siteRoot) && is_file($target)) {
            if (safeFileWrite($target, $content)) $success = 'File saved.';
            else $error = 'Save failed.';
        } else $error = 'Invalid file.';
    } elseif ($action === 'delete') {
        $target = $siteRoot . '/' . ltrim(sanitize($_POST['file'] ?? ''), '/');
        if (validatePath($target, $siteRoot)) {
            if (is_file($target)) { unlink($target); $success = 'Deleted.'; }
            elseif (is_dir($target)) { deleteFolderRecursive($target, USERS_PATH); $success = 'Folder deleted.'; }
        } else $error = 'Invalid path.';
    } elseif ($action === 'rename') {
        $target = $siteRoot . '/' . ltrim(sanitize($_POST['file'] ?? ''), '/');
        $newName = sanitize($_POST['newname'] ?? '');
        if (validatePath($target, $siteRoot) && preg_match('/^[a-zA-Z0-9_\-\.]+$/', $newName)) {
            $newTarget = dirname($target) . '/' . $newName;
            if (validatePath($newTarget, $siteRoot)) { rename($target, $newTarget); $success = 'Renamed.'; }
        } else $error = 'Invalid input.';
    }
}

render:
// Edit mode
$editFile = '';
$editContent = '';
if ($action === 'edit' && !empty($_GET['file'])) {
    $ef = $siteRoot . '/' . ltrim(sanitize($_GET['file']), '/');
    if (validatePath($ef, $siteRoot) && is_file($ef) && isTextFile($ef)) {
        $editFile = sanitize($_GET['file']);
        $editContent = file_get_contents($ef);
    }
}

$items = listDirectory($currentPath, $siteRoot);
$breadcrumbs = [];
if ($relPath) {
    $parts = explode('/', trim($relPath, '/'));
    $built = '';
    foreach ($parts as $p) {
        $built .= '/' . $p;
        $breadcrumbs[] = ['name' => $p, 'path' => $built];
    }
}

renderHead('File Manager');
renderSidebar('user', 'sites');
renderTopbar('File Manager — ' . htmlspecialchars($siteName));
?>

<?php if ($success) renderAlert('success', $success); ?>
<?php if ($error) renderAlert('danger', $error); ?>

<!-- Site switcher + breadcrumb -->
<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:16px;">
    <select class="form-control" style="width:auto;" onchange="location.href='<?= BASE_URL ?>user/filemanager.php?site='+this.value">
        <?php foreach ($sites as $s): ?>
        <option value="<?= htmlspecialchars($s['name']) ?>" <?= $s['name'] === $siteName ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <nav style="display:flex;align-items:center;gap:4px;font-size:13px;">
        <a href="?site=<?= urlencode($siteName) ?>" style="color:var(--primary);">/ root</a>
        <?php foreach ($breadcrumbs as $bc): ?>
        <span>/</span>
        <a href="?site=<?= urlencode($siteName) ?>&path=<?= urlencode($bc['path']) ?>" style="color:var(--primary);"><?= htmlspecialchars($bc['name']) ?></a>
        <?php endforeach; ?>
    </nav>
</div>

<?php if ($editFile): ?>
<!-- Edit panel -->
<div class="card animate-in">
    <div class="card-header">
        <span class="card-title">✏️ Edit: <?= htmlspecialchars(basename($editFile)) ?></span>
        <a href="?site=<?= urlencode($siteName) ?>&path=<?= urlencode($relPath) ?>" class="btn btn-sm btn-secondary">← Back</a>
    </div>
    <form method="POST"><div class="card-body" style="padding:0;">
        <?php csrfField(); ?><input type="hidden" name="action" value="save"><input type="hidden" name="file" value="<?= htmlspecialchars($editFile) ?>">
        <textarea name="content" style="width:100%;height:60vh;font-family:monospace;font-size:13px;border:none;border-radius:0 0 var(--radius) var(--radius);padding:16px;background:var(--surface2);color:var(--text);resize:vertical;outline:none;"><?= htmlspecialchars($editContent) ?></textarea>
    </div>
    <div class="modal-footer">
        <a href="?site=<?= urlencode($siteName) ?>&path=<?= urlencode($relPath) ?>" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">💾 Save File</button>
    </div></form>
</div>
<?php else: ?>
<!-- Toolbar -->
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;">
    <button class="btn btn-secondary" onclick="openModal('uploadModal')">⬆️ Upload</button>
    <button class="btn btn-secondary" onclick="openModal('mkdirModal')">📁 New Folder</button>
    <?php if ($relPath): ?>
    <a href="?site=<?= urlencode($siteName) ?>&path=<?= urlencode(dirname($relPath) === '.' ? '' : dirname($relPath)) ?>" class="btn btn-secondary">⬆ Parent</a>
    <?php endif; ?>
</div>

<div class="card animate-in">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Name</th><th>Size</th><th>Modified</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($items)): ?>
            <tr><td colspan="4" class="text-center text-muted" style="padding:20px;">Empty folder</td></tr>
            <?php else: foreach ($items as $item): ?>
            <?php
                $itemRelPath = ltrim($relPath . '/' . $item['name'], '/');
                $itemRel = ltrim($relPath, '/');
            ?>
            <tr>
                <td>
                    <?php if ($item['is_dir']): ?>
                    <a href="?site=<?= urlencode($siteName) ?>&path=<?= urlencode($itemRelPath) ?>" style="font-weight:600;color:var(--primary);">
                        📁 <?= htmlspecialchars($item['name']) ?>
                    </a>
                    <?php else: ?>
                    <span><?= getFileIcon($item['name']) ?> <?= htmlspecialchars($item['name']) ?></span>
                    <?php endif; ?>
                </td>
                <td class="text-small text-muted"><?= $item['is_dir'] ? '—' : humanFileSize($item['size']) ?></td>
                <td class="text-small text-muted"><?= date('M j H:i', $item['modified']) ?></td>
                <td>
                    <div style="display:flex;gap:4px;">
                        <?php if (!$item['is_dir'] && $item['is_text']): ?>
                        <a href="?site=<?= urlencode($siteName) ?>&path=<?= urlencode($relPath) ?>&action=edit&file=<?= urlencode($itemRelPath) ?>" class="btn btn-sm btn-secondary">✏️</a>
                        <?php endif; ?>
                        <button class="btn btn-sm btn-warning" onclick="openRenameModal('<?= addslashes($itemRelPath) ?>', '<?= addslashes($item['name']) ?>')">✏️</button>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this item?')">
                            <?php csrfField(); ?><input type="hidden" name="action" value="delete"><input type="hidden" name="file" value="<?= htmlspecialchars($itemRelPath) ?>">
                            <button class="btn btn-sm btn-danger">🗑️</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Upload Modal -->
<div class="modal-overlay" id="uploadModal">
    <div class="modal">
        <div class="modal-header"><span class="modal-title">⬆️ Upload File</span><button class="modal-close" data-modal-close>✕</button></div>
        <form method="POST" enctype="multipart/form-data"><div class="modal-body">
            <?php csrfField(); ?><input type="hidden" name="action" value="upload">
            <div class="form-group"><label>Select File</label><input type="file" class="form-control" name="file" required></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-modal-close>Cancel</button><button type="submit" class="btn btn-primary">Upload</button></div></form>
    </div>
</div>

<!-- Mkdir Modal -->
<div class="modal-overlay" id="mkdirModal">
    <div class="modal">
        <div class="modal-header"><span class="modal-title">📁 New Folder</span><button class="modal-close" data-modal-close>✕</button></div>
        <form method="POST"><div class="modal-body">
            <?php csrfField(); ?><input type="hidden" name="action" value="mkdir">
            <div class="form-group"><label>Folder Name</label><input type="text" class="form-control" name="name" pattern="[a-zA-Z0-9_\-\.]+" required></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-modal-close>Cancel</button><button type="submit" class="btn btn-primary">Create</button></div></form>
    </div>
</div>

<!-- Rename Modal -->
<div class="modal-overlay" id="renameModal">
    <div class="modal">
        <div class="modal-header"><span class="modal-title">✏️ Rename</span><button class="modal-close" data-modal-close>✕</button></div>
        <form method="POST"><div class="modal-body">
            <?php csrfField(); ?><input type="hidden" name="action" value="rename">
            <input type="hidden" name="file" id="renameFile">
            <div class="form-group"><label>New Name</label><input type="text" class="form-control" name="newname" id="renameNewName" required pattern="[a-zA-Z0-9_\-\.]+"></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-modal-close>Cancel</button><button type="submit" class="btn btn-primary">Rename</button></div></form>
    </div>
</div>

<script>
function openRenameModal(file, name) {
    document.getElementById('renameFile').value = file;
    document.getElementById('renameNewName').value = name;
    openModal('renameModal');
}
</script>

<?php endif; ?>
<?php renderFooter(); ?>
