<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/api_helper.php';
require_once __DIR__ . '/../includes/layout.php';

requireRole('admin');

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'create') {
            $name = sanitize($_POST['name'] ?? '');
            $userId = sanitize($_POST['user_id'] ?? '');
            $rateLimit = (int)($_POST['rate_limit'] ?? 60);
            if (empty($name) || empty($userId)) { $error = 'Name and user required.'; }
            else {
                $result = generateApiKey($userId, $name, $rateLimit);
                if ($result['success']) { $success = "API Key created: " . htmlspecialchars($result['key']); }
            }
        } elseif ($action === 'revoke') {
            $id = sanitize($_POST['id'] ?? '');
            $keys = readJson(DATA_PATH . 'api_keys.json');
            deleteById($keys, $id);
            writeJson(DATA_PATH . 'api_keys.json', $keys);
            $success = 'API key revoked.';
        }
    }
}

$keys = readJson(DATA_PATH . 'api_keys.json');
$users = readJson(DATA_PATH . 'users.json');

renderHead('API Keys');
renderSidebar('admin', 'api_keys');
renderTopbar('API Keys');
?>

<?php if ($success) renderAlert('success', $success); ?>
<?php if ($error) renderAlert('danger', $error); ?>

<div class="section-header">
    <h2 class="section-title">API Keys (<?= count($keys) ?>)</h2>
    <button class="btn btn-primary" onclick="openModal('createKeyModal')">➕ Create Key</button>
</div>

<div class="alert alert-info">
    ℹ️ API keys grant access to the REST API. Send as header: <code class="font-mono">X-API-Key: YOUR_KEY</code>
    or query param: <code class="font-mono">?api_key=YOUR_KEY</code>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Name</th><th>Key</th><th>User</th><th>Rate Limit</th><th>Last Used</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($keys)): ?>
            <tr><td colspan="7"><div class="empty-state"><span class="empty-icon">🔑</span><h3>No API keys</h3></div></td></tr>
            <?php else: foreach ($keys as $k): ?>
            <?php $user = findById($users, $k['user_id']); ?>
            <tr>
                <td style="font-weight:600;"><?= htmlspecialchars($k['name']) ?></td>
                <td>
                    <div style="display:flex;align-items:center;gap:6px;">
                        <code class="font-mono text-small" style="background:var(--surface2);padding:3px 8px;border-radius:4px;">
                            <?= htmlspecialchars(substr($k['key'], 0, 20)) ?>...
                        </code>
                        <button class="btn btn-sm btn-secondary" data-copy="<?= htmlspecialchars($k['key']) ?>">📋</button>
                    </div>
                </td>
                <td><?= htmlspecialchars($user['username'] ?? $k['user_id']) ?></td>
                <td><?= $k['rate_limit'] ?>/min</td>
                <td class="text-small text-muted"><?= $k['last_used'] ? timeAgo($k['last_used']) : 'Never' ?></td>
                <td><?= getStatusBadge($k['status'] ?? 'active') ?></td>
                <td>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Revoke this API key?')">
                        <?php csrfField(); ?><input type="hidden" name="action" value="revoke"><input type="hidden" name="id" value="<?= $k['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger">🗑️ Revoke</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Create Key Modal -->
<div class="modal-overlay" id="createKeyModal">
    <div class="modal">
        <div class="modal-header"><span class="modal-title">➕ Create API Key</span><button class="modal-close" data-modal-close>✕</button></div>
        <form method="POST"><div class="modal-body">
            <?php csrfField(); ?><input type="hidden" name="action" value="create">
            <div class="form-group"><label>Key Name</label><input type="text" class="form-control" name="name" placeholder="My App API" required></div>
            <div class="form-group">
                <label>Assign to User</label>
                <select class="form-control" name="user_id" required>
                    <option value="">Select user...</option>
                    <?php foreach ($users as $u): ?>
                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['username']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Rate Limit (requests/min)</label><input type="number" class="form-control" name="rate_limit" value="60" min="1" max="1000"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
            <button type="submit" class="btn btn-primary">Generate Key</button>
        </div></form>
    </div>
</div>

<?php renderFooter(); ?>
