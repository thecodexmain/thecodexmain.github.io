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

        if ($action === 'create') {
            $username = preg_replace('/[^a-zA-Z0-9_]/', '', trim($_POST['username'] ?? ''));
            $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'] ?? '';
            $credits = (int)($_POST['credits'] ?? 0);

            if (strlen($username) < 3) { $error = 'Username must be at least 3 characters.'; }
            elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $error = 'Invalid email.'; }
            elseif (strlen($password) < 6) { $error = 'Password must be at least 6 characters.'; }
            else {
                $resellers = readJson(DATA_PATH . 'resellers.json');
                foreach ($resellers as $r) {
                    if ($r['username'] === $username || $r['email'] === $email) { $error = 'Username or email already exists.'; break; }
                }
                if (!$error) {
                    $resellers[] = [
                        'id' => generateId('r'),
                        'username' => $username,
                        'email' => $email,
                        'password' => password_hash($password, PASSWORD_BCRYPT),
                        'role' => 'reseller',
                        'status' => 'active',
                        'credits' => $credits,
                        'created_at' => date('c'),
                        'last_login' => null,
                        'login_ip' => null,
                        'failed_attempts' => 0,
                        'locked_until' => null,
                    ];
                    writeJson(DATA_PATH . 'resellers.json', $resellers);
                    appLog('create_reseller', $_SESSION['username'], "Created reseller: {$username}");
                    $success = "Reseller '{$username}' created.";
                }
            }

        } elseif ($action === 'edit') {
            $id = sanitize($_POST['id'] ?? '');
            $resellers = readJson(DATA_PATH . 'resellers.json');
            $updates = [
                'email' => filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL),
                'status' => in_array($_POST['status'] ?? '', ['active', 'suspended']) ? $_POST['status'] : 'active',
                'credits' => (int)($_POST['credits'] ?? 0),
            ];
            if (!empty($_POST['password'])) {
                $updates['password'] = password_hash($_POST['password'], PASSWORD_BCRYPT);
            }
            updateById($resellers, $id, $updates);
            writeJson(DATA_PATH . 'resellers.json', $resellers);
            $success = 'Reseller updated.';

        } elseif ($action === 'delete') {
            $id = sanitize($_POST['id'] ?? '');
            $resellers = readJson(DATA_PATH . 'resellers.json');
            $r = findById($resellers, $id);
            if ($r) {
                deleteById($resellers, $id);
                writeJson(DATA_PATH . 'resellers.json', $resellers);
                appLog('delete_reseller', $_SESSION['username'], "Deleted reseller: {$r['username']}");
                $success = 'Reseller deleted.';
            }

        } elseif ($action === 'add_credits') {
            $id = sanitize($_POST['id'] ?? '');
            $amount = (int)($_POST['amount'] ?? 0);
            $resellers = readJson(DATA_PATH . 'resellers.json');
            $r = findById($resellers, $id);
            if ($r) {
                updateById($resellers, $id, ['credits' => max(0, ($r['credits'] ?? 0) + $amount)]);
                writeJson(DATA_PATH . 'resellers.json', $resellers);
                $success = "Credits updated for {$r['username']}.";
            }
        }
    }
}

$resellers = readJson(DATA_PATH . 'resellers.json');

renderHead('Resellers');
renderSidebar('admin', 'resellers');
renderTopbar('Manage Resellers');
?>

<?php if ($success) renderAlert('success', $success); ?>
<?php if ($error) renderAlert('danger', $error); ?>

