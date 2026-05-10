<?php
require_once __DIR__ . '/includes/auth.php';
primeRequireRole('owner');
primeSyncDataState();

$currentUser = primeGetCurrentUser();
$baseUrl = primeGetBaseUrl();
$users = primeLoadData('users');
$editId = $_GET['edit'] ?? '';
$editingReseller = null;
foreach ($users as $user) {
    if (($user['role'] ?? '') === 'reseller' && ($user['id'] ?? '') === $editId) {
        $editingReseller = $user;
        break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $users = primeLoadData('users');

    if ($action === 'save_reseller') {
        $recordId = $_POST['record_id'] ?? '';
        $name = primeRaw($_POST['name'] ?? '');
        $username = primeRaw($_POST['username'] ?? '');
        $email = primeRaw($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $balance = max(0, (float)($_POST['balance'] ?? 0));
        $status = $_POST['status'] ?? 'active';
        $status = in_array($status, ['active', 'suspended'], true) ? $status : 'active';

        if ($name === '' || $username === '') {
            primeSetFlash('error', 'Name and username are required.');
            header('Location: ' . $baseUrl . '/resellers.php' . ($recordId ? '?edit=' . urlencode($recordId) : ''));
            exit;
        }

        foreach ($users as $user) {
            if (($user['username'] ?? '') === $username && ($user['id'] ?? '') !== $recordId) {
                primeSetFlash('error', 'Username is already in use.');
                header('Location: ' . $baseUrl . '/resellers.php' . ($recordId ? '?edit=' . urlencode($recordId) : ''));
                exit;
            }
        }

        $targetIndex = null;
        $record = null;
        foreach ($users as $index => $user) {
            if (($user['id'] ?? '') === $recordId && ($user['role'] ?? '') === 'reseller') {
                $targetIndex = $index;
                $record = $user;
                break;
            }
        }

        if (!$recordId) {
            $record = [
                'id' => primeGenerateId('RES'),
                'role' => 'reseller',
                'created_at' => primeNow(),
                'last_login_at' => '',
            ];
        }

        $record['name'] = $name;
        $record['username'] = $username;
        $record['email'] = $email;
        $record['status'] = $status;
        $record['balance'] = $balance;
        if ($password !== '') {
            $record['password'] = password_hash($password, PASSWORD_DEFAULT);
        } elseif (empty($record['password'])) {
            primeSetFlash('error', 'Password is required for new reseller accounts.');
            header('Location: ' . $baseUrl . '/resellers.php');
            exit;
        }

        if ($targetIndex === null) {
            $users[] = $record;
            primeAddAuditLog($currentUser, 'create_reseller', 'Created reseller ' . $record['username'] . '.', ['reseller_id' => $record['id']]);
            primeSetFlash('success', 'Reseller created successfully.');
        } else {
            $users[$targetIndex] = $record;
            primeAddAuditLog($currentUser, 'update_reseller', 'Updated reseller ' . $record['username'] . '.', ['reseller_id' => $record['id']]);
            primeSetFlash('success', 'Reseller updated successfully.');
        }

        primeSaveData('users', $users);
        header('Location: ' . $baseUrl . '/resellers.php');
        exit;
    }

    if ($action === 'adjust_balance') {
        $resellerId = $_POST['reseller_id'] ?? '';
        $delta = (float)($_POST['amount'] ?? 0);
        $reason = primeRaw($_POST['reason'] ?? 'Balance adjustment');
        foreach ($users as &$user) {
            if (($user['id'] ?? '') === $resellerId && ($user['role'] ?? '') === 'reseller') {
                $newBalance = round((float)($user['balance'] ?? 0) + $delta, 2);
                if ($newBalance < 0) {
                    primeSetFlash('error', 'Balance cannot go below zero.');
                    header('Location: ' . $baseUrl . '/resellers.php');
                    exit;
                }
                $user['balance'] = $newBalance;
                primeAddAuditLog($currentUser, 'adjust_balance', 'Adjusted reseller balance for ' . $user['username'] . '.', ['delta' => $delta, 'reason' => $reason]);
                primeSetFlash('success', 'Balance updated successfully.');
                break;
            }
        }
        unset($user);
        primeSaveData('users', $users);
        header('Location: ' . $baseUrl . '/resellers.php');
        exit;
    }

    if ($action === 'toggle_status') {
        $resellerId = $_POST['reseller_id'] ?? '';
        foreach ($users as &$user) {
            if (($user['id'] ?? '') === $resellerId && ($user['role'] ?? '') === 'reseller') {
                $user['status'] = ($user['status'] ?? 'active') === 'active' ? 'suspended' : 'active';
                primeAddAuditLog($currentUser, 'toggle_reseller_status', 'Changed reseller status for ' . $user['username'] . ' to ' . $user['status'] . '.');
                primeSetFlash('success', 'Reseller status updated successfully.');
                break;
            }
        }
        unset($user);
        primeSaveData('users', $users);
        header('Location: ' . $baseUrl . '/resellers.php');
        exit;
    }
}

$resellers = array_values(array_filter(primeLoadData('users'), fn($user) => ($user['role'] ?? '') === 'reseller'));
$pageTitle = 'Resellers';
include __DIR__ . '/includes/header.php';
?>
<div class="wrapper">
<?php include __DIR__ . '/includes/sidebar.php'; ?>
<div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="bi bi-person-badge text-theme"></i> Resellers</h2>
            <p class="text-muted mb-0">Create reseller accounts, manage balances, and suspend access when needed.</p>
        </div>
        <?php if ($editingReseller): ?><a href="<?php echo $baseUrl; ?>/resellers.php" class="btn btn-outline-secondary">Cancel Edit</a><?php endif; ?>
    </div>
    <?php echo primeRenderFlash(); ?>

    <div class="row g-3 mb-4">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><i class="bi bi-person-plus"></i> <?php echo $editingReseller ? 'Edit Reseller' : 'Add Reseller'; ?></div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="save_reseller">
                        <input type="hidden" name="record_id" value="<?php echo htmlspecialchars($editingReseller['id'] ?? ''); ?>">
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label">Name</label><input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($editingReseller['name'] ?? ''); ?>" required></div>
                            <div class="col-md-6"><label class="form-label">Username</label><input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($editingReseller['username'] ?? ''); ?>" required></div>
                            <div class="col-12"><label class="form-label">Email</label><input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($editingReseller['email'] ?? ''); ?>"></div>
                            <div class="col-md-6"><label class="form-label">Password <?php echo $editingReseller ? '(optional)' : ''; ?></label><input type="password" class="form-control" name="password"></div>
                            <div class="col-md-3"><label class="form-label">Balance</label><input type="number" step="0.01" class="form-control" name="balance" value="<?php echo htmlspecialchars((string)($editingReseller['balance'] ?? 0)); ?>"></div>
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="active" <?php echo (($editingReseller['status'] ?? 'active') === 'active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="suspended" <?php echo (($editingReseller['status'] ?? '') === 'suspended') ? 'selected' : ''; ?>>Suspended</option>
                                </select>
                            </div>
                            <div class="col-12"><button type="submit" class="btn btn-primary px-4"><i class="bi bi-save"></i> <?php echo $editingReseller ? 'Update Reseller' : 'Create Reseller'; ?></button></div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header"><i class="bi bi-wallet2"></i> Balance Operations</div>
                <div class="card-body">
                    <p class="text-muted mb-2">Owner can add or deduct reseller balance. Negative values deduct funds.</p>
                    <p class="mb-0">Pricing remains online and resellers consume balance automatically during key generation.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><i class="bi bi-table"></i> Reseller Accounts</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Reseller</th><th>Balance</th><th>Status</th><th>Last Login</th><th>Quick Balance</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php if (empty($resellers)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No resellers added yet.</td></tr>
                    <?php else: foreach ($resellers as $reseller): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?php echo htmlspecialchars($reseller['name']); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars($reseller['username']); ?></small>
                            </td>
                            <td><strong>₹<?php echo number_format((float)($reseller['balance'] ?? 0), 2); ?></strong></td>
                            <td><span class="badge bg-<?php echo primeStatusBadgeClass($reseller['status'] ?? 'active'); ?>"><?php echo htmlspecialchars(ucfirst($reseller['status'] ?? 'active')); ?></span></td>
                            <td><?php echo htmlspecialchars(primeFormatDateTime($reseller['last_login_at'] ?? '')); ?></td>
                            <td>
                                <form method="POST" class="row g-2 align-items-center">
                                    <input type="hidden" name="action" value="adjust_balance">
                                    <input type="hidden" name="reseller_id" value="<?php echo htmlspecialchars($reseller['id']); ?>">
                                    <div class="col-4"><input type="number" step="0.01" class="form-control form-control-sm" name="amount" placeholder="+/-"></div>
                                    <div class="col-5"><input type="text" class="form-control form-control-sm" name="reason" placeholder="Reason"></div>
                                    <div class="col-3"><button class="btn btn-sm btn-outline-primary w-100">Save</button></div>
                                </form>
                            </td>
                            <td>
                                <a href="<?php echo $baseUrl; ?>/resellers.php?edit=<?php echo urlencode($reseller['id']); ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="reseller_id" value="<?php echo htmlspecialchars($reseller['id']); ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-warning" data-confirm="Toggle this reseller status?"><i class="bi bi-arrow-repeat"></i></button>
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
