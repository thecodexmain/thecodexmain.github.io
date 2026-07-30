<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';

requireRole('admin');

$success = $error = '';
$viewTicket = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $action = $_POST['action'] ?? '';
        $tickets = readJson(DATA_PATH . 'tickets.json');

        if ($action === 'reply') {
            $id = sanitize($_POST['id'] ?? '');
            $message = trim($_POST['message'] ?? '');
            $status = in_array($_POST['status'] ?? '', ['open', 'closed', 'answered']) ? $_POST['status'] : 'answered';
            if (empty($message)) { $error = 'Reply message cannot be empty.'; }
            else {
                foreach ($tickets as &$t) {
                    if ($t['id'] === $id) {
                        $t['messages'][] = [
                            'author' => $_SESSION['username'],
                            'role' => 'admin',
                            'message' => htmlspecialchars($message),
                            'created_at' => date('c'),
                        ];
                        $t['status'] = $status;
                        $t['updated_at'] = date('c');
                        break;
                    }
                }
                unset($t);
                writeJson(DATA_PATH . 'tickets.json', $tickets);
                addNotification($id, 'Ticket Update', 'Admin replied to your ticket.', 'info');
                appLog('reply_ticket', $_SESSION['username'], "Replied to ticket: {$id}");
                $success = 'Reply sent.';
            }

        } elseif ($action === 'close') {
            $id = sanitize($_POST['id'] ?? '');
            foreach ($tickets as &$t) {
                if ($t['id'] === $id) { $t['status'] = 'closed'; $t['updated_at'] = date('c'); break; }
            }
            unset($t);
            writeJson(DATA_PATH . 'tickets.json', $tickets);
            $success = 'Ticket closed.';

        } elseif ($action === 'delete') {
            $id = sanitize($_POST['id'] ?? '');
            deleteById($tickets, $id);
            writeJson(DATA_PATH . 'tickets.json', $tickets);
            $success = 'Ticket deleted.';
        }
    }
}

if (isset($_GET['view'])) {
    $tickets = readJson(DATA_PATH . 'tickets.json');
    $viewTicket = findById($tickets, sanitize($_GET['view']));
}

$tickets = readJson(DATA_PATH . 'tickets.json');
$tickets = array_reverse($tickets);

renderHead('Tickets');
renderSidebar('admin', 'tickets');
renderTopbar('Support Tickets');
?>

<?php if ($success) renderAlert('success', $success); ?>
<?php if ($error) renderAlert('danger', $error); ?>

<?php if ($viewTicket): ?>
<!-- Ticket View -->
<div style="margin-bottom:12px;"><a href="<?= BASE_URL ?>admin/tickets.php" class="btn btn-secondary">← Back to Tickets</a></div>
<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title"><?= htmlspecialchars($viewTicket['subject'] ?? 'No Subject') ?></div>
            <div class="text-small text-muted">Ticket #<?= htmlspecialchars($viewTicket['id']) ?> · <?= htmlspecialchars($viewTicket['username'] ?? '') ?></div>
        </div>
        <div style="display:flex;gap:8px;align-items:center;">
            <?= getStatusBadge($viewTicket['status'] ?? 'open') ?>
            <span class="badge-status badge-pending"><?= ucfirst($viewTicket['priority'] ?? 'normal') ?></span>
            <form method="POST" style="display:inline;">
                <?php csrfField(); ?><input type="hidden" name="action" value="close"><input type="hidden" name="id" value="<?= $viewTicket['id'] ?>">
                <button class="btn btn-sm btn-secondary">🔒 Close</button>
            </form>
        </div>
    </div>
    <div class="card-body">
        <div style="max-height:400px;overflow-y:auto;margin-bottom:16px;">
        <?php foreach ($viewTicket['messages'] ?? [] as $msg): ?>
        <div class="ticket-message <?= $msg['role'] === 'admin' ? 'admin-msg' : 'user-msg' ?>">
            <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                <strong><?= htmlspecialchars($msg['author']) ?></strong>
                <span class="badge-status <?= $msg['role'] === 'admin' ? 'badge-info' : 'badge-active' ?>"><?= ucfirst($msg['role']) ?></span>
            </div>
            <div class="ticket-content"><?= htmlspecialchars($msg['message']) ?></div>
            <div class="ticket-meta"><?= date('M j, Y H:i', strtotime($msg['created_at'])) ?></div>
        </div>
        <?php endforeach; ?>
        </div>

        <?php if (($viewTicket['status'] ?? 'open') !== 'closed'): ?>
        <form method="POST">
            <?php csrfField(); ?><input type="hidden" name="action" value="reply"><input type="hidden" name="id" value="<?= $viewTicket['id'] ?>">
            <div class="form-group">
                <label>Reply</label>
                <textarea class="form-control" name="message" rows="3" placeholder="Type your reply..." required></textarea>
            </div>
            <div style="display:flex;gap:8px;align-items:center;">
                <select class="form-control" name="status" style="width:auto;">
                    <option value="answered">Mark as Answered</option>
                    <option value="open">Keep Open</option>
                    <option value="closed">Close Ticket</option>
                </select>
                <button type="submit" class="btn btn-primary">📨 Send Reply</button>
            </div>
        </form>
        <?php else: ?>
        <div class="alert alert-info">ℹ️ This ticket is closed.</div>
        <?php endif; ?>
    </div>
</div>

<?php else: ?>
<!-- Ticket List -->
<div class="section-header">
    <h2 class="section-title">All Tickets (<?= count($tickets) ?>)</h2>
    <div style="display:flex;gap:8px;">
        <select class="form-control" id="statusFilter" style="width:auto;" onchange="filterTickets()">
            <option value="">All Status</option>
            <option value="open">Open</option>
            <option value="answered">Answered</option>
            <option value="closed">Closed</option>
        </select>
        <div class="search-bar"><input type="text" class="form-control" id="tableSearch" placeholder="Search..."></div>
    </div>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Subject</th><th>User</th><th>Priority</th><th>Status</th><th>Updated</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($tickets)): ?>
            <tr><td colspan="6"><div class="empty-state"><span class="empty-icon">🎫</span><h3>No tickets yet</h3></div></td></tr>
            <?php else: foreach ($tickets as $t): ?>
            <tr data-status="<?= $t['status'] ?? 'open' ?>">
                <td>
                    <a href="?view=<?= $t['id'] ?>" style="font-weight:600;"><?= htmlspecialchars($t['subject'] ?? 'No Subject') ?></a>
                    <div class="text-small text-muted">#<?= htmlspecialchars($t['id']) ?></div>
                </td>
                <td><?= htmlspecialchars($t['username'] ?? '—') ?></td>
                <td><span class="badge-status <?= ($t['priority'] ?? 'normal') === 'high' ? 'badge-inactive' : 'badge-pending' ?>"><?= ucfirst($t['priority'] ?? 'normal') ?></span></td>
                <td><?= getStatusBadge($t['status'] ?? 'open') ?></td>
                <td class="text-small text-muted"><?= timeAgo($t['updated_at'] ?? $t['created_at']) ?></td>
                <td>
                    <div style="display:flex;gap:4px;">
                        <a href="?view=<?= $t['id'] ?>" class="btn btn-sm btn-secondary">👁️ View</a>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this ticket?')">
                            <?php csrfField(); ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $t['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger">🗑️</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php renderFooter(); ?>
<script>
function filterTickets() {
    const v = document.getElementById('statusFilter').value;
    document.querySelectorAll('tbody tr[data-status]').forEach(r => {
        r.style.display = !v || r.dataset.status === v ? '' : 'none';
    });
}
</script>
