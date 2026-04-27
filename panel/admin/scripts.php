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
        $scripts = readJson(DATA_PATH . 'scripts.json');

        if ($action === 'upload') {
            $name = sanitize($_POST['name'] ?? '');
            $version = sanitize($_POST['version'] ?? '1.0');
            $category = sanitize($_POST['category'] ?? 'General');
            $description = sanitize($_POST['description'] ?? '');
            $demoLink = filter_var(trim($_POST['demo_link'] ?? ''), FILTER_SANITIZE_URL);
            $thumbnail = sanitize($_POST['thumbnail'] ?? '');

            if (empty($name)) { $error = 'Script name is required.'; }
            elseif (empty($_FILES['script_file']['name'])) { $error = 'Please upload a ZIP file.'; }
            else {
                $file = $_FILES['script_file'];
                if ($file['error'] !== UPLOAD_ERR_OK) { $error = 'Upload error: ' . $file['error']; }
                elseif ($file['size'] > MAX_UPLOAD_SIZE) { $error = 'File too large (max 50MB).'; }
                elseif (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'zip') { $error = 'Only ZIP files are allowed.'; }
                else {
                    $id = generateId('s');
                    $filename = $id . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '_', basename($file['name']));
                    $dest = SCRIPTS_PATH . $filename;
                    if (!is_dir(SCRIPTS_PATH)) mkdir(SCRIPTS_PATH, 0755, true);
                    if (move_uploaded_file($file['tmp_name'], $dest)) {
                        $scripts[] = [
                            'id' => $id,
                            'name' => $name,
                            'version' => $version,
                            'category' => $category,
                            'description' => $description,
                            'demo_link' => $demoLink,
                            'file' => $filename,
                            'size_kb' => (int)($file['size'] / 1024),
                            'created_at' => date('c'),
                            'status' => 'active',
                            'thumbnail' => $thumbnail,
                        ];
                        writeJson(DATA_PATH . 'scripts.json', $scripts);
                        appLog('upload_script', $_SESSION['username'], "Uploaded script: {$name}");
                        $success = "Script '{$name}' uploaded.";
                    } else { $error = 'Failed to save file.'; }
                }
            }

        } elseif ($action === 'edit') {
            $id = sanitize($_POST['id'] ?? '');
            $updates = [
                'name' => sanitize($_POST['name'] ?? ''),
                'version' => sanitize($_POST['version'] ?? ''),
                'category' => sanitize($_POST['category'] ?? ''),
                'description' => sanitize($_POST['description'] ?? ''),
                'demo_link' => filter_var(trim($_POST['demo_link'] ?? ''), FILTER_SANITIZE_URL),
                'thumbnail' => sanitize($_POST['thumbnail'] ?? ''),
                'status' => in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active',
            ];
            // Replace ZIP if uploaded
            if (!empty($_FILES['script_file']['name']) && $_FILES['script_file']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['script_file'];
                if (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) === 'zip') {
                    $script = findById($scripts, $id);
                    $filename = $id . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '_', basename($file['name']));
                    $dest = SCRIPTS_PATH . $filename;
                    if (move_uploaded_file($file['tmp_name'], $dest)) {
                        // Delete old file
                        if ($script && !empty($script['file']) && file_exists(SCRIPTS_PATH . $script['file'])) {
                            unlink(SCRIPTS_PATH . $script['file']);
                        }
                        $updates['file'] = $filename;
                        $updates['size_kb'] = (int)($file['size'] / 1024);
                    }
                }
            }
            updateById($scripts, $id, $updates);
            writeJson(DATA_PATH . 'scripts.json', $scripts);
            $success = 'Script updated.';

        } elseif ($action === 'delete') {
            $id = sanitize($_POST['id'] ?? '');
            $script = findById($scripts, $id);
            if ($script) {
                if (!empty($script['file']) && file_exists(SCRIPTS_PATH . $script['file'])) {
                    unlink(SCRIPTS_PATH . $script['file']);
                }
                deleteById($scripts, $id);
                writeJson(DATA_PATH . 'scripts.json', $scripts);
                appLog('delete_script', $_SESSION['username'], "Deleted script: {$script['name']}");
                $success = 'Script deleted.';
            }
        }
    }
}

$scripts = readJson(DATA_PATH . 'scripts.json');
$categories = ['Blog', 'E-commerce', 'CMS', 'Forum', 'Portfolio', 'Landing Page', 'Admin Panel', 'Other'];

renderHead('Scripts');
renderSidebar('admin', 'scripts');
renderTopbar('Manage Scripts');
?>

<?php if ($success) renderAlert('success', $success); ?>
<?php if ($error) renderAlert('danger', $error); ?>

