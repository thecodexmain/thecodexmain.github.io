<?php
require_once __DIR__ . '/../includes/auth.php';
hostingRequireRole(['admin']);
$baseUrl = hostingGetBaseUrl();

$services = hostingEnsureDefaultServices();
$error = '';

$editId = (string)($_GET['edit'] ?? '');
$editing = null;
if ($editId !== '') {
    foreach ($services as $s) {
        if (($s['id'] ?? '') === $editId) { $editing = $s; break; }
    }
    if (!$editing) {
        hostingSetFlash('error', 'Service not found.');
        header('Location: ' . hostingGetBaseUrl() . '/admin/services.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    hostingVerifyCsrf();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'create') {
        $name = trim((string)($_POST['name'] ?? ''));
        $price = (float)($_POST['price'] ?? 0);
        $cycle = (string)($_POST['billing_cycle'] ?? 'monthly');
        $features = trim((string)($_POST['features'] ?? ''));
        $featureList = array_values(array_filter(array_map('trim', preg_split("/\\r?\\n/", $features))));

        if ($name === '' || $price <= 0) {
            $error = 'Name and price are required.';
        } else {
            $services[] = [
                'id' => 'S-' . hostingGenerateId(),
                'name' => $name,
                'price' => $price,
                'billing_cycle' => in_array($cycle, ['monthly', 'yearly'], true) ? $cycle : 'monthly',
                'active' => true,
                'features' => $featureList
            ];
            hostingSaveData('services', $services);
            hostingSetFlash('success', 'Service created.');
            header('Location: ' . hostingGetBaseUrl() . '/admin/services.php');
            exit;
        }
    }

    if ($action === 'update') {
        $id = (string)($_POST['service_id'] ?? '');
        $name = trim((string)($_POST['name'] ?? ''));
        $price = (float)($_POST['price'] ?? 0);
        $cycle = (string)($_POST['billing_cycle'] ?? 'monthly');
        $active = isset($_POST['active']);
        $features = trim((string)($_POST['features'] ?? ''));
        $featureList = array_values(array_filter(array_map('trim', preg_split("/\\r?\\n/", $features))));

        $found = false;
        foreach ($services as &$s) {
            if (($s['id'] ?? '') !== $id) continue;
            $found = true;
            $s['name'] = $name ?: ($s['name'] ?? '');
            $s['price'] = $price > 0 ? $price : (float)($s['price'] ?? 0);
            $s['billing_cycle'] = in_array($cycle, ['monthly', 'yearly'], true) ? $cycle : 'monthly';
            $s['active'] = $active;
            $s['features'] = $featureList;
            break;
        }
        unset($s);
        if (!$found) {
            $error = 'Service not found.';
        } else {
            hostingSaveData('services', $services);
            hostingSetFlash('success', 'Service updated.');
            header('Location: ' . hostingGetBaseUrl() . '/admin/services.php');
            exit;
        }
    }

    if ($action === 'delete') {
        $id = (string)($_POST['service_id'] ?? '');
        $services = array_values(array_filter($services, fn($s) => ($s['id'] ?? '') !== $id));
        hostingSaveData('services', $services);
        hostingSetFlash('success', 'Service deleted.');
        header('Location: ' . hostingGetBaseUrl() . '/admin/services.php');
        exit;
    }
}
?>
<?php require __DIR__ . '/../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h4 fw-bold mb-1">Services</h1>
        <div class="text-muted">Create hosting plans users can request.</div>
    </div>
    <a class="btn btn-sm btn-outline-secondary" href="<?php echo $baseUrl; ?>/admin/dashboard.php"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Plan</th><th>Price</th><th>Cycle</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($services as $s): ?>
                        <tr>
                            <td class="fw-semibold"><?php echo htmlspecialchars($s['name'] ?? ''); ?><div class="text-muted small"><?php echo htmlspecialchars($s['id'] ?? ''); ?></div></td>
                            <td>$<?php echo number_format((float)($s['price'] ?? 0), 2); ?></td>
                            <td class="text-muted"><?php echo htmlspecialchars($s['billing_cycle'] ?? 'monthly'); ?></td>
                            <td><span class="badge text-bg-<?php echo !empty($s['active']) ? 'success' : 'secondary'; ?>"><?php echo !empty($s['active']) ? 'active' : 'inactive'; ?></span></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="<?php echo $baseUrl; ?>/admin/services.php?edit=<?php echo urlencode($s['id'] ?? ''); ?>">Edit</a>
                                <form method="post" action="" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(hostingCsrfToken()); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="service_id" value="<?php echo htmlspecialchars($s['id'] ?? ''); ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit" onclick="return confirm('Delete this service?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <?php if ($editing): ?>
            <div class="card">
                <div class="card-header bg-transparent fw-semibold">Edit service</div>
                <div class="card-body">
                    <form method="post" action="" class="row g-2">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(hostingCsrfToken()); ?>">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="service_id" value="<?php echo htmlspecialchars($editing['id'] ?? ''); ?>">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Name</label>
                            <input class="form-control" name="name" value="<?php echo htmlspecialchars($editing['name'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Price</label>
                            <input class="form-control" name="price" type="number" step="0.01" min="0" value="<?php echo htmlspecialchars((string)($editing['price'] ?? 0)); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Billing</label>
                            <select class="form-select" name="billing_cycle">
                                <option value="monthly" <?php echo ($editing['billing_cycle'] ?? 'monthly') === 'monthly' ? 'selected' : ''; ?>>monthly</option>
                                <option value="yearly" <?php echo ($editing['billing_cycle'] ?? 'monthly') === 'yearly' ? 'selected' : ''; ?>>yearly</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Features (one per line)</label>
                            <textarea class="form-control" name="features" rows="5"><?php echo htmlspecialchars(implode("\n", $editing['features'] ?? [])); ?></textarea>
                        </div>
                        <div class="col-12 form-check ms-2">
                            <input class="form-check-input" type="checkbox" name="active" id="active" <?php echo !empty($editing['active']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="active">Active</label>
                        </div>
                        <div class="col-12 d-flex justify-content-end gap-2">
                            <a class="btn btn-outline-secondary" href="<?php echo $baseUrl; ?>/admin/services.php">Cancel</a>
                            <button class="btn btn-primary" type="submit">Save changes</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-header bg-transparent fw-semibold">Create service</div>
                <div class="card-body">
                    <form method="post" action="" class="row g-2">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(hostingCsrfToken()); ?>">
                        <input type="hidden" name="action" value="create">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Name</label>
                            <input class="form-control" name="name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Price</label>
                            <input class="form-control" name="price" type="number" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Billing</label>
                            <select class="form-select" name="billing_cycle">
                                <option value="monthly" selected>monthly</option>
                                <option value="yearly">yearly</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Features (one per line)</label>
                            <textarea class="form-control" name="features" rows="5" placeholder="Free SSL&#10;10GB SSD&#10;Support tickets"></textarea>
                        </div>
                        <div class="col-12 d-flex justify-content-end">
                            <button class="btn btn-primary" type="submit"><i class="bi bi-plus-circle me-1"></i>Create</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
