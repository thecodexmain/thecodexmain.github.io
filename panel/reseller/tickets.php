<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';

requireRole('admin', 'reseller');

$resellerId = $_SESSION['user_id'];
$users = readJson(DATA_PATH . 'users.json');
$myUserIds = array_map(fn($u) => $u['id'], array_filter($users, fn($u) => ($u['reseller_id'] ?? '') === $resellerId));
$tickets = readJson(DATA_PATH . 'tickets.json');
$myTickets = array_values(array_filter($tickets, fn($t) => in_array($t['user_id'], $myUserIds)));
$myTickets = array_reverse($myTickets);

$success = $error = '';
$viewTicket = null;

if (isset($_GET['id'])) {
    $id = sanitize($_GET['id']);
    foreach ($myTickets as $t) { if ($t['id'] === $id) { $viewTicket = $t; break; } }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRF($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $action = $_POST['action'] ?? '';
    $id = sanitize($_POST['id'] ?? '');
    $fullTickets = readJson(DATA_PATH . 'tickets.json');
    foreach ($fullTickets as &$t) {
        if ($t['id'] !== $id) continue;
        if (!in_array($t['user_id'], $myUserIds)) break;
        if ($action === 'reply') {
            $msg = sanitize($_POST['message'] ?? '');
            if ($msg) {
                $t['replies'][] = ['from' => 'reseller', 'message' => $msg, 'at' => date('c')];
                $t['status'] = 'answered';
                $success = 'Reply sent.';
            }
        } elseif ($action === 'close') {
            $t['status'] = 'closed';
            $success = 'Ticket closed.';
        }
        break;
    } unset($t);
    writeJson(DATA_PATH . 'tickets.json', $fullTickets);
    redirect(BASE_URL . 'reseller/tickets.php' . ($id ? "?id=$id" : ''));
}

$filterStatus = $_GET['status'] ?? '';
if ($filterStatus) $myTickets = array_values(array_filter($myTickets, fn($t) => $t['status'] === $filterStatus));

renderHead('Tickets');
renderSidebar('reseller', 'tickets');
renderTopbar('Support Tickets');
?>

<?php if ($success) renderAlert('success', $success); ?>

<?php if ($viewTicket): ?>
<div style="margin-bottom:16px;"><a href="<?= BASE_URL ?>reseller/tickets.php" class="btn btn-secondary">← Back to Tickets</a></div>
<div class="card">
    <div class="card-header">
        <span class="card-title">[#<?= $viewTicket['id'] ?>] <?= htmlspecialchars($viewTicket['subject']) ?></span>
        <?= getStatusBadge($viewTicket['status']) ?>
    </div>
    <div class="card-body">
        <div style="background:var(--surface2);padding:12px 16px;border-radius:var(--radius-sm);margin-bottom:16px;">
            <div class="text-small text-muted">User message:</div>
            <p style="margin:8px 0 0;"><?= nl2br(htmlspecialchars($viewTicket['message'])) ?></p>
        </div>
        <?php foreach ($viewTicket['replies'] ?? [] as $reply): ?>
        <div style="background:<?= $reply['from'] === 'reseller' ? 'color-mix(in srgb,var(--primary) 10%,transparent)' : 'var(--surface2)' ?>;padding:12px 16px;border-radius:var(--radius-sm);margin-bottom:10px;">
            <div class="text-small text-muted"><strong><?= ucfirst($reply['from']) ?></strong> · <?= timeAgo($reply['at']) ?></div>
            <p style="margin:6px 0 0;"><?= nl2br(htmlspecialchars($reply['message'])) ?></p>
        </div>
        <?php endforeach; ?>
        <?php if ($viewTicket['status'] !== 'closed'): ?>
        <form method="POST" style="margin-top:16px;">
            <?php csrfField(); ?><input type="hidden" name="action" value="reply"><input type="hidden" name="id" value="<?= $viewTicket['id'] ?>">
            <div class="form-group"><label>Reply</label><textarea class="form-control" name="message" rows="4" required></textarea></div>
            <div style="display:flex;gap:8px;">
                <button type="submit" class="btn btn-primary">Send Reply</button>
                <button type="submit" name="action" value="close" class="btn btn-secondary">Close Ticket</button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>
<?php else: ?>
<div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;">
    <?php foreach (['', 'open', 'answered', 'closed'] as $st): ?>
    <a href="?<?= $st ? "status=$st" : '' ?>" class="btn <?= $filterStatus === $st ? 'btn-primary' : 'btn-secondary' ?>"><?= $st ? ucfirst($st) : 'All' ?></a>
    <?php endforeach; ?>
</div>
<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>#</th><th>Subject</th><th>User</th><th>Status</th><th>Updated</th><th></th></tr></thead>
            <tbody>
            <?php if (empty($myTickets)): ?>
            <tr><td colspan="6"><div class="empty-state"><span class="empty-icon">🎫</span><h3>No tickets</h3></div></td></tr>
            <?php else: foreach ($myTickets as $t):
                $u = findById($users, $t['user_id']); ?>
            <tr>
                <td class="text-small text-muted"><?= $t['id'] ?></td>
                <td><strong><?= htmlspecialchars($t['subject']) ?></strong></td>
                <td><?= htmlspecialchars($u['username'] ?? '—') ?></td>
                <td><?= getStatusBadge($t['status']) ?></td>
                <td class="text-small text-muted"><?= timeAgo($t['updated_at'] ?? $t['created_at']) ?></td>
                <td><a href="?id=<?= $t['id'] ?>" class="btn btn-sm btn-secondary">View</a></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php renderFooter(); ?>
