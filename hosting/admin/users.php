<?php
require_once __DIR__ . '/../includes/auth.php';
hostingRequireRole(['admin']);
$baseUrl = hostingGetBaseUrl();

$users = hostingLoadData('users');
$me = hostingCurrentUser();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    hostingVerifyCsrf();
    $action = (string)($_POST['action'] ?? '');
    $userId = (string)($_POST['user_id'] ?? '');

    $idx = null;
    foreach ($users as $i => $u) {
        if (($u['id'] ?? '') === $userId) { $idx = $i; break; }
    }
    if ($idx === null) {
        $error = 'User not found.';
    } else {
        if ($action === 'toggle_status') {
            if (($users[$idx]['id'] ?? '') === ($me['id'] ?? '')) {
                $error = 'You cannot disable your own account.';
            } else {
                $users[$idx]['status'] = (($users[$idx]['status'] ?? 'active') === 'active') ? 'disabled' : 'active';
                hostingSaveData('users', $users);
                hostingSetFlash('success', 'User updated.');
                header('Location: ' . hostingGetBaseUrl() . '/admin/users.php');
                exit;
            }
        }
        if ($action === 'set_role') {
            $role = (string)($_POST['role'] ?? 'user');
            if (!in_array($role, ['user', 'admin'], true)) $role = 'user';
            if (($users[$idx]['id'] ?? '') === ($me['id'] ?? '') && $role !== 'admin') {
                $error = 'You cannot remove your own admin role.';
            } else {
                $users[$idx]['role'] = $role;
                hostingSaveData('users', $users);
                hostingSetFlash('success', 'Role updated.');
                header('Location: ' . hostingGetBaseUrl() . '/admin/users.php');
                exit;
            }
        }
        if ($action === 'reset_password') {
            $newPass = (string)($_POST['new_password'] ?? '');
            if (strlen($newPass) < 8) {
                $error = 'Password must be at least 8 characters.';
            } else {
                $users[$idx]['password'] = password_hash($newPass, PASSWORD_DEFAULT);
                hostingSaveData('users', $users);
                hostingSetFlash('success', 'Password reset.');
                header('Location: ' . hostingGetBaseUrl() . '/admin/users.php');
                exit;
            }
        }
    }
}
?>
<?php require __DIR__ . '/../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h4 fw-bold mb-1">Users</h1>
        <div class="text-muted">Enable/disable accounts and set roles.</div>
    </div>
    <a class="btn btn-sm btn-outline-secondary" href="<?php echo $baseUrl; ?>/admin/dashboard.php"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
            <tr>
                <th>User</th>
                <th>Email</th>
                <th>Username</th>
                <th>Role</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td class="fw-semibold"><?php echo htmlspecialchars($u['name'] ?? ''); ?></td>
                    <td class="text-muted"><?php echo htmlspecialchars($u['email'] ?? ''); ?></td>
                    <td class="text-muted"><?php echo htmlspecialchars($u['username'] ?? ''); ?></td>
                    <td><span class="badge text-bg-<?php echo ($u['role'] ?? 'user') === 'admin' ? 'primary' : 'secondary'; ?>"><?php echo htmlspecialchars($u['role'] ?? 'user'); ?></span></td>
                    <td><span class="badge text-bg-<?php echo ($u['status'] ?? 'active') === 'active' ? 'success' : 'danger'; ?>"><?php echo htmlspecialchars($u['status'] ?? 'active'); ?></span></td>
                    <td class="text-end">
                        <div class="d-flex justify-content-end gap-2 flex-wrap">
                            <form method="post" action="" class="m-0">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(hostingCsrfToken()); ?>">
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($u['id'] ?? ''); ?>">
                                <button class="btn btn-sm btn-outline-<?php echo ($u['status'] ?? 'active') === 'active' ? 'danger' : 'success'; ?>" type="submit">
                                    <?php echo ($u['status'] ?? 'active') === 'active' ? 'Disable' : 'Enable'; ?>
                                </button>
                            </form>
                            <form method="post" action="" class="m-0 d-flex gap-1">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(hostingCsrfToken()); ?>">
                                <input type="hidden" name="action" value="set_role">
                                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($u['id'] ?? ''); ?>">
                                <select class="form-select form-select-sm" name="role" style="max-width:120px">
                                    <option value="user" <?php echo ($u['role'] ?? 'user') === 'user' ? 'selected' : ''; ?>>user</option>
                                    <option value="admin" <?php echo ($u['role'] ?? 'user') === 'admin' ? 'selected' : ''; ?>>admin</option>
                                </select>
                                <button class="btn btn-sm btn-outline-primary" type="submit">Save</button>
                            </form>
                        </div>
                        <details class="mt-2">
                            <summary class="small text-muted">Reset password</summary>
                            <form method="post" action="" class="row g-2 mt-2">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(hostingCsrfToken()); ?>">
                                <input type="hidden" name="action" value="reset_password">
                                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($u['id'] ?? ''); ?>">
                                <div class="col-8">
                                    <input class="form-control form-control-sm" name="new_password" type="password" placeholder="New password (min 8)" required>
                                </div>
                                <div class="col-4 d-grid">
                                    <button class="btn btn-sm btn-outline-secondary" type="submit">Reset</button>
                                </div>
                            </form>
                        </details>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
