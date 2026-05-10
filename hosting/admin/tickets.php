<?php
require_once __DIR__ . '/../includes/layout.php';
hostingRequireRole('admin');
$admin = hostingGetCurrentUser();
$tickets = loadHostingData('tickets');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticketId = trim($_POST['ticket_id'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $status = trim($_POST['status'] ?? 'answered');

    foreach ($tickets as &$ticket) {
        if (($ticket['id'] ?? '') === $ticketId) {
            if ($message !== '') {
                $ticket['messages'][] = [
                    'by' => $admin['name'],
                    'role' => 'admin',
                    'message' => $message,
                    'created_at' => date('Y-m-d H:i:s')
                ];
            }
            $ticket['status'] = in_array($status, ['open', 'answered', 'closed'], true) ? $status : 'answered';
            $ticket['updated_at'] = date('Y-m-d H:i:s');
            hostingSendAutoMail($ticket['user_email'], 'Ticket updated by admin', "Hi {$ticket['user_name']},\n\nYour ticket {$ticket['id']} has an update.\nStatus: {$ticket['status']}\n\nAdmin message:\n{$message}", ['event' => 'ticket_admin_update', 'ticket_id' => $ticket['id']]);
            hostingSetFlash('success', "Ticket {$ticket['id']} updated.");
            break;
        }
    }
    unset($ticket);

    saveHostingData('tickets', $tickets);
    header('Location: ' . hostingGetBaseUrl() . '/admin/tickets.php');
    exit;
}

hostingLayoutStart('Manage Tickets - CodexHost');
?>
<h2 class="fw-bold mb-4">Support Tickets</h2>
<?php if (empty($tickets)): ?>
    <div class="alert alert-info">No tickets found.</div>
<?php else: foreach (array_reverse($tickets) as $ticket): ?>
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span><strong><?php echo hostingSanitize($ticket['id']); ?></strong> — <?php echo hostingSanitize($ticket['subject']); ?> (<?php echo hostingSanitize($ticket['user_name']); ?>)</span>
            <span class="badge bg-<?php echo ($ticket['status'] === 'closed') ? 'secondary' : (($ticket['status'] === 'answered') ? 'success' : 'warning text-dark'); ?>"><?php echo ucfirst(hostingSanitize($ticket['status'])); ?></span>
        </div>
        <div class="card-body">
            <?php foreach (($ticket['messages'] ?? []) as $msg): ?>
                <div class="border rounded p-2 mb-2">
                    <div class="small text-muted"><?php echo hostingSanitize($msg['role']); ?> • <?php echo hostingSanitize($msg['by']); ?> • <?php echo hostingSanitize($msg['created_at']); ?></div>
                    <div><?php echo nl2br(hostingSanitize($msg['message'])); ?></div>
                </div>
            <?php endforeach; ?>
            <form method="POST" class="mt-3 row g-2">
                <input type="hidden" name="ticket_id" value="<?php echo hostingSanitize($ticket['id']); ?>">
                <div class="col-md-7">
                    <textarea class="form-control" name="message" rows="2" placeholder="Reply to user (optional if only changing status)"></textarea>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="status">
                        <option value="answered">Answered</option>
                        <option value="open">Open</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
                <div class="col-md-2"><button class="btn btn-primary w-100">Update</button></div>
            </form>
        </div>
    </div>
<?php endforeach; endif; ?>
<?php hostingLayoutEnd(); ?>
