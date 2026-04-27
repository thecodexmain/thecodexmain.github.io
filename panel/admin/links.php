<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/links_helper.php';
require_once __DIR__ . '/../includes/layout.php';

requireRole('admin');

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'delete') {
            $id = sanitize($_POST['id'] ?? '');
            $result = deleteShortLink($id, '', 'admin');
            if ($result['success']) $success = 'Link deleted.';
            else $error = $result['error'];
        } elseif ($action === 'toggle') {
            $id = sanitize($_POST['id'] ?? '');
            $links = readJson(DATA_PATH . 'links.json');
            foreach ($links as &$l) {
                if ($l['id'] === $id) {
                    $l['status'] = ($l['status'] ?? 'active') === 'active' ? 'inactive' : 'active';
                    break;
                }
            }
            unset($l);
            writeJson(DATA_PATH . 'links.json', $links);
            $success = 'Link status updated.';
        }
    }
}

$links = readJson(DATA_PATH . 'links.json');
$links = array_reverse($links);
$users = readJson(DATA_PATH . 'users.json');
$totalClicks = array_sum(array_column($links, 'clicks'));

renderHead('Short Links');
renderSidebar('admin', 'links');
renderTopbar('Short Links Overview');
?>

<?php if ($success) renderAlert('success', $success); ?>
<?php if ($error) renderAlert('danger', $error); ?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card-top">
            <div><div class="stat-value"><?= count($links) ?></div><div class="stat-label">Total Links</div></div>
            <div class="stat-icon primary">🔗</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <div><div class="stat-value"><?= number_format($totalClicks) ?></div><div class="stat-label">Total Clicks</div></div>
            <div class="stat-icon success">👆</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <div><div class="stat-value"><?= count(array_filter($links, fn($l) => $l['status'] === 'active')) ?></div><div class="stat-label">Active Links</div></div>
            <div class="stat-icon info">✅</div>
        </div>
    </div>
</div>

<div class="section-header">
    <h2 class="section-title">All Links</h2>
    <div class="search-bar"><input type="text" class="form-control" id="tableSearch" placeholder="Search links..."></div>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Code</th><th>Target URL</th><th>User</th><th>Clicks</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($links)): ?>
            <tr><td colspan="7"><div class="empty-state"><span class="empty-icon">🔗</span><h3>No links yet</h3></div></td></tr>
            <?php else: foreach ($links as $lnk): ?>
            <?php $user = findById($users, $lnk['user_id']); ?>
            <tr>
                <td>
                    <code class="font-mono" style="background:var(--surface2);padding:3px 8px;border-radius:4px;"><?= htmlspecialchars($lnk['code']) ?></code>
                    <button class="btn btn-sm btn-secondary btn-icon" data-copy="<?= htmlspecialchars(BASE_URL . 'l/' . $lnk['code']) ?>" style="margin-left:4px;">📋</button>
                </td>
                <td class="truncate" style="max-width:200px;" title="<?= htmlspecialchars($lnk['target']) ?>">
                    <a href="<?= htmlspecialchars($lnk['target']) ?>" target="_blank"><?= htmlspecialchars($lnk['target']) ?></a>
                </td>
                <td><?= htmlspecialchars($user['username'] ?? '—') ?></td>
                <td><strong><?= number_format($lnk['clicks'] ?? 0) ?></strong></td>
                <td><?= getStatusBadge($lnk['status'] ?? 'active') ?></td>
                <td class="text-small text-muted"><?= date('M j, Y', strtotime($lnk['created_at'])) ?></td>
                <td>
                    <div style="display:flex;gap:4px;">
                        <form method="POST" style="display:inline;">
                            <?php csrfField(); ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= $lnk['id'] ?>">
                            <button class="btn btn-sm btn-warning">⏸️</button>
                        </form>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete link?')">
                            <?php csrfField(); ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $lnk['id'] ?>">
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

<?php renderFooter(); ?>
