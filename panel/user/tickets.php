<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';

requireRole('user');

$userId = $_SESSION['user_id'];
$success = $error = '';
$allTickets = readJson(DATA_PATH . 'tickets.json');
$myTickets = array_values(array_filter($allTickets, fn($t) => $t['user_id'] === $userId));
$myTickets = array_reverse($myTickets);

$viewTicket = null;
if (isset($_GET['id'])) {
    $id = sanitize($_GET['id']);
    foreach ($myTickets as $t) { if ($t['id'] === $id) { $viewTicket = $t; break; } }
}

$showNew = isset($_GET['new']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST[CSRF_TOKEN_NAME] ?? '')) { $error = 'Invalid token.'; }
    else {
        $action = $_POST['action'] ?? '';
        $tickets = readJson(DATA_PATH . 'tickets.json');
        if ($action === 'create') {
            $subject = sanitize($_POST['subject'] ?? '');
            $message = sanitize($_POST['message'] ?? '');
            if (empty($subject) || empty($message)) { $error = 'Subject and message required.'; }
            else {
                $openCount = count(array_filter($myTickets, fn($t) => in_array($t['status'], ['open', 'answered'])));
                if ($openCount >= 5) { $error = 'You have too many open tickets (max 5).'; }
                else {
                    $ticket = ['id' => generateId('tkt'), 'user_id' => $userId, 'subject' => $subject,
                        'message' => $message, 'status' => 'open', 'priority' => sanitize($_POST['priority'] ?? 'normal'),
                        'replies' => [], 'created_at' => date('c'), 'updated_at' => date('c')];
                    $tickets[] = $ticket;
                    writeJson(DATA_PATH . 'tickets.json', $tickets);
                    appLog('info', "Ticket created: {$subject}", $userId);
                    redirect(BASE_URL . 'user/tickets.php?id=' . $ticket['id']);
                }
            }
        } elseif ($action === 'reply') {
            $id = sanitize($_POST['id'] ?? '');
            $msg = sanitize($_POST['message'] ?? '');
            if ($msg) {
                foreach ($tickets as &$t) {
                    if ($t['id'] === $id && $t['user_id'] === $userId && $t['status'] !== 'closed') {
                        $t['replies'][] = ['from' => 'user', 'message' => $msg, 'at' => date('c')];
                        $t['status'] = 'open';
                        $t['updated_at'] = date('c');
                        break;
                    }
                } unset($t);
                writeJson(DATA_PATH . 'tickets.json', $tickets);
                redirect(BASE_URL . 'user/tickets.php?id=' . $id);
            }
        }
    }
}

renderHead('Tickets');
renderSidebar('user', 'tickets');
renderTopbar('Support Tickets');
?>

<?php if ($success) renderAlert('success', $success); ?>
<?php if ($error) renderAlert('danger', $error); ?>

<?php if ($viewTicket): ?>
<div style="margin-bottom:16px;"><a href="<?= BASE_URL ?>user/tickets.php" class="btn btn-secondary">← My Tickets</a></div>
<div class="card">
    <div class="card-header">
        <span class="card-title"><?= htmlspecialchars($viewTicket['subject']) ?></span>
        <?= getStatusBadge($viewTicket['status']) ?>
    </div>
    <div class="card-body">
        <div style="background:var(--surface2);padding:12px 16px;border-radius:var(--radius-sm);margin-bottom:16px;">
            <div class="text-small text-muted">Your message · <?= timeAgo($viewTicket['created_at']) ?></div>
            <p style="margin:8px 0 0;"><?= nl2br(htmlspecialchars($viewTicket['message'])) ?></p>
        </div>
        <?php foreach ($viewTicket['replies'] ?? [] as $reply): ?>
        <div style="background:<?= $reply['from'] !== 'user' ? 'color-mix(in srgb,var(--primary) 10%,transparent)' : 'var(--surface2)' ?>;padding:12px 16px;border-radius:var(--radius-sm);margin-bottom:10px;<?= $reply['from'] !== 'user' ? 'border-left:3px solid var(--primary);' : '' ?>">
            <div class="text-small text-muted"><strong><?= $reply['from'] !== 'user' ? '🛡️ Support' : '👤 You' ?></strong> · <?= timeAgo($reply['at']) ?></div>
            <p style="margin:6px 0 0;"><?= nl2br(htmlspecialchars($reply['message'])) ?></p>
        </div>
        <?php endforeach; ?>
        <?php if ($viewTicket['status'] !== 'closed'): ?>
        <form method="POST" style="margin-top:16px;">
            <?php csrfField(); ?><input type="hidden" name="action" value="reply"><input type="hidden" name="id" value="<?= $viewTicket['id'] ?>">
            <div class="form-group"><textarea class="form-control" name="message" rows="4" placeholder="Type your reply..." required></textarea></div>
            <button type="submit" class="btn btn-primary">Send Reply</button>
        </form>
        <?php else: ?>
        <div class="alert alert-info">This ticket is closed. Open a new ticket if you need further assistance.</div>
        <?php endif; ?>
    </div>
</div>
<?php elseif ($showNew): ?>
<div style="margin-bottom:16px;"><a href="<?= BASE_URL ?>user/tickets.php" class="btn btn-secondary">← My Tickets</a></div>
<div class="card animate-in" style="max-width:700px;">
    <div class="card-header"><span class="card-title">➕ Open New Ticket</span></div>
    <form method="POST"><div class="card-body">
        <?php csrfField(); ?><input type="hidden" name="action" value="create">
        <div class="form-group"><label>Subject</label><input type="text" class="form-control" name="subject" required></div>
        <div class="form-group">
            <label>Priority</label>
            <select class="form-control" name="priority">
                <option value="low">Low</option>
                <option value="normal" selected>Normal</option>
                <option value="high">High</option>
            </select>
        </div>
        <div class="form-group"><label>Message</label><textarea class="form-control" name="message" rows="6" placeholder="Describe your issue in detail..." required></textarea></div>
    </div>
    <div class="modal-footer"><a href="<?= BASE_URL ?>user/tickets.php" class="btn btn-secondary">Cancel</a><button type="submit" class="btn btn-primary">Submit Ticket</button></div></form>
</div>
<?php else: ?>
<div class="section-header">
    <h2 class="section-title">My Tickets (<?= count($myTickets) ?>)</h2>
    <a href="?new=1" class="btn btn-primary">➕ New Ticket</a>
</div>
<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Subject</th><th>Status</th><th>Priority</th><th>Updated</th><th></th></tr></thead>
            <tbody>
            <?php if (empty($myTickets)): ?>
            <tr><td colspan="5"><div class="empty-state"><span class="empty-icon">🎫</span><h3>No tickets yet</h3><a href="?new=1" class="btn btn-primary">Open a Ticket</a></div></td></tr>
            <?php else: foreach ($myTickets as $t): ?>
            <tr>
                <td><strong><?= htmlspecialchars($t['subject']) ?></strong></td>
                <td><?= getStatusBadge($t['status']) ?></td>
                <td><span class="badge badge-<?= $t['priority'] === 'high' ? 'danger' : ($t['priority'] === 'normal' ? 'warning' : 'info') ?>"><?= ucfirst($t['priority'] ?? 'normal') ?></span></td>
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
