<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mailer.php';
hostingRequireRole(['user']);

$baseUrl = hostingGetBaseUrl();
$user = hostingCurrentUser();
$tickets = hostingLoadData('tickets');

$ticketId = (string)($_GET['id'] ?? '');
$current = null;
if ($ticketId !== '') {
    foreach ($tickets as $t) {
        if (($t['id'] ?? '') === $ticketId && ($t['user_id'] ?? '') === $user['id']) {
            $current = $t;
            break;
        }
    }
    if (!$current) {
        hostingSetFlash('error', 'Ticket not found.');
        header('Location: ' . hostingGetBaseUrl() . '/user/tickets.php');
        exit;
    }
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    hostingVerifyCsrf();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'create') {
        $subject = trim((string)($_POST['subject'] ?? ''));
        $message = trim((string)($_POST['message'] ?? ''));
        if ($subject === '' || $message === '') {
            $error = 'Subject and message are required.';
        } else {
            $id = 'T-' . hostingGenerateId();
            $tickets[] = [
                'id' => $id,
                'user_id' => $user['id'],
                'subject' => $subject,
                'status' => 'open',
                'created_at' => hostingNow(),
                'updated_at' => hostingNow(),
                'messages' => [[
                    'id' => 'M-' . hostingGenerateId(),
                    'author_role' => 'user',
                    'author_id' => $user['id'],
                    'body' => $message,
                    'created_at' => hostingNow()
                ]]
            ];
            hostingSaveData('tickets', $tickets);
            $settings = hostingGetSettings();
            $support = (string)($settings['support_email'] ?? '');
            if ($support) {
                hostingQueueMail($support, 'New ticket: ' . $subject, "A new ticket was created.\n\nCustomer: {$user['name']} ({$user['email']})\nTicket: {$id}\nSubject: {$subject}\n\nOpen: " . hostingGetBaseUrl() . "/admin/tickets.php?id={$id}\n\nMessage:\n{$message}");
                hostingDispatchMailQueue(5);
            }
            hostingSetFlash('success', 'Ticket created.');
            header('Location: ' . hostingGetBaseUrl() . '/user/tickets.php?id=' . urlencode($id));
            exit;
        }
    }

    if ($action === 'reply' && $ticketId !== '') {
        $message = trim((string)($_POST['message'] ?? ''));
        if ($message === '') {
            $error = 'Message cannot be empty.';
        } else {
            foreach ($tickets as &$t) {
                if (($t['id'] ?? '') !== $ticketId || ($t['user_id'] ?? '') !== $user['id']) continue;
                $t['messages'] = $t['messages'] ?? [];
                $t['messages'][] = [
                    'id' => 'M-' . hostingGenerateId(),
                    'author_role' => 'user',
                    'author_id' => $user['id'],
                    'body' => $message,
                    'created_at' => hostingNow()
                ];
                $t['updated_at'] = hostingNow();
                break;
            }
            unset($t);
            hostingSaveData('tickets', $tickets);
            $settings = hostingGetSettings();
            $support = (string)($settings['support_email'] ?? '');
            if ($support) {
                hostingQueueMail($support, 'Ticket reply: ' . $ticketId, "Customer replied to ticket {$ticketId}.\n\nCustomer: {$user['name']} ({$user['email']})\n\nMessage:\n{$message}\n\nOpen: " . hostingGetBaseUrl() . "/admin/tickets.php?id={$ticketId}");
                hostingDispatchMailQueue(5);
            }
            hostingSetFlash('success', 'Reply sent.');
            header('Location: ' . hostingGetBaseUrl() . '/user/tickets.php?id=' . urlencode($ticketId));
            exit;
        }
    }

    if ($action === 'close' && $ticketId !== '') {
        foreach ($tickets as &$t) {
            if (($t['id'] ?? '') !== $ticketId || ($t['user_id'] ?? '') !== $user['id']) continue;
            $t['status'] = 'closed';
            $t['updated_at'] = hostingNow();
            break;
        }
        unset($t);
        hostingSaveData('tickets', $tickets);
        hostingSetFlash('success', 'Ticket closed.');
        header('Location: ' . hostingGetBaseUrl() . '/user/tickets.php?id=' . urlencode($ticketId));
        exit;
    }
}