<div class="section-header">
    <h2 class="section-title">Scripts (<?= count($scripts) ?>)</h2>
    <div style="display:flex;gap:8px;">
        <div class="search-bar"><input type="text" class="form-control" id="tableSearch" placeholder="Search..."></div>
        <button class="btn btn-primary" onclick="openModal('uploadModal')">📤 Upload Script</button>
    </div>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Script</th><th>Category</th><th>Version</th><th>Size</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($scripts)): ?>
            <tr><td colspan="7"><div class="empty-state"><span class="empty-icon">🚀</span><h3>No scripts yet</h3><p>Upload your first script ZIP</p></div></td></tr>
            <?php else: foreach ($scripts as $s): ?>
            <tr>
                <td>
                    <div style="font-weight:600;"><?= htmlspecialchars($s['name']) ?></div>
                    <div class="text-small text-muted truncate" style="max-width:200px;"><?= htmlspecialchars($s['description']) ?></div>
                </td>
                <td><?= htmlspecialchars($s['category']) ?></td>
                <td><?= htmlspecialchars($s['version']) ?></td>
                <td class="text-small"><?= $s['size_kb'] > 0 ? ($s['size_kb'] >= 1024 ? round($s['size_kb']/1024,1).'MB' : $s['size_kb'].'KB') : '—' ?></td>
                <td><?= getStatusBadge($s['status'] ?? 'active') ?></td>
                <td class="text-small text-muted"><?= date('M j, Y', strtotime($s['created_at'])) ?></td>
                <td>
                    <div style="display:flex;gap:4px;">
                        <button class="btn btn-sm btn-secondary" onclick="editScript(<?= htmlspecialchars(json_encode($s)) ?>)">✏️</button>
                        <?php if (!empty($s['demo_link']) && $s['demo_link'] !== '#'): ?>
                        <a href="<?= htmlspecialchars($s['demo_link']) ?>" target="_blank" class="btn btn-sm btn-info">🔗</a>
                        <?php endif; ?>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete script?')">
                            <?php csrfField(); ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
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

<!-- Upload Modal -->
<div class="modal-overlay" id="uploadModal">
    <div class="modal">
        <div class="modal-header"><span class="modal-title">📤 Upload Script</span><button class="modal-close" data-modal-close>✕</button></div>
        <form method="POST" enctype="multipart/form-data"><div class="modal-body">
            <?php csrfField(); ?><input type="hidden" name="action" value="upload">
            <div class="form-row">
                <div class="form-group"><label>Script Name</label><input type="text" class="form-control" name="name" placeholder="WordPress Starter" required></div>
                <div class="form-group"><label>Version</label><input type="text" class="form-control" name="version" value="1.0"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Category</label>
                    <select class="form-control" name="category">
                        <?php foreach ($categories as $c): ?><option><?= $c ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Demo URL</label><input type="url" class="form-control" name="demo_link" placeholder="https://..."></div>
            </div>
            <div class="form-group"><label>Description</label><textarea class="form-control" name="description" rows="2" placeholder="Brief description..."></textarea></div>
            <div class="form-group"><label>Thumbnail URL <span class="text-muted">(optional)</span></label><input type="url" class="form-control" name="thumbnail" placeholder="https://..."></div>
            <div class="form-group">
                <label>ZIP File <span style="color:var(--danger);">*</span></label>
                <input type="file" class="form-control" name="script_file" accept=".zip" required>
                <div class="form-hint">Max 50MB. Must be a .zip file.</div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
            <button type="submit" class="btn btn-primary">📤 Upload</button>
        </div></form>
    </div>
</div>

<!-- Edit Script Modal -->
<div class="modal-overlay" id="editScriptModal">
    <div class="modal">
        <div class="modal-header"><span class="modal-title">✏️ Edit Script</span><button class="modal-close" data-modal-close>✕</button></div>
        <form method="POST" enctype="multipart/form-data"><div class="modal-body">
            <?php csrfField(); ?><input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="esId">
            <div class="form-row">
                <div class="form-group"><label>Name</label><input type="text" class="form-control" name="name" id="esName" required></div>
                <div class="form-group"><label>Version</label><input type="text" class="form-control" name="version" id="esVersion"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Category</label>
                    <select class="form-control" name="category" id="esCategory">
                        <?php foreach ($categories as $c): ?><option value="<?= $c ?>"><?= $c ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Status</label>
                    <select class="form-control" name="status" id="esStatus">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="form-group"><label>Description</label><textarea class="form-control" name="description" id="esDesc" rows="2"></textarea></div>
            <div class="form-row">
                <div class="form-group"><label>Demo URL</label><input type="url" class="form-control" name="demo_link" id="esDemo"></div>
                <div class="form-group"><label>Thumbnail URL</label><input type="url" class="form-control" name="thumbnail" id="esThumb"></div>
            </div>
            <div class="form-group">
                <label>Replace ZIP <span class="text-muted">(leave blank to keep)</span></label>
                <input type="file" class="form-control" name="script_file" accept=".zip">
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
function editScript(s) {
    document.getElementById('esId').value = s.id;
    document.getElementById('esName').value = s.name;
    document.getElementById('esVersion').value = s.version;
    document.getElementById('esCategory').value = s.category;
    document.getElementById('esStatus').value = s.status || 'active';
    document.getElementById('esDesc').value = s.description || '';
    document.getElementById('esDemo').value = s.demo_link || '';
    document.getElementById('esThumb').value = s.thumbnail || '';
    openModal('editScriptModal');
}
</script>
