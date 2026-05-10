<?php
require_once __DIR__ . '/includes/auth.php';
primeRequireLogin();
primeSyncDataState();

$currentUser = primeGetCurrentUser();
$baseUrl = primeGetBaseUrl();

$allEndUsers = primeLoadData('end_users');
$editId = $_GET['edit'] ?? '';
$editingUser = null;
foreach (primeFilterOwnedRecords($allEndUsers, $currentUser) as $candidate) {
    if (($candidate['id'] ?? '') === $editId) {
        $editingUser = $candidate;
        break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (primePortalLockedFor($currentUser)) {
        primeSetFlash('error', 'Maintenance mode is enabled. Reseller user updates are temporarily disabled.');
        header('Location: ' . $baseUrl . '/users.php');
        exit;
    }

    $action = $_POST['action'] ?? '';
    $endUsers = primeLoadData('end_users');
    $keys = primeLoadData('keys');
    $accessibleUsers = primeFilterOwnedRecords($endUsers, $currentUser);
    $accessibleUserIds = array_column($accessibleUsers, 'id');
    $accessibleKeys = primeFilterOwnedRecords($keys, $currentUser);
    $accessibleKeyIds = array_column($accessibleKeys, 'id');

    if ($action === 'delete_user') {
        $userId = $_POST['user_id'] ?? '';
        if (in_array($userId, $accessibleUserIds, true)) {
            foreach ($keys as &$key) {
                if (($key['assigned_to'] ?? '') === $userId) {
                    $key['assigned_to'] = '';
                    $key['used_at'] = '';
                    $key['status'] = !empty($key['expires_at']) && strtotime($key['expires_at']) < time() ? 'expired' : 'unused';
                }
            }
            unset($key);
            $endUsers = array_values(array_filter($endUsers, fn($user) => ($user['id'] ?? '') !== $userId));
            primeSaveData('end_users', $endUsers);
            primeSaveData('keys', $keys);
            primeAddAuditLog($currentUser, 'delete_user', 'Deleted end user ' . $userId . '.');
            primeSetFlash('success', 'User deleted successfully.');
        }
        header('Location: ' . $baseUrl . '/users.php');
        exit;
    }

    if ($action === 'save_user') {
        $recordId = $_POST['record_id'] ?? '';
        $name = primeRaw($_POST['name'] ?? '');
        $username = primeRaw($_POST['username'] ?? '');
        $email = primeRaw($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $status = $_POST['status'] ?? 'active';
                $rawReferralCode = primeRaw($_POST['referred_by_code'] ?? '');
                $referredByCode = $rawReferralCode !== '' ? strtoupper(primeSlug($rawReferralCode)) : '';
        $selectedKeyId = $_POST['key_id'] ?? '';
        $expiryAt = trim((string)($_POST['expiry_at'] ?? ''));
        $status = in_array($status, ['active', 'inactive'], true) ? $status : 'active';

        if ($name === '' || $username === '') {
            primeSetFlash('error', 'Name and username are required.');
            header('Location: ' . $baseUrl . '/users.php' . ($recordId ? '?edit=' . urlencode($recordId) : ''));
            exit;
        }

        foreach ($endUsers as $user) {
            if (($user['username'] ?? '') === $username && ($user['id'] ?? '') !== $recordId) {
                primeSetFlash('error', 'Username is already in use.');
                header('Location: ' . $baseUrl . '/users.php' . ($recordId ? '?edit=' . urlencode($recordId) : ''));
                exit;
            }
        }

        $referralOwner = $referredByCode ? primeFindEndUserByReferralCode($referredByCode) : null;
        if ($referredByCode && (!$referralOwner || !primeCanUseReferralCode($referredByCode, $recordId))) {
            primeSetFlash('error', 'Referral code is invalid for the current referral mode.');
            header('Location: ' . $baseUrl . '/users.php' . ($recordId ? '?edit=' . urlencode($recordId) : ''));
            exit;
        }

        $targetIndex = null;
        $record = null;
        foreach ($endUsers as $index => $user) {
            if (($user['id'] ?? '') === $recordId) {
                $targetIndex = $index;
                $record = $user;
                break;
            }
        }

        if ($recordId && (!in_array($recordId, $accessibleUserIds, true) || $targetIndex === null)) {
            primeSetFlash('error', 'You cannot edit that user.');
            header('Location: ' . $baseUrl . '/users.php');
            exit;
        }

        if (!$recordId) {
            $record = [
                'id' => primeGenerateId('USR'),
                'created_at' => primeNow(),
                'owner_user_id' => $currentUser['id'],
            ];
        }

        if ($referredByCode !== '' && ($record['referral_code'] ?? '') === $referredByCode) {
            primeSetFlash('error', 'Users cannot register with their own referral code.');
            header('Location: ' . $baseUrl . '/users.php' . ($recordId ? '?edit=' . urlencode($recordId) : ''));
            exit;
        }

        $oldKeyId = $record['key_id'] ?? '';
        $newKeyId = $selectedKeyId;
        if ($newKeyId !== '' && !in_array($newKeyId, $accessibleKeyIds, true)) {
            primeSetFlash('error', 'Selected key is outside your inventory.');
            header('Location: ' . $baseUrl . '/users.php' . ($recordId ? '?edit=' . urlencode($recordId) : ''));
            exit;
        }

        foreach ($keys as $key) {
            if (($key['id'] ?? '') === $newKeyId) {
                $assignedTo = $key['assigned_to'] ?? '';
                if ($assignedTo && $assignedTo !== ($record['id'] ?? '')) {
                    primeSetFlash('error', 'Selected key is already assigned to another user.');
                    header('Location: ' . $baseUrl . '/users.php' . ($recordId ? '?edit=' . urlencode($recordId) : ''));
                    exit;
                }
                if (($key['status'] ?? '') === 'expired') {
                    primeSetFlash('error', 'Expired keys cannot be assigned.');
                    header('Location: ' . $baseUrl . '/users.php' . ($recordId ? '?edit=' . urlencode($recordId) : ''));
                    exit;
                }
            }
        }

        foreach ($keys as &$key) {
            if (($key['id'] ?? '') === $oldKeyId && $oldKeyId !== $newKeyId) {
                $key['assigned_to'] = '';
                $key['used_at'] = '';
                $key['status'] = !empty($key['expires_at']) && strtotime($key['expires_at']) < time() ? 'expired' : 'unused';
            }
        }
        unset($key);

        $record['name'] = $name;
        $record['username'] = $username;
        $record['email'] = $email;
        $record['status'] = $status;
        $record['referred_by_code'] = $referredByCode;
        $record['referral_code'] = $record['referral_code'] ?? primeCreateReferralCode($name);
        $record['updated_at'] = primeNow();
        $record['key_id'] = $newKeyId;

        if ($password !== '') {
            $record['password'] = password_hash($password, PASSWORD_DEFAULT);
        } elseif (empty($record['password'])) {
            $record['password'] = password_hash('demo123', PASSWORD_DEFAULT);
        }

        $chosenExpiry = $expiryAt !== '' ? date('Y-m-d H:i:s', strtotime($expiryAt . ' 00:00:00')) : '';
        if ($newKeyId !== '') {
            foreach ($keys as &$key) {
                if (($key['id'] ?? '') === $newKeyId) {
                    $key['assigned_to'] = $record['id'];
                    $key['used_at'] = $key['used_at'] ?: primeNow();
                    $key['status'] = !empty($key['expires_at']) && strtotime($key['expires_at']) < time() ? 'expired' : 'used';
                    if ($chosenExpiry === '' || strtotime($chosenExpiry) > strtotime($key['expires_at'])) {
                        $chosenExpiry = $key['expires_at'];
                    }
                    break;
                }
            }
            unset($key);
        }

        $record['expiry_at'] = $chosenExpiry ?: date('Y-m-d H:i:s', strtotime('+30 days'));

        if ($targetIndex === null) {
            $endUsers[] = $record;
            primeAddAuditLog($currentUser, 'create_user', 'Registered end user ' . $record['username'] . '.', ['user_id' => $record['id']]);
            primeSetFlash('success', 'User created successfully.');
        } else {
            $endUsers[$targetIndex] = $record;
            primeAddAuditLog($currentUser, 'update_user', 'Updated end user ' . $record['username'] . '.', ['user_id' => $record['id']]);
            primeSetFlash('success', 'User updated successfully.');
        }

        primeSaveData('end_users', $endUsers);
        primeSaveData('keys', $keys);
        header('Location: ' . $baseUrl . '/users.php');
        exit;
    }
}

$visibleUsers = primeFilterOwnedRecords(primeLoadData('end_users'), $currentUser);
$availableKeys = array_values(array_filter(primeFilterOwnedRecords(primeLoadData('keys'), $currentUser), function ($key) use ($editingUser) {
    if (($key['status'] ?? '') === 'expired') {
        return false;
    }
    if (empty($key['assigned_to'])) {
        return true;
    }
    return ($editingUser['id'] ?? '') !== '' && ($key['assigned_to'] ?? '') === ($editingUser['id'] ?? '');
}));
$pageTitle = 'Users';
include __DIR__ . '/includes/header.php';
?>
<div class="wrapper">
<?php include __DIR__ . '/includes/sidebar.php'; ?>
<div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="bi bi-people text-theme"></i> User Management</h2>
            <p class="text-muted mb-0">Register users, apply referral rules, set expiry dates, and assign keys.</p>
        </div>
        <?php if ($editingUser): ?><a href="<?php echo $baseUrl; ?>/users.php" class="btn btn-outline-secondary"><i class="bi bi-x-circle"></i> Cancel Edit</a><?php endif; ?>
    </div>
    <?php echo primeRenderFlash(); ?>

    <div class="row g-3 mb-4">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><i class="bi bi-person-plus"></i> <?php echo $editingUser ? 'Edit User' : 'Add User'; ?></div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="save_user">
                        <input type="hidden" name="record_id" value="<?php echo htmlspecialchars($editingUser['id'] ?? ''); ?>">
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label">Name</label><input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($editingUser['name'] ?? ''); ?>" required></div>
                            <div class="col-md-6"><label class="form-label">Username</label><input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($editingUser['username'] ?? ''); ?>" required></div>
                            <div class="col-12"><label class="form-label">Email</label><input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($editingUser['email'] ?? ''); ?>"></div>
                            <div class="col-md-6"><label class="form-label">Password <?php echo $editingUser ? '(leave blank to keep)' : ''; ?></label><input type="password" class="form-control" name="password"></div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="active" <?php echo (($editingUser['status'] ?? 'active') === 'active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo (($editingUser['status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-6"><label class="form-label">User Expiry Time</label><input type="date" class="form-control" name="expiry_at" value="<?php echo !empty($editingUser['expiry_at']) ? htmlspecialchars(date('Y-m-d', strtotime($editingUser['expiry_at']))) : ''; ?>"></div>
                            <div class="col-md-6"><label class="form-label">Referral Used</label><input type="text" class="form-control" name="referred_by_code" value="<?php echo htmlspecialchars($editingUser['referred_by_code'] ?? ''); ?>" placeholder="Optional referral code"></div>
                            <div class="col-12">
                                <label class="form-label">Assign Key</label>
                                <select class="form-select" name="key_id">
                                    <option value="">No key assigned</option>
                                    <?php foreach ($availableKeys as $key): ?>
                                        <option value="<?php echo htmlspecialchars($key['id']); ?>" <?php echo (($editingUser['key_id'] ?? '') === ($key['id'] ?? '')) ? 'selected' : ''; ?>><?php echo htmlspecialchars($key['key_code']); ?> (<?php echo htmlspecialchars(primeFormatDate($key['expires_at'] ?? '')); ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php if ($editingUser): ?>
                                <div class="col-12 small text-muted">Referral code: <strong><?php echo htmlspecialchars($editingUser['referral_code'] ?? '-'); ?></strong></div>
                            <?php endif; ?>
                            <div class="col-12"><button type="submit" class="btn btn-primary px-4"><i class="bi bi-save"></i> <?php echo $editingUser ? 'Update User' : 'Create User'; ?></button></div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header"><i class="bi bi-info-circle"></i> Referral Policy</div>
                <div class="card-body">
                    <p class="mb-2">Current mode: <strong><?php echo (primeGetSettings()['referral_mode'] ?? 'single_use') === 'unlimited' ? 'One referral can register unlimited users' : 'One referral can register one user'; ?></strong></p>
                    <p class="text-muted mb-0">User expiry follows the selected date or the assigned key expiry, whichever ends sooner.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><i class="bi bi-table"></i> Registered Users</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>User</th><th>Referral</th><th>Used Referral</th><th>Key</th><th>Expiry</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php if (empty($visibleUsers)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No users yet.</td></tr>
                    <?php else: foreach (array_reverse($visibleUsers) as $user): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?php echo htmlspecialchars($user['name']); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars($user['username']); ?></small>
                            </td>
                            <td><span class="code-pill"><?php echo htmlspecialchars($user['referral_code'] ?? '-'); ?></span></td>
                            <td><?php echo htmlspecialchars($user['referred_by_code'] ?: '-'); ?></td>
                            <td><?php echo htmlspecialchars(($user['key_id'] ?? '') ?: '-'); ?></td>
                            <td><?php echo htmlspecialchars(primeFormatDate($user['expiry_at'] ?? '')); ?></td>
                            <td><span class="badge bg-<?php echo primeStatusBadgeClass($user['status'] ?? 'active'); ?>"><?php echo htmlspecialchars(ucfirst($user['status'] ?? 'active')); ?></span></td>
                            <td>
                                <a href="<?php echo $baseUrl; ?>/users.php?edit=<?php echo urlencode($user['id']); ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['id']); ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Delete this user and free the linked key?"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
