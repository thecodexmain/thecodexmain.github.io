<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';

requireRole('admin');

$success = $error = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create') {
            $username = preg_replace('/[^a-zA-Z0-9_]/', '', trim($_POST['username'] ?? ''));
            $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'] ?? '';
            $role = in_array($_POST['role'] ?? '', ['admin', 'user']) ? $_POST['role'] : 'user';
            $plan = sanitize($_POST['plan'] ?? 'basic');
            $status = in_array($_POST['status'] ?? '', ['active', 'suspended']) ? $_POST['status'] : 'active';
            $expires = sanitize($_POST['expires_at'] ?? '');

            if (strlen($username) < 3) { $error = 'Username must be at least 3 characters.'; }
            elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $error = 'Invalid email address.'; }
            elseif (strlen($password) < 6) { $error = 'Password must be at least 6 characters.'; }
            else {
                $users = readJson(DATA_PATH . 'users.json');
                foreach ($users as $u) {
                    if ($u['username'] === $username) { $error = 'Username already exists.'; break; }
                    if ($u['email'] === $email) { $error = 'Email already in use.'; break; }
                }
                if (!$error) {
                    $users[] = [
                        'id' => generateId('u'),
                        'username' => $username,
                        'email' => $email,
                        'password' => password_hash($password, PASSWORD_BCRYPT),
                        'role' => $role,
                        'plan' => $plan,
                        'status' => $status,
                        'created_at' => date('c'),
                        'expires_at' => $expires ?: date('c', strtotime('+30 days')),
                        'last_login' => null,
                        'login_ip' => null,
                        'failed_attempts' => 0,
                        'locked_until' => null,
                        '2fa_secret' => null,
                        'reseller_id' => null,
                        'credits' => 0
                    ];
                    writeJson(DATA_PATH . 'users.json', $users);
                    appLog('create_user', $_SESSION['username'], "Created user: {$username}");
                    $success = "User '{$username}' created successfully.";
                }
            }

        } elseif ($action === 'edit') {
            $id = sanitize($_POST['id'] ?? '');
            $users = readJson(DATA_PATH . 'users.json');
            $user = findById($users, $id);
            if (!$user) { $error = 'User not found.'; }
            else {
                $updates = [
                    'email' => filter_var(trim($_POST['email'] ?? $user['email']), FILTER_SANITIZE_EMAIL),
                    'plan' => sanitize($_POST['plan'] ?? $user['plan']),
                    'status' => in_array($_POST['status'] ?? '', ['active', 'suspended']) ? $_POST['status'] : $user['status'],
                    'expires_at' => sanitize($_POST['expires_at'] ?? $user['expires_at']),
                    'credits' => (int)($_POST['credits'] ?? $user['credits']),
                ];
                if (!empty($_POST['password'])) {
                    if (strlen($_POST['password']) < 6) { $error = 'Password must be at least 6 characters.'; }
                    else { $updates['password'] = password_hash($_POST['password'], PASSWORD_BCRYPT); }
                }
                if (!$error) {
                    updateById($users, $id, $updates);
                    writeJson(DATA_PATH . 'users.json', $users);
                    appLog('edit_user', $_SESSION['username'], "Edited user: {$user['username']}");
                    $success = "User updated successfully.";
                }
            }

        } elseif ($action === 'delete') {
            $id = sanitize($_POST['id'] ?? '');
            if ($id === 'u001') { $error = 'Cannot delete the main admin account.'; }
            else {
                $users = readJson(DATA_PATH . 'users.json');
                $user = findById($users, $id);
                if ($user) {
                    deleteById($users, $id);
                    writeJson(DATA_PATH . 'users.json', $users);
                    appLog('delete_user', $_SESSION['username'], "Deleted user: {$user['username']}");
                    $success = "User deleted.";
                } else { $error = 'User not found.'; }
            }

        } elseif ($action === 'toggle_status') {
            $id = sanitize($_POST['id'] ?? '');
            $users = readJson(DATA_PATH . 'users.json');
            $user = findById($users, $id);
            if ($user) {
                $newStatus = ($user['status'] ?? 'active') === 'active' ? 'suspended' : 'active';
                updateById($users, $id, ['status' => $newStatus]);
                writeJson(DATA_PATH . 'users.json', $users);
                appLog('toggle_user_status', $_SESSION['username'], "Set {$user['username']} to {$newStatus}");
                $success = "User status updated to {$newStatus}.";
            }
        }
    }
}

$users = readJson(DATA_PATH . 'users.json');
$plans = readJson(DATA_PATH . 'plans.json');

renderHead('Users');
renderSidebar('admin', 'users');
renderTopbar('Manage Users');
?>

<?php if ($success) renderAlert('success', $success); ?>
<?php if ($error) renderAlert('danger', $error); ?>

