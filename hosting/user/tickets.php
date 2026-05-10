<?php
require_once __DIR__ . '/../includes/layout.php';
hostingRequireRole('user');
$user = hostingGetCurrentUser();
$tickets = loadHostingData('tickets');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if ($subject === '' || $message === '') {
            hostingSetFlash('danger', 'Subject and message are required.');
        } else {
            $ticket = [
                'id' => hostingGenerateId('TCK-'),
                'user_id' => $user['id'],
                'user_name' => $user['name'],
                'user_email' => $user['email'],
                'subject' => $subject,
                'status' => 'open',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'messages' => [[
                    'by' => $user['name'],
                    'role' => 'user',
                    'message' => $message,
                    'created_at' => date('Y-m-d H:i:s')
                ]]
            ];
            $tickets[] = $ticket;
            saveHostingData('tickets', $tickets);
            foreach (hostingAdminEmails() as $adminEmail) {
                hostingSendAutoMail($adminEmail, 'New support ticket created', "Ticket {$ticket['id']} created by {$user['name']} with subject: {$subject}", ['event' => 'ticket_created', 'ticket_id' => $ticket['id']]);
            }
            hostingSetFlash('success', 'Ticket created successfully.');
            header('Location: ' . hostingGetBaseUrl() . '/user/tickets.php');
            exit;
        }
    }

    if ($action === 'reply') {
        $ticketId = trim($_POST['ticket_id'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if ($ticketId === '' || $message === '') {
            hostingSetFlash('danger', 'Invalid ticket reply.');
        } else {
            foreach ($tickets as &$ticket) {
                if (($ticket['id'] ?? '') === $ticketId && ($ticket['user_id'] ?? '') === $user['id']) {
                    $ticket['messages'][] = [
                        'by' => $user['name'],
                        'role' => 'user',
                        'message' => $message,
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    $ticket['status'] = 'open';
                    $ticket['updated_at'] = date('Y-m-d H:i:s');
                    foreach (hostingAdminEmails() as $adminEmail) {
                        hostingSendAutoMail($adminEmail, 'Ticket reply from user', "New reply on {$ticketId} from {$user['name']}", ['event' => 'ticket_reply_user', 'ticket_id' => $ticketId]);
                    }
                    break;
                }
            }
            unset($ticket);
            saveHostingData('tickets', $tickets);
            hostingSetFlash('success', 'Reply added to ticket.');
            header('Location: ' . hostingGetBaseUrl() . '/user/tickets.php');
            exit;
        }
    }
}

$userTickets = array_values(array_filter($tickets, fn($t) => ($t['user_id'] ?? '') === $user['id']));
hostingLayoutStart('Support Tickets - CodexHost');
?>
<h2 class="fw-bold mb-4">Support Tickets</h2>
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Create New Ticket</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3"><label class="form-label">Subject</label><input name="subject" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Message</label><textarea name="message" class="form-control" rows="5" required></textarea></div>
                    <button class="btn btn-primary">Submit Ticket</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <?php if (empty($userTickets)): ?>
            <div class="alert alert-info">No tickets yet.</div>
        <?php else: foreach (array_reverse($userTickets) as $ticket): ?>
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span><strong><?php echo hostingSanitize($ticket['id']); ?></strong> — <?php echo hostingSanitize($ticket['subject']); ?></span>
                    <span class="badge bg-<?php echo ($ticket['status'] === 'closed') ? 'secondary' : (($ticket['status'] === 'answered') ? 'success' : 'warning text-dark'); ?>"><?php echo ucfirst(hostingSanitize($ticket['status'])); ?></span>
                </div>
                <div class="card-body">
                    <?php foreach (($ticket['messages'] ?? []) as $msg): ?>
                        <div class="border rounded p-2 mb-2">
                            <div class="small text-muted"><?php echo hostingSanitize($msg['role']); ?> • <?php echo hostingSanitize($msg['by']); ?> • <?php echo hostingSanitize($msg['created_at']); ?></div>
                            <div><?php echo nl2br(hostingSanitize($msg['message'])); ?></div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (($ticket['status'] ?? '') !== 'closed'): ?>
                        <form method="POST" class="mt-3">
                            <input type="hidden" name="action" value="reply">
                            <input type="hidden" name="ticket_id" value="<?php echo hostingSanitize($ticket['id']); ?>">
                            <label class="form-label">Add Reply</label>
                            <textarea name="message" class="form-control mb-2" rows="3" required></textarea>
                            <button class="btn btn-outline-primary btn-sm">Send Reply</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>
</div>
<?php hostingLayoutEnd(); ?>