<div class="section-header">
    <h2 class="section-title">Resellers (<?= count($resellers) ?>)</h2>
    <div style="display:flex;gap:8px;">
        <div class="search-bar"><input type="text" class="form-control" id="tableSearch" placeholder="Search..."></div>
        <button class="btn btn-primary" onclick="openModal('createResellerModal')">➕ Add Reseller</button>
    </div>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Reseller</th><th>Status</th><th>Credits</th><th>Last Login</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($resellers)): ?>
            <tr><td colspan="5"><div class="empty-state"><span class="empty-icon">🤝</span><h3>No resellers yet</h3></div></td></tr>
            <?php else: foreach ($resellers as $r): ?>
            <tr>
                <td>
                    <div style="font-weight:600;"><?= htmlspecialchars($r['username']) ?></div>
                    <div class="text-small text-muted"><?= htmlspecialchars($r['email']) ?></div>
                </td>
                <td><?= getStatusBadge($r['status'] ?? 'active') ?></td>
                <td><strong><?= number_format($r['credits'] ?? 0) ?></strong> credits</td>
                <td class="text-small text-muted"><?= $r['last_login'] ? timeAgo($r['last_login']) : 'Never' ?></td>
                <td>
                    <div style="display:flex;gap:4px;">
                        <button class="btn btn-sm btn-secondary" onclick="editReseller(<?= htmlspecialchars(json_encode($r)) ?>)">✏️ Edit</button>
                        <button class="btn btn-sm btn-success" onclick="addCredits('<?= $r['id'] ?>', '<?= htmlspecialchars(addslashes($r['username'])) ?>')">💰 Credits</button>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this reseller?')">
                            <?php csrfField(); ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
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

<!-- Create Modal -->
<div class="modal-overlay" id="createResellerModal">
    <div class="modal">
        <div class="modal-header"><span class="modal-title">➕ Create Reseller</span><button class="modal-close" data-modal-close>✕</button></div>
        <form method="POST"><div class="modal-body">
            <?php csrfField(); ?><input type="hidden" name="action" value="create">
            <div class="form-row">
                <div class="form-group"><label>Username</label><input type="text" class="form-control" name="username" required></div>
                <div class="form-group"><label>Email</label><input type="email" class="form-control" name="email" required></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Password</label><input type="password" class="form-control" name="password" required></div>
                <div class="form-group"><label>Starting Credits</label><input type="number" class="form-control" name="credits" value="0" min="0"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
            <button type="submit" class="btn btn-primary">Create Reseller</button>
        </div></form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editResellerModal">
    <div class="modal">
        <div class="modal-header"><span class="modal-title">✏️ Edit Reseller</span><button class="modal-close" data-modal-close>✕</button></div>
        <form method="POST"><div class="modal-body">
            <?php csrfField(); ?><input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="editResId">
            <div class="form-group"><label>Email</label><input type="email" class="form-control" name="email" id="editResEmail" required></div>
            <div class="form-row">
                <div class="form-group"><label>New Password</label><input type="password" class="form-control" name="password" placeholder="Leave blank to keep"></div>
                <div class="form-group"><label>Status</label><select class="form-control" name="status" id="editResStatus"><option value="active">Active</option><option value="suspended">Suspended</option></select></div>
            </div>
            <div class="form-group"><label>Credits</label><input type="number" class="form-control" name="credits" id="editResCredits" min="0"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
            <button type="submit" class="btn btn-primary">Save</button>
        </div></form>
    </div>
</div>

<!-- Add Credits Modal -->
<div class="modal-overlay" id="creditsModal">
    <div class="modal">
        <div class="modal-header"><span class="modal-title">💰 Add/Remove Credits</span><button class="modal-close" data-modal-close>✕</button></div>
        <form method="POST"><div class="modal-body">
            <?php csrfField(); ?><input type="hidden" name="action" value="add_credits">
            <input type="hidden" name="id" id="creditsResId">
            <p class="text-muted mb-3">Reseller: <strong id="creditsResName"></strong></p>
            <div class="form-group">
                <label>Amount (use negative to deduct)</label>
                <input type="number" class="form-control" name="amount" placeholder="e.g. 50 or -10" required>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
            <button type="submit" class="btn btn-success">Update Credits</button>
        </div></form>
    </div>
</div>

<?php renderFooter(); ?>
<script>
function editReseller(r) {
    document.getElementById('editResId').value = r.id;
    document.getElementById('editResEmail').value = r.email;
    document.getElementById('editResStatus').value = r.status || 'active';
    document.getElementById('editResCredits').value = r.credits || 0;
    openModal('editResellerModal');
}
function addCredits(id, name) {
    document.getElementById('creditsResId').value = id;
    document.getElementById('creditsResName').textContent = name;
    openModal('creditsModal');
}
</script>
