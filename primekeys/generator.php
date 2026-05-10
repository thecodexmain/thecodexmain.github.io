<?php
require_once __DIR__ . '/includes/auth.php';
primeRequireLogin();
primeSyncDataState();

$currentUser = primeGetCurrentUser();
$baseUrl = primeGetBaseUrl();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (primePortalLockedFor($currentUser)) {
        primeSetFlash('error', 'Maintenance mode is enabled. Reseller generation is temporarily disabled.');
        header('Location: ' . $baseUrl . '/generator.php');
        exit;
    }

    $generatorType = primeRaw($_POST['generator_type'] ?? 'random');
    $labelSeed = primeRaw($_POST['label_seed'] ?? '');
    $quantity = max(1, min(100, (int)($_POST['quantity'] ?? 1)));
    $durationDays = max(1, min(365, (int)($_POST['duration_days'] ?? 30)));
    $unitPrice = primePriceForDuration($durationDays);
    $totalCost = $unitPrice * $quantity;

    $users = primeLoadData('users');
    $keys = primeLoadData('keys');
    $existingCodes = array_column($keys, 'key_code');

    if (($currentUser['role'] ?? '') === 'reseller') {
        foreach ($users as &$user) {
            if (($user['id'] ?? '') === ($currentUser['id'] ?? '')) {
                if ((float)($user['balance'] ?? 0) < $totalCost) {
                    primeSetFlash('error', 'Insufficient balance to generate the requested keys.');
                    header('Location: ' . $baseUrl . '/generator.php');
                    exit;
                }
                $user['balance'] = round((float)$user['balance'] - $totalCost, 2);
                $currentUser = $user;
                $_SESSION['primekeys_balance'] = $user['balance'];
                break;
            }
        }
        unset($user);
        primeSaveData('users', $users);
    }

    for ($i = 0; $i < $quantity; $i++) {
        $code = primeCreateKeyCode($generatorType, $labelSeed, $existingCodes);
        $existingCodes[] = $code;
        $keys[] = [
            'id' => primeGenerateId('KEY'),
            'key_code' => $code,
            'generator_type' => $generatorType,
            'label_seed' => $labelSeed,
            'duration_days' => $durationDays,
            'status' => 'unused',
            'owner_user_id' => $currentUser['id'],
            'created_by_id' => $currentUser['id'],
            'created_by_name' => $currentUser['name'],
            'created_by_role' => $currentUser['role'],
            'assigned_to' => '',
            'created_at' => primeNow(),
            'used_at' => '',
            'expires_at' => date('Y-m-d H:i:s', strtotime('+' . $durationDays . ' days')),
            'cost_charged' => ($currentUser['role'] ?? '') === 'reseller' ? $unitPrice : 0,
        ];
    }

    primeSaveData('keys', $keys);
    primeAddAuditLog($currentUser, 'generate_keys', 'Generated ' . $quantity . ' ' . $generatorType . ' key(s).', [
        'quantity' => $quantity,
        'duration_days' => $durationDays,
        'total_cost' => $totalCost,
    ]);
    primeSetFlash('success', 'Generated ' . $quantity . ' key(s) successfully.');
    header('Location: ' . $baseUrl . '/generator.php');
    exit;
}

$settings = primeGetSettings();
$recentKeys = array_slice(array_reverse(primeFilterOwnedRecords(primeLoadData('keys'), $currentUser)), 0, 10);
$pageTitle = 'Key Generator';
include __DIR__ . '/includes/header.php';
?>
<div class="wrapper">
<?php include __DIR__ . '/includes/sidebar.php'; ?>
<div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="bi bi-magic text-theme"></i> Key Generator</h2>
            <p class="text-muted mb-0">Create random, named, or hybrid keys with online pricing and expiry control.</p>
        </div>
        <div class="text-end small text-muted">
            <div>Price / day: <strong>₹<?php echo number_format((float)$settings['price_per_day'], 2); ?></strong></div>
            <?php if (($currentUser['role'] ?? '') === 'reseller'): ?>
                <div>Balance: <strong>₹<?php echo number_format((float)($currentUser['balance'] ?? 0), 2); ?></strong></div>
            <?php endif; ?>
        </div>
    </div>
    <?php echo primeRenderFlash(); ?>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><i class="bi bi-plus-circle"></i> Generate Keys</div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Generator Type</label>
                                <select class="form-select" name="generator_type">
                                    <option value="random">Random Key Generator</option>
                                    <option value="name">Name Key Generator</option>
                                    <option value="name_random">Name + Random Generator</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Quantity</label>
                                <input type="number" class="form-control" min="1" max="100" name="quantity" value="1" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Name / Seed</label>
                                <input type="text" class="form-control" name="label_seed" placeholder="Optional for random keys">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Key Expiry Time (Days)</label>
                                <input type="number" class="form-control" min="1" max="365" name="duration_days" value="30" required>
                            </div>
                            <div class="col-12 small text-muted">
                                Resellers are charged automatically from balance using the online price per day setting.
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary px-4"><i class="bi bi-stars"></i> Generate</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header"><i class="bi bi-lightning"></i> Generation Rules</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6"><div class="status-tile"><div class="fw-semibold mb-1">Random</div><div class="small text-muted">Fully generated unique key strings.</div></div></div>
                        <div class="col-md-6"><div class="status-tile"><div class="fw-semibold mb-1">Name</div><div class="small text-muted">Uses your provided label in the final key.</div></div></div>
                        <div class="col-md-6"><div class="status-tile"><div class="fw-semibold mb-1">Name + Random</div><div class="small text-muted">Combines brandable text and randomized segments.</div></div></div>
                        <div class="col-md-6"><div class="status-tile"><div class="fw-semibold mb-1">Expiry</div><div class="small text-muted">Every key stores its duration and expiry time.</div></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header"><i class="bi bi-clock-history"></i> Recently Generated</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Key</th><th>Type</th><th>Days</th><th>Cost</th><th>Status</th><th>Created</th></tr></thead>
                    <tbody>
                    <?php if (empty($recentKeys)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No key history yet.</td></tr>
                    <?php else: foreach ($recentKeys as $key): ?>
                        <tr>
                            <td><span class="code-pill"><?php echo htmlspecialchars($key['key_code']); ?></span></td>
                            <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $key['generator_type'] ?? 'random'))); ?></td>
                            <td><?php echo (int)($key['duration_days'] ?? 0); ?></td>
                            <td>₹<?php echo number_format((float)($key['cost_charged'] ?? 0), 2); ?></td>
                            <td><span class="badge bg-<?php echo primeStatusBadgeClass($key['status'] ?? 'unused'); ?>"><?php echo htmlspecialchars(ucfirst($key['status'] ?? 'unused')); ?></span></td>
                            <td><?php echo htmlspecialchars(primeFormatDateTime($key['created_at'] ?? '')); ?></td>
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
