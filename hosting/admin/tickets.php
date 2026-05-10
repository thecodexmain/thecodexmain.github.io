<?php
require_once __DIR__ . '/../includes/auth.php';
hostingRequireRole(['admin']);
$baseUrl = hostingGetBaseUrl();

$admin = hostingCurrentUser();
$tickets = hostingLoadData('tickets');

$ticketId = (string)($_GET['id'] ?? '');
$current = null;
if ($ticketId !== '') {
    foreach ($tickets as $t) {
        if (($t['id'] ?? '') === $ticketId) { $current = $t; break; }
    }
    if (!$current) {
        hostingSetFlash('error', 'Ticket not found.');
        header('Location: ' . hostingGetBaseUrl() . '/admin/tickets.php');
        exit;
    }
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    hostingVerifyCsrf();
    $action = (string)($_POST['action'] ?? '');
    $id = (string)($_POST['ticket_id'] ?? '');

    $idx = null;
    foreach ($tickets as $i => $t) {
        if (($t['id'] ?? '') === $id) { $idx = $i; break; }
    }
    if ($idx === null) {
        $error = 'Ticket not found.';
    } else {
        if ($action === 'reply') {
            $message = trim((string)($_POST['message'] ?? ''));
            if ($message === '') {
                $error = 'Message cannot be empty.';
            } else {
                $tickets[$idx]['messages'] = $tickets[$idx]['messages'] ?? [];
                $tickets[$idx]['messages'][] = [
                    'id' => 'M-' . hostingGenerateId(),
                    'author_role' => 'admin',
                    'author_id' => $admin['id'],
                    'body' => $message,
                    'created_at' => hostingNow()
                ];
                $tickets[$idx]['updated_at'] = hostingNow();
                $tickets[$idx]['status'] = 'open';
                hostingSaveData('tickets', $tickets);
                hostingSetFlash('success', 'Reply sent.');
                header('Location: ' . hostingGetBaseUrl() . '/admin/tickets.php?id=' . urlencode($id));
                exit;
            }
        }

        if ($action === 'close') {
            $tickets[$idx]['status'] = 'closed';
            $tickets[$idx]['updated_at'] = hostingNow();
            hostingSaveData('tickets', $tickets);
            hostingSetFlash('success', 'Ticket closed.');
            header('Location: ' . hostingGetBaseUrl() . '/admin/tickets.php?id=' . urlencode($id));
            exit;
        }

        if ($action === 'open') {
            $tickets[$idx]['status'] = 'open';
            $tickets[$idx]['updated_at'] = hostingNow();
            hostingSaveData('tickets', $tickets);
            hostingSetFlash('success', 'Ticket reopened.');
            header('Location: ' . hostingGetBaseUrl() . '/admin/tickets.php?id=' . urlencode($id));
            exit;
        }
    }
}

