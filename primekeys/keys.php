<?php
require_once __DIR__ . '/includes/auth.php';
primeRequireLogin();
primeSyncDataState();

$currentUser = primeGetCurrentUser();
$baseUrl = primeGetBaseUrl();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (primePortalLockedFor($currentUser)) {
        primeSetFlash('error', 'Maintenance mode is enabled. Reseller key actions are temporarily blocked.');
        header('Location: ' . $baseUrl . '/keys.php');
        exit;
    }

    $action = $_POST['action'] ?? '';
    $keys = primeLoadData('keys');
    $endUsers = primeLoadData('end_users');
    $accessible = primeFilterOwnedRecords($keys, $currentUser);
    $accessibleIds = array_column($accessible, 'id');
    $removed = 0;

    $removeKeys = function ($callback) use (&$keys, &$endUsers, &$removed, $accessibleIds) {
        $keys = array_values(array_filter($keys, function ($key) use ($callback, &$endUsers, &$removed, $accessibleIds) {
            if (!in_array($key['id'] ?? '', $accessibleIds, true)) {
                return true;
            }
            if (!$callback($key)) {
                return true;
            }
            foreach ($endUsers as &$user) {
                if (($user['key_id'] ?? '') === ($key['id'] ?? '')) {
                    $user['key_id'] = '';
                    $user['updated_at'] = primeNow();
                }
            }
            unset($user);
            $removed++;
            return false;
        }));
    };

    if ($action === 'delete_key') {
        $keyId = $_POST['key_id'] ?? '';
        if (in_array($keyId, $accessibleIds, true)) {
            $removeKeys(fn($key) => ($key['id'] ?? '') === $keyId);
            primeAddAuditLog($currentUser, 'delete_key', 'Deleted key ' . $keyId . '.');
        }
    } elseif ($action === 'delete_all_keys') {
        if (trim((string)($_POST['confirm_text'] ?? '')) === 'DELETE ALL') {
            $removeKeys(fn($key) => true);
            primeAddAuditLog($currentUser, 'delete_all_keys', 'Deleted all accessible keys.');
        } else {
            primeSetFlash('error', 'Type DELETE ALL to confirm deleting all keys.');
            header('Location: ' . $baseUrl . '/keys.php');
            exit;
        }
    } elseif ($action === 'cleanup_expired_unused') {
        if (trim((string)($_POST['confirm_text'] ?? '')) === 'CLEANUP') {
            $removeKeys(fn($key) => in_array(($key['status'] ?? ''), ['expired', 'unused'], true));
            primeAddAuditLog($currentUser, 'cleanup_keys', 'Deleted expired and unused accessible keys.');
        } else {
            primeSetFlash('error', 'Type CLEANUP to confirm the cleanup action.');
            header('Location: ' . $baseUrl . '/keys.php');
            exit;
        }
    }

    primeSaveData('keys', $keys);
    primeSaveData('end_users', $endUsers);
    primeSetFlash('success', $removed > 0 ? 'Updated key inventory successfully.' : 'No keys matched the requested action.');
    header('Location: ' . $baseUrl . '/keys.php');
    exit;
}

$search = strtolower(primeRaw($_GET['search'] ?? ''));
$filterStatus = primeRaw($_GET['status'] ?? '');
$keys = primeFilterOwnedRecords(primeLoadData('keys'), $currentUser);
$keys = array_values(array_filter($keys, function ($key) use ($search, $filterStatus) {
    $matchesSearch = $search === '' || str_contains(strtolower(($key['key_code'] ?? '') . ' ' . ($key['label_seed'] ?? '') . ' ' . ($key['created_by_name'] ?? '')), $search);
    $matchesStatus = $filterStatus === '' || ($key['status'] ?? '') === $filterStatus;
    return $matchesSearch && $matchesStatus;
}));

$pageTitle = 'Keys';
include __DIR__ . '/includes/header.php';
?>
<div class="wrapper">
<?php include __DIR__ . '/includes/sidebar.php'; ?>
<div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="bi bi-key text-theme"></i> Key Management</h2>
            <p class="text-muted mb-0">Single delete, delete all, and expired/unused cleanup are available here.</p>
        </div>
        <a href="<?php echo $baseUrl; ?>/generator.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Generate Keys</a>
    </div>
    <?php echo primeRenderFlash(); ?>

    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="row g-2">
                        <div class="col-md-6"><input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" placeholder="Search key, label, creator..."></div>
                        <div class="col-md-3">
                            <select class="form-select" name="status">
                                <option value="">All Statuses</option>
                                <?php foreach (['unused','used','expired'] as $status): ?>
                                    <option value="<?php echo $status; ?>" <?php echo $filterStatus === $status ? 'selected' : ''; ?>><?php echo ucfirst($status); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button class="btn btn-outline-primary w-100"><i class="bi bi-search"></i> Filter</button>
                            <a href="<?php echo $baseUrl; ?>/keys.php" class="btn btn-outline-secondary w-100">Reset</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <form method="POST" class="mb-3">
                        <input type="hidden" name="action" value="cleanup_expired_unused">
                        <label class="form-label small fw-semibold">Delete Expired & Unused Keys</label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="confirm_text" placeholder="Type CLEANUP">
                            <button class="btn btn-outline-danger">Run</button>
                        </div>
                    </form>
                    <form method="POST">
                        <input type="hidden" name="action" value="delete_all_keys">
                        <label class="form-label small fw-semibold">Delete All Keys</label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="confirm_text" placeholder="Type DELETE ALL">
                            <button class="btn btn-danger">Delete</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Key</th><th>Type</th><th>Owner</th><th>Assigned User</th><th>Expiry</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php if (empty($keys)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No keys found.</td></tr>
                    <?php else: foreach ($keys as $key): $assignedUser = !empty($key['assigned_to']) ? primeFindEndUserById($key['assigned_to']) : null; ?>
                        <tr>
                            <td>
                                <div class="fw-semibold code-pill"><?php echo htmlspecialchars($key['key_code']); ?></div>
                                <small class="text-muted">Seed: <?php echo htmlspecialchars($key['label_seed'] ?: '-'); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $key['generator_type'] ?? 'random'))); ?></td>
                            <td><?php echo htmlspecialchars($key['created_by_name'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($assignedUser['name'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars(primeFormatDate($key['expires_at'] ?? '')); ?></td>
                            <td><span class="badge bg-<?php echo primeStatusBadgeClass($key['status'] ?? 'unused'); ?>"><?php echo htmlspecialchars(ucfirst($key['status'] ?? 'unused')); ?></span></td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="delete_key">
                                    <input type="hidden" name="key_id" value="<?php echo htmlspecialchars($key['id']); ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Delete this key permanently?"><i class="bi bi-trash"></i></button>
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