<div class="section-header">
    <h2 class="section-title">All Users (<?= count($users) ?>)</h2>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <div class="search-bar"><input type="text" class="form-control" id="tableSearch" placeholder="Search users..."></div>
        <button class="btn btn-primary" onclick="openModal('createUserModal')">➕ Add User</button>
    </div>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>User</th><th>Role</th><th>Plan</th><th>Status</th>
                    <th>Expires</th><th>Last Login</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($users)): ?>
            <tr><td colspan="7"><div class="empty-state"><span class="empty-icon">👥</span><h3>No users yet</h3></div></td></tr>
            <?php else: foreach ($users as $u): ?>
            <tr>
                <td>
                    <div style="font-weight:600;"><?= htmlspecialchars($u['username']) ?></div>
                    <div class="text-small text-muted"><?= htmlspecialchars($u['email']) ?></div>
                </td>
                <td><span class="badge-status badge-info"><?= ucfirst($u['role']) ?></span></td>
                <td><?= ucfirst($u['plan'] ?? 'basic') ?></td>
                <td><?= getStatusBadge($u['status'] ?? 'active') ?></td>
                <td class="text-small text-muted">
                    <?php
                    if (!empty($u['expires_at'])) {
                        $exp = strtotime($u['expires_at']);
                        $expired = $exp < time();
                        echo '<span style="color:' . ($expired ? 'var(--danger)' : 'inherit') . ';">' . date('M j, Y', $exp) . ($expired ? ' ⚠️' : '') . '</span>';
                    } else { echo '—'; }
                    ?>
                </td>
                <td class="text-small text-muted"><?= $u['last_login'] ? timeAgo($u['last_login']) : 'Never' ?></td>
                <td>
                    <div style="display:flex;gap:4px;flex-wrap:wrap;">
                        <button class="btn btn-sm btn-secondary" onclick="editUser(<?= htmlspecialchars(json_encode($u)) ?>)">✏️</button>
                        <form method="POST" style="display:inline;">
                            <?php csrfField(); ?>
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                            <button type="submit" class="btn btn-sm <?= ($u['status'] ?? 'active') === 'active' ? 'btn-warning' : 'btn-success' ?>" title="Toggle Status">
                                <?= ($u['status'] ?? 'active') === 'active' ? '🔒' : '🔓' ?>
                            </button>
                        </form>
                        <?php if ($u['id'] !== 'u001'): ?>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete user <?= htmlspecialchars(addslashes($u['username'])) ?>? This cannot be undone.')">
                            <?php csrfField(); ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger">🗑️</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Create User Modal -->
<div class="modal-overlay" id="createUserModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">➕ Create New User</span>
            <button class="modal-close" data-modal-close>✕</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <?php csrfField(); ?>
                <input type="hidden" name="action" value="create">
                <div class="form-row">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" class="form-control" name="username" placeholder="johndoe" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" class="form-control" name="email" placeholder="john@example.com" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" class="form-control" name="password" placeholder="Min 6 characters" required>
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <select class="form-control" name="role">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Plan</label>
                        <select class="form-control" name="plan">
                            <?php foreach ($plans as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select class="form-control" name="status">
                            <option value="active">Active</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Expires At</label>
                    <input type="datetime-local" class="form-control" name="expires_at" value="<?= date('Y-m-d\TH:i', strtotime('+30 days')) ?>">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Create User</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal-overlay" id="editUserModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">✏️ Edit User</span>
            <button class="modal-close" data-modal-close>✕</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <?php csrfField(); ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="editUserId">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" class="form-control" id="editUsername" readonly style="opacity:0.7;">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" class="form-control" name="email" id="editEmail" required>
                    </div>
                    <div class="form-group">
                        <label>New Password <small class="text-muted">(leave blank to keep)</small></label>
                        <input type="password" class="form-control" name="password" placeholder="New password...">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Plan</label>
                        <select class="form-control" name="plan" id="editPlan">
                            <?php foreach ($plans as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select class="form-control" name="status" id="editStatus">
                            <option value="active">Active</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Expires At</label>
                        <input type="datetime-local" class="form-control" name="expires_at" id="editExpires">
                    </div>
                    <div class="form-group">
                        <label>Credits</label>
                        <input type="number" class="form-control" name="credits" id="editCredits" min="0">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<?php renderFooter(); ?>
<script>
function editUser(u) {
    document.getElementById('editUserId').value = u.id;
    document.getElementById('editUsername').value = u.username;
    document.getElementById('editEmail').value = u.email;
    document.getElementById('editPlan').value = u.plan || 'basic';
    document.getElementById('editStatus').value = u.status || 'active';
    document.getElementById('editCredits').value = u.credits || 0;
    if (u.expires_at) {
        const d = new Date(u.expires_at);
        document.getElementById('editExpires').value = d.toISOString().slice(0,16);
    }
    openModal('editUserModal');
}
</script>