$users = hostingLoadData('users');
?>
<?php require __DIR__ . '/../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h4 fw-bold mb-1">Tickets</h1>
        <div class="text-muted">Reply to customer tickets.</div>
    </div>
    <a class="btn btn-sm btn-outline-secondary" href="<?php echo $baseUrl; ?>/admin/dashboard.php"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if ($current): ?>
    <?php
    $uName = '';
    $uEmail = '';
    foreach ($users as $u) {
        if (($u['id'] ?? '') === ($current['user_id'] ?? '')) { $uName = $u['name'] ?? ''; $uEmail = $u['email'] ?? ''; break; }
    }
    ?>
    <div class="card mb-3">
        <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
            <div class="fw-semibold">
                <?php echo htmlspecialchars($current['subject'] ?? ''); ?>
                <span class="badge ms-2 text-bg-<?php echo ($current['status'] ?? 'open') === 'open' ? 'success' : 'secondary'; ?>">
                    <?php echo htmlspecialchars(ucfirst($current['status'] ?? 'open')); ?>
                </span>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-sm btn-outline-secondary" href="<?php echo $baseUrl; ?>/admin/tickets.php"><i class="bi bi-list me-1"></i>All tickets</a>
                <form method="post" action="" class="m-0">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(hostingCsrfToken()); ?>">
                    <input type="hidden" name="ticket_id" value="<?php echo htmlspecialchars($current['id'] ?? ''); ?>">
                    <input type="hidden" name="action" value="<?php echo ($current['status'] ?? 'open') === 'open' ? 'close' : 'open'; ?>">
                    <button class="btn btn-sm btn-outline-<?php echo ($current['status'] ?? 'open') === 'open' ? 'danger' : 'success'; ?>" type="submit">
                        <?php echo ($current['status'] ?? 'open') === 'open' ? 'Close' : 'Reopen'; ?>
                    </button>
                </form>
            </div>
        </div>
        <div class="card-body">
            <div class="small text-muted mb-3">
                Customer: <span class="fw-semibold"><?php echo htmlspecialchars($uName ?: ($current['user_id'] ?? '')); ?></span>
                <?php if ($uEmail): ?> • <?php echo htmlspecialchars($uEmail); ?><?php endif; ?>
            </div>
            <?php foreach (($current['messages'] ?? []) as $m): ?>
                <div class="border rounded-3 p-3 mb-2 <?php echo ($m['author_role'] ?? '') === 'admin' ? 'bg-light' : ''; ?>">
                    <div class="d-flex justify-content-between gap-2">
                        <div class="fw-semibold small"><?php echo ($m['author_role'] ?? '') === 'admin' ? 'You (Admin)' : 'Customer'; ?></div>
                        <div class="text-muted small"><?php echo htmlspecialchars($m['created_at'] ?? ''); ?></div>
                    </div>
                    <div class="mt-2"><?php echo nl2br(htmlspecialchars($m['body'] ?? '')); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="card-footer bg-transparent">
            <form method="post" action="" class="row g-2">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(hostingCsrfToken()); ?>">
                <input type="hidden" name="ticket_id" value="<?php echo htmlspecialchars($current['id'] ?? ''); ?>">
                <input type="hidden" name="action" value="reply">
                <div class="col-12">
                    <textarea class="form-control" name="message" rows="3" placeholder="Write your reply..." required></textarea>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-reply me-1"></i>Send reply</button>
                </div>
            </form>
        </div>
    </div>
<?php else: ?>
    <?php
    usort($tickets, fn($a, $b) => strcmp((string)($b['updated_at'] ?? ''), (string)($a['updated_at'] ?? '')));
    ?>
    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>ID</th><th>Subject</th><th>Customer</th><th>Status</th><th>Updated</th><th class="text-end"></th></tr></thead>
                <tbody>
                <?php if (empty($tickets)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No tickets yet.</td></tr>
                <?php else: ?>
                    <?php foreach (array_slice($tickets, 0, 50) as $t): ?>
                        <?php
                        $uName = '';
                        foreach ($users as $u) { if (($u['id'] ?? '') === ($t['user_id'] ?? '')) { $uName = $u['name'] ?? ''; break; } }
                        ?>
                        <tr>
                            <td class="text-muted small"><?php echo htmlspecialchars($t['id'] ?? ''); ?></td>
                            <td class="fw-semibold"><?php echo htmlspecialchars($t['subject'] ?? ''); ?></td>
                            <td class="text-muted"><?php echo htmlspecialchars($uName ?: ($t['user_id'] ?? '')); ?></td>
                            <td><span class="badge text-bg-<?php echo ($t['status'] ?? 'open') === 'open' ? 'success' : 'secondary'; ?>"><?php echo htmlspecialchars(ucfirst($t['status'] ?? 'open')); ?></span></td>
                            <td class="text-muted small"><?php echo htmlspecialchars($t['updated_at'] ?? ''); ?></td>
                            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?php echo $baseUrl; ?>/admin/tickets.php?id=<?php echo urlencode($t['id'] ?? ''); ?>">View</a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