$myTickets = array_values(array_filter($tickets, fn($t) => ($t['user_id'] ?? '') === $user['id']));
?>
<?php require __DIR__ . '/../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h4 fw-bold mb-1">Tickets</h1>
        <div class="text-muted">Open a ticket and track replies.</div>
    </div>
    <a class="btn btn-sm btn-outline-secondary" href="<?php echo $baseUrl; ?>/user/dashboard.php"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if ($current): ?>
    <div class="card mb-3">
        <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
            <div class="fw-semibold">
                <?php echo htmlspecialchars($current['subject'] ?? ''); ?>
                <span class="badge ms-2 text-bg-<?php echo ($current['status'] ?? 'open') === 'open' ? 'success' : 'secondary'; ?>">
                    <?php echo htmlspecialchars(ucfirst($current['status'] ?? 'open')); ?>
                </span>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-sm btn-outline-secondary" href="<?php echo $baseUrl; ?>/user/tickets.php"><i class="bi bi-list me-1"></i>All tickets</a>
                <?php if (($current['status'] ?? 'open') === 'open'): ?>
                    <form method="post" action="" class="m-0">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(hostingCsrfToken()); ?>">
                        <input type="hidden" name="action" value="close">
                        <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-x-circle me-1"></i>Close</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <?php foreach (($current['messages'] ?? []) as $m): ?>
                <div class="border rounded-3 p-3 mb-2 <?php echo ($m['author_role'] ?? '') === 'admin' ? 'bg-light' : ''; ?>">
                    <div class="d-flex justify-content-between gap-2">
                        <div class="fw-semibold small">
                            <?php echo ($m['author_role'] ?? '') === 'admin' ? 'Support' : 'You'; ?>
                        </div>
                        <div class="text-muted small"><?php echo htmlspecialchars($m['created_at'] ?? ''); ?></div>
                    </div>
                    <div class="mt-2"><?php echo nl2br(htmlspecialchars($m['body'] ?? '')); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if (($current['status'] ?? 'open') === 'open'): ?>
            <div class="card-footer bg-transparent">
                <form method="post" action="" class="row g-2">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(hostingCsrfToken()); ?>">
                    <input type="hidden" name="action" value="reply">
                    <div class="col-12">
                        <textarea class="form-control" name="message" rows="3" placeholder="Write your reply..." required></textarea>
                    </div>
                    <div class="col-12 d-flex justify-content-end">
                        <button class="btn btn-primary" type="submit"><i class="bi bi-reply me-1"></i>Send reply</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header bg-transparent fw-semibold">Your tickets</div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>ID</th><th>Subject</th><th>Status</th><th>Updated</th><th></th></tr></thead>
                        <tbody>
                        <?php if (empty($myTickets)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No tickets yet.</td></tr>
                        <?php else: ?>
                            <?php foreach (array_slice(array_reverse($myTickets), 0, 20) as $t): ?>
                                <tr>
                                    <td class="text-muted small"><?php echo htmlspecialchars($t['id'] ?? ''); ?></td>
                                    <td class="fw-semibold"><?php echo htmlspecialchars($t['subject'] ?? ''); ?></td>
                                    <td><span class="badge text-bg-<?php echo ($t['status'] ?? 'open') === 'open' ? 'success' : 'secondary'; ?>"><?php echo htmlspecialchars(ucfirst($t['status'] ?? 'open')); ?></span></td>
                                    <td class="text-muted small"><?php echo htmlspecialchars($t['updated_at'] ?? ''); ?></td>
                                    <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?php echo $baseUrl; ?>/user/tickets.php?id=<?php echo urlencode($t['id'] ?? ''); ?>">View</a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header bg-transparent fw-semibold">Open a ticket</div>
                <div class="card-body">
                    <form method="post" action="" class="row g-2">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(hostingCsrfToken()); ?>">
                        <input type="hidden" name="action" value="create">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Subject</label>
                            <input class="form-control" name="subject" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Message</label>
                            <textarea class="form-control" name="message" rows="4" required></textarea>
                        </div>
                        <div class="col-12 d-flex justify-content-end">
                            <button class="btn btn-primary" type="submit"><i class="bi bi-send me-1"></i>Create ticket</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
