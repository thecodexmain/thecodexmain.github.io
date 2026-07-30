<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/links_helper.php';
require_once __DIR__ . '/../includes/layout.php';

requireRole('user');

$userId = $_SESSION['user_id'];
$plan = getUserPlan($userId);
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST[CSRF_TOKEN_NAME] ?? '')) { $error = 'Invalid token.'; }
    else {
        $action = $_POST['action'] ?? '';
        if ($action === 'create') {
            $target = sanitize($_POST['target'] ?? '');
            $customCode = sanitize($_POST['custom_code'] ?? '');
            $password = $_POST['link_password'] ?? '';
            $expiry = sanitize($_POST['expiry'] ?? '');
            $maxClicks = (int)($_POST['max_clicks'] ?? 0);
            if (empty($target) || !filter_var($target, FILTER_VALIDATE_URL)) { $error = 'Valid URL required.'; }
            else {
                $result = createShortLink($userId, $target, $customCode ?: null, $password ?: null, $expiry ?: null, $maxClicks ?: null);
                if ($result['success']) $success = "Short link created: " . htmlspecialchars(BASE_URL . 'l/' . $result['code']);
                else $error = $result['error'];
            }
        } elseif ($action === 'delete') {
            $id = sanitize($_POST['id'] ?? '');
            $result = deleteShortLink($id, $userId);
            if ($result['success']) $success = 'Link deleted.';
            else $error = $result['error'];
        } elseif ($action === 'toggle') {
            $id = sanitize($_POST['id'] ?? '');
            $links = readJson(DATA_PATH . 'links.json');
            foreach ($links as &$l) {
                if ($l['id'] === $id && $l['user_id'] === $userId) {
                    $l['status'] = ($l['status'] ?? 'active') === 'active' ? 'inactive' : 'active'; break;
                }
            } unset($l);
            writeJson(DATA_PATH . 'links.json', $links);
            $success = 'Link updated.';
        }
    }
}

$allLinks = readJson(DATA_PATH . 'links.json');
$myLinks = array_values(array_filter($allLinks, fn($l) => $l['user_id'] === $userId));
$myLinks = array_reverse($myLinks);
$limit = $plan['links_limit'] ?? 0;

renderHead('Short Links');
renderSidebar('user', 'links');
renderTopbar('Short Links');
?>

<?php if ($success) renderAlert('success', $success); ?>
<?php if ($error) renderAlert('danger', $error); ?>

<div class="section-header">
    <h2 class="section-title">My Links (<?= count($myLinks) ?><?= $limit > 0 ? " / $limit" : '' ?>)</h2>
    <?php if (!$limit || count($myLinks) < $limit): ?>
    <button class="btn btn-primary" onclick="openModal('createLinkModal')">➕ Create Link</button>
    <?php else: ?>
    <span class="badge badge-warning">Link limit reached (<?= $limit ?>)</span>
    <?php endif; ?>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Code</th><th>Target</th><th>Clicks</th><th>Status</th><th>Options</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($myLinks)): ?>
            <tr><td colspan="6"><div class="empty-state"><span class="empty-icon">🔗</span><h3>No links yet</h3><p>Create your first short link!</p></div></td></tr>
            <?php else: foreach ($myLinks as $lnk): ?>
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:6px;">
                        <code class="font-mono" style="background:var(--surface2);padding:3px 8px;border-radius:4px;font-size:13px;"><?= htmlspecialchars($lnk['code']) ?></code>
                        <button class="btn btn-sm btn-secondary btn-icon" data-copy="<?= htmlspecialchars(BASE_URL . 'l/' . $lnk['code']) ?>">📋</button>
                        <a href="<?= htmlspecialchars(BASE_URL . 'l/' . $lnk['code']) ?>" target="_blank" class="btn btn-sm btn-secondary btn-icon">🔗</a>
                    </div>
                </td>
                <td class="truncate" style="max-width:150px;" title="<?= htmlspecialchars($lnk['target']) ?>">
                    <a href="<?= htmlspecialchars($lnk['target']) ?>" target="_blank" class="text-small"><?= htmlspecialchars($lnk['target']) ?></a>
                </td>
                <td><strong><?= number_format($lnk['clicks'] ?? 0) ?></strong></td>
                <td><?= getStatusBadge($lnk['status'] ?? 'active') ?></td>
                <td class="text-small text-muted">
                    <?= !empty($lnk['password']) ? '🔒 ' : '' ?>
                    <?= !empty($lnk['expiry']) ? '⏰ ' . date('M j', strtotime($lnk['expiry'])) : '' ?>
                    <?= !empty($lnk['max_clicks']) ? '🎯 max ' . $lnk['max_clicks'] : '' ?>
                </td>
                <td>
                    <div style="display:flex;gap:4px;">
                        <form method="POST" style="display:inline;">
                            <?php csrfField(); ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= $lnk['id'] ?>">
                            <button class="btn btn-sm btn-warning" title="Toggle"><?= ($lnk['status'] ?? 'active') === 'active' ? '⏸️' : '▶️' ?></button>
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

<!-- Create Link Modal -->
<div class="modal-overlay" id="createLinkModal">
    <div class="modal">
        <div class="modal-header"><span class="modal-title">➕ Create Short Link</span><button class="modal-close" data-modal-close>✕</button></div>
        <form method="POST"><div class="modal-body">
            <?php csrfField(); ?><input type="hidden" name="action" value="create">
            <div class="form-group"><label>Destination URL *</label><input type="url" class="form-control" name="target" placeholder="https://example.com/your-long-url" required></div>
            <div class="form-group"><label>Custom Code <small class="text-muted">(optional)</small></label><input type="text" class="form-control" name="custom_code" placeholder="my-link (letters, numbers, hyphens)" pattern="[a-zA-Z0-9\-]+"></div>
            <div class="form-group"><label>Password <small class="text-muted">(optional)</small></label><input type="text" class="form-control" name="link_password" placeholder="Protect with password"></div>
            <div class="row">
                <div class="col"><div class="form-group"><label>Expiry Date <small class="text-muted">(optional)</small></label><input type="date" class="form-control" name="expiry"></div></div>
                <div class="col"><div class="form-group"><label>Max Clicks <small class="text-muted">(optional)</small></label><input type="number" class="form-control" name="max_clicks" min="0" placeholder="0 = unlimited"></div></div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
            <button type="submit" class="btn btn-primary">Create Link</button>
        </div></form>
    </div>
</div>

<?php renderFooter(); ?>
