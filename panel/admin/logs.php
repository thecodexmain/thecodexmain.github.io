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
        if ($action === 'clear') {
            $level = $_POST['level'] ?? 'all';
            $logs = readJson(DATA_PATH . 'logs.json');
            if ($level === 'all') {
                writeJson(DATA_PATH . 'logs.json', []);
                $success = 'All logs cleared.';
            } else {
                $filtered = array_values(array_filter($logs, fn($l) => $l['level'] !== $level));
                writeJson(DATA_PATH . 'logs.json', $filtered);
                $success = "Cleared {$level} logs.";
            }
            appLog('clear_logs', $_SESSION['username'], "Cleared logs: {$level}");
        }
    }
}

$logs = readJson(DATA_PATH . 'logs.json');
$logs = array_reverse($logs);

// Filter
$filterLevel = $_GET['level'] ?? '';
$filterUser = sanitize($_GET['user'] ?? '');
$filterAction = sanitize($_GET['action_filter'] ?? '');

if ($filterLevel) $logs = array_filter($logs, fn($l) => $l['level'] === $filterLevel);
if ($filterUser) $logs = array_filter($logs, fn($l) => stripos($l['user'], $filterUser) !== false);
if ($filterAction) $logs = array_filter($logs, fn($l) => stripos($l['action'], $filterAction) !== false);

$logs = array_values($logs);
$total = count($logs);
$perPage = 50;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;
$pageLogs = array_slice($logs, $offset, $perPage);
$totalPages = max(1, ceil($total / $perPage));

renderHead('Logs');
renderSidebar('admin', 'logs');
renderTopbar('Activity Logs');
?>

<?php if ($success) renderAlert('success', $success); ?>
<?php if ($error) renderAlert('danger', $error); ?>

<div class="section-header">
    <h2 class="section-title">Logs (<?= $total ?>)</h2>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <form method="GET" style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
            <select class="form-control" name="level" style="width:auto;" onchange="this.form.submit()">
                <option value="">All Levels</option>
                <?php foreach (['info', 'warning', 'error'] as $l): ?>
                <option value="<?= $l ?>" <?= $filterLevel === $l ? 'selected' : '' ?>><?= ucfirst($l) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" class="form-control" name="user" placeholder="Filter by user..." value="<?= htmlspecialchars($filterUser) ?>" style="width:150px;">
            <input type="text" class="form-control" name="action_filter" placeholder="Filter by action..." value="<?= htmlspecialchars($filterAction) ?>" style="width:150px;">
            <button type="submit" class="btn btn-secondary">Filter</button>
            <a href="<?= BASE_URL ?>admin/logs.php" class="btn btn-secondary">Reset</a>
        </form>
        <button class="btn btn-danger" onclick="openModal('clearLogsModal')">🗑️ Clear</button>
    </div>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Time</th><th>Level</th><th>Action</th><th>User</th><th>Details</th><th>IP</th></tr></thead>
            <tbody>
            <?php if (empty($pageLogs)): ?>
            <tr><td colspan="6"><div class="empty-state"><span class="empty-icon">📋</span><h3>No logs</h3></div></td></tr>
            <?php else: foreach ($pageLogs as $log): ?>
            <tr>
                <td class="text-small text-muted" style="white-space:nowrap;"><?= date('M j, H:i:s', strtotime($log['timestamp'])) ?></td>
                <td>
                    <span class="badge-status <?= $log['level'] === 'error' ? 'badge-inactive' : ($log['level'] === 'warning' ? 'badge-pending' : 'badge-active') ?>">
                        <?= ucfirst($log['level']) ?>
                    </span>
                </td>
                <td style="font-weight:500;"><?= htmlspecialchars($log['action']) ?></td>
                <td><?= htmlspecialchars($log['user']) ?></td>
                <td class="text-small text-muted" style="max-width:300px;" title="<?= htmlspecialchars($log['details']) ?>"><?= htmlspecialchars(substr($log['details'], 0, 80)) ?></td>
                <td class="text-small font-mono"><?= htmlspecialchars($log['ip']) ?></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($totalPages > 1): ?>
    <div style="padding:12px 16px;display:flex;gap:6px;justify-content:center;flex-wrap:wrap;">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?page=<?= $i ?>&level=<?= $filterLevel ?>&user=<?= urlencode($filterUser) ?>&action_filter=<?= urlencode($filterAction) ?>"
           class="btn btn-sm <?= $i === $page ? 'btn-primary' : 'btn-secondary' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Clear Modal -->
<div class="modal-overlay" id="clearLogsModal">
    <div class="modal">
        <div class="modal-header"><span class="modal-title">🗑️ Clear Logs</span><button class="modal-close" data-modal-close>✕</button></div>
        <form method="POST"><div class="modal-body">
            <?php csrfField(); ?><input type="hidden" name="action" value="clear">
            <div class="form-group">
                <label>Clear Which Logs?</label>
                <select class="form-control" name="level">
                    <option value="all">All Logs</option>
                    <option value="info">Info Only</option>
                    <option value="warning">Warnings Only</option>
                    <option value="error">Errors Only</option>
                </select>
            </div>
            <div class="alert alert-warning">⚠️ This action cannot be undone.</div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
            <button type="submit" class="btn btn-danger">Clear Logs</button>
        </div></form>
    </div>
</div>

<?php renderFooter(); ?>
