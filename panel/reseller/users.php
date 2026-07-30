<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';

requireRole('admin', 'reseller');

$resellerId = $_SESSION['user_id'];
$resellers = readJson(DATA_PATH . 'resellers.json');
$me = findById($resellers, $resellerId);
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST[CSRF_TOKEN_NAME] ?? '')) { $error = 'Invalid token.'; }
    else {
        $action = $_POST['action'] ?? '';
        $users = readJson(DATA_PATH . 'users.json');
        $plans = readJson(DATA_PATH . 'plans.json');

        if ($action === 'create') {
            $username = sanitize($_POST['username'] ?? '');
            $email = sanitize($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $plan = sanitize($_POST['plan'] ?? 'basic');
            $planData = findById($plans, null, 'name', $plan);
            $cost = (int)($planData['reseller_price'] ?? 10);

            if (empty($username) || empty($email) || strlen($password) < 6) {
                $error = 'All fields required (password min 6 chars).';
            } elseif (($me['credits'] ?? 0) < $cost) {
                $error = "Not enough credits. Creating a {$plan} user costs {$cost} credits.";
            } else {
                foreach ($users as $u) {
                    if ($u['username'] === $username || $u['email'] === $email) { $error = 'Username or email taken.'; break; }
                }
                if (!$error) {
                    $newUser = ['id' => generateId('u'), 'username' => $username, 'email' => $email, 'password' => password_hash($password, PASSWORD_BCRYPT),
                        'role' => 'user', 'plan' => $plan, 'status' => 'active', 'reseller_id' => $resellerId,
                        'credits' => 0, 'created_at' => date('c'), 'last_login' => null, 'failed_logins' => 0, 'locked_until' => null,
                        'sites' => [], 'expiry' => date('c', strtotime('+30 days'))];
                    $users[] = $newUser;
                    writeJson(DATA_PATH . 'users.json', $users);
                    foreach ($resellers as &$r) { if ($r['id'] === $resellerId) { $r['credits'] -= $cost; break; } } unset($r);
                    writeJson(DATA_PATH . 'resellers.json', $resellers);
                    $me['credits'] = ($me['credits'] ?? 0) - $cost;
                    $success = "User '{$username}' created. {$cost} credits deducted. Balance: {$me['credits']}";
                }
            }
        } elseif ($action === 'delete') {
            $id = sanitize($_POST['id'] ?? '');
            if ($id) {
                $target = findById($users, $id);
                if ($target && ($target['reseller_id'] ?? '') === $resellerId) {
                    deleteById($users, $id);
                    writeJson(DATA_PATH . 'users.json', $users);
                    $success = 'User deleted.';
                } else $error = 'User not found or not yours.';
            }
        } elseif ($action === 'toggle') {
            $id = sanitize($_POST['id'] ?? '');
            foreach ($users as &$u) {
                if ($u['id'] === $id && ($u['reseller_id'] ?? '') === $resellerId) {
                    $u['status'] = $u['status'] === 'active' ? 'suspended' : 'active'; break;
                }
            } unset($u);
            writeJson(DATA_PATH . 'users.json', $users);
            $success = 'User status updated.';
        }
    }
}

$users = readJson(DATA_PATH . 'users.json');
$myUsers = array_values(array_filter($users, fn($u) => ($u['reseller_id'] ?? '') === $resellerId));
$plans = readJson(DATA_PATH . 'plans.json');

renderHead('My Users');
renderSidebar('reseller', 'users');
renderTopbar('My Users');
?>

<?php if ($success) renderAlert('success', $success); ?>
<?php if ($error) renderAlert('danger', $error); ?>

<div class="alert alert-info">
    💰 Credits: <strong><?= number_format($me['credits'] ?? 0) ?></strong> — Each user creation costs credits based on plan pricing.
</div>

<div class="section-header">
    <h2 class="section-title">Users (<?= count($myUsers) ?>)</h2>
    <button class="btn btn-primary" onclick="openModal('createUserModal')">➕ Create User</button>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>User</th><th>Plan</th><th>Status</th><th>Expiry</th><th>Created</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($myUsers)): ?>
            <tr><td colspan="6"><div class="empty-state"><span class="empty-icon">👥</span><h3>No users yet</h3></div></td></tr>
            <?php else: foreach ($myUsers as $u): ?>
            <tr>
                <td><div style="font-weight:600;"><?= htmlspecialchars($u['username']) ?></div><div class="text-small text-muted"><?= htmlspecialchars($u['email']) ?></div></td>
                <td><?= ucfirst($u['plan'] ?? 'basic') ?></td>
                <td><?= getStatusBadge($u['status'] ?? 'active') ?></td>
                <td class="text-small"><?= !empty($u['expiry']) ? date('M j, Y', strtotime($u['expiry'])) : '—' ?></td>
                <td class="text-small text-muted"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                <td>
                    <div style="display:flex;gap:4px;">
                        <form method="POST" style="display:inline;">
                            <?php csrfField(); ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= $u['id'] ?>">
                            <button class="btn btn-sm btn-warning"><?= $u['status'] === 'active' ? '⏸️ Suspend' : '▶️ Activate' ?></button>
                        </form>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete user?')">
                            <?php csrfField(); ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $u['id'] ?>">
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

<!-- Create User Modal -->
<div class="modal-overlay" id="createUserModal">
    <div class="modal">
        <div class="modal-header"><span class="modal-title">➕ Create User</span><button class="modal-close" data-modal-close>✕</button></div>
        <form method="POST"><div class="modal-body">
            <?php csrfField(); ?><input type="hidden" name="action" value="create">
            <div class="form-group"><label>Username</label><input type="text" class="form-control" name="username" required></div>
            <div class="form-group"><label>Email</label><input type="email" class="form-control" name="email" required></div>
            <div class="form-group"><label>Password</label><input type="password" class="form-control" name="password" minlength="6" required></div>
            <div class="form-group">
                <label>Plan</label>
                <select class="form-control" name="plan">
                    <?php foreach ($plans as $p): ?>
                    <option value="<?= htmlspecialchars($p['name']) ?>">
                        <?= htmlspecialchars(ucfirst($p['name'])) ?> — <?= $p['reseller_price'] ?? $p['price'] ?> credits
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="alert alert-warning">Available Credits: <strong><?= number_format($me['credits'] ?? 0) ?></strong></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
            <button type="submit" class="btn btn-primary">Create &amp; Deduct Credits</button>
        </div></form>
    </div>
</div>

<?php renderFooter(); ?>
