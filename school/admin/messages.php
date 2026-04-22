<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['super_admin', 'admin']);

$baseUrl = getBaseUrl();
$currentUser = getCurrentUser();

// Delete
if (isset($_GET['action']) && $_GET['action']==='delete') {
    $msgs = loadData('messages');
    $msgs = array_values(array_filter($msgs, fn($m) => $m['id']!==sanitize($_GET['id'])));
    saveData('messages', $msgs);
    setFlash('success', 'Message deleted.');
    header('Location: '.$baseUrl.'/admin/messages.php'); exit;
}

// Send message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to      = sanitize($_POST['to_role']  ?? '');
    $subject = sanitize($_POST['subject']  ?? '');
    $body    = sanitize($_POST['body']     ?? '');

    $msgs = loadData('messages');
    $msgs[] = [
        'id'         => generateId('MSG'),
        'from_id'    => $currentUser['id'],
        'from_name'  => $currentUser['name'],
        'to_role'    => $to,
        'subject'    => $subject,
        'body'       => $body,
        'created_at' => date('Y-m-d H:i:s'),
        'read'       => false
    ];
    saveData('messages', $msgs);
    setFlash('success', 'Message sent!');
    header('Location: '.$baseUrl.'/admin/messages.php'); exit;
}

$messages = loadData('messages');
usort($messages, fn($a,$b) => strcmp($b['created_at']??'',$a['created_at']??''));

$pageTitle = 'Messages';
include __DIR__ . '/../includes/header.php';
?>
<div class="wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="page-header"><h2><i class="bi bi-chat-dots text-theme"></i> Messages</h2></div>
    <?php echo renderFlash(); ?>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">Send Message</div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Send To</label>
                            <select class="form-select" name="to_role" required>
                                <option value="all">All Users</option>
                                <option value="students">All Students</option>
                                <option value="teachers">All Teachers</option>
                                <option value="parents">All Parents</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Subject <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="subject" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Message <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="body" rows="5" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-send"></i> Send Message</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">Sent Messages (<?php echo count($messages); ?>)</div>
                <div class="card-body p-0">
                    <?php if (empty($messages)): ?>
                        <p class="text-muted text-center py-4">No messages sent yet.</p>
                    <?php else: foreach ($messages as $m): ?>
                        <div class="p-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fw-semibold"><?php echo htmlspecialchars($m['subject']); ?></div>
                                    <div class="text-muted small"><?php echo htmlspecialchars($m['body']); ?></div>
                                    <small class="text-muted mt-1 d-block">
                                        <i class="bi bi-person"></i> From: <?php echo htmlspecialchars($m['from_name']??''); ?>
                                        &bull; <i class="bi bi-people"></i> To: <?php echo ucfirst($m['to_role']??''); ?>
                                        &bull; <i class="bi bi-clock"></i> <?php echo htmlspecialchars($m['created_at']??''); ?>
                                    </small>
                                </div>
                                <a href="?action=delete&id=<?php echo urlencode($m['id']); ?>" class="btn btn-sm btn-outline-danger btn-delete ms-3"><i class="bi bi-trash"></i></a>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
