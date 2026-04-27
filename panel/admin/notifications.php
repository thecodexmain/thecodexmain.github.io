<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';

requireRole('admin');

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'broadcast') {
            $title = sanitize($_POST['title'] ?? '');
            $message = sanitize($_POST['message'] ?? '');
            $type = in_array($_POST['type'] ?? '', ['info', 'success', 'warning', 'danger']) ? $_POST['type'] : 'info';
            $target = $_POST['target'] ?? 'all';

            if (empty($title) || empty($message)) { $error = 'Title and message required.'; }
            else {
                $users = readJson(DATA_PATH . 'users.json');
                $count = 0;
                foreach ($users as $u) {
                    if ($target === 'all' || ($target === 'active' && $u['status'] === 'active')) {
                        addNotification($u['id'], $title, $message, $type);
                        $count++;
                    }
                }
                appLog('broadcast_notification', $_SESSION['username'], "Sent to {$count} users: {$title}");
                $success = "Notification sent to {$count} users.";
            }

        } elseif ($action === 'send_to_user') {
            $userId = sanitize($_POST['user_id'] ?? '');
            $title = sanitize($_POST['title'] ?? '');
            $message = sanitize($_POST['message'] ?? '');
            $type = in_array($_POST['type'] ?? '', ['info', 'success', 'warning', 'danger']) ? $_POST['type'] : 'info';

            if (empty($userId) || empty($title) || empty($message)) { $error = 'All fields required.'; }
            else {
                addNotification($userId, $title, $message, $type);
                $success = 'Notification sent.';
            }

        } elseif ($action === 'delete') {
            $id = sanitize($_POST['id'] ?? '');
            $notifications = readJson(DATA_PATH . 'notifications.json');
            deleteById($notifications, $id);
            writeJson(DATA_PATH . 'notifications.json', $notifications);
            $success = 'Notification deleted.';

        } elseif ($action === 'clear_all') {
            writeJson(DATA_PATH . 'notifications.json', []);
            $success = 'All notifications cleared.';
        }
    }
}

$notifications = readJson(DATA_PATH . 'notifications.json');
$notifications = array_reverse($notifications);
$users = readJson(DATA_PATH . 'users.json');

renderHead('Notifications');
renderSidebar('admin', 'notifications');
renderTopbar('Notifications');
?>

<?php if ($success) renderAlert('success', $success); ?>
<?php if ($error) renderAlert('danger', $error); ?>

<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-header"><span class="card-title">📢 Broadcast Notification</span></div>
            <div class="card-body">
                <form method="POST">
                    <?php csrfField(); ?><input type="hidden" name="action" value="broadcast">
                    <div class="form-group"><label>Title</label><input type="text" class="form-control" name="title" placeholder="Important Update" required></div>
                    <div class="form-group"><label>Message</label><textarea class="form-control" name="message" rows="3" placeholder="Your message here..." required></textarea></div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Type</label>
                            <select class="form-control" name="type">
                                <option value="info">ℹ️ Info</option>
                                <option value="success">✅ Success</option>
                                <option value="warning">⚠️ Warning</option>
                                <option value="danger">❌ Alert</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Target</label>
                            <select class="form-control" name="target">
                                <option value="all">All Users</option>
                                <option value="active">Active Users Only</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">📢 Broadcast</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card">
            <div class="card-header"><span class="card-title">👤 Send to Specific User</span></div>
            <div class="card-body">
                <form method="POST">
                    <?php csrfField(); ?><input type="hidden" name="action" value="send_to_user">
                    <div class="form-group">
                        <label>User</label>
                        <select class="form-control" name="user_id" required>
                            <option value="">Select user...</option>
                            <?php foreach ($users as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['username']) ?> (<?= htmlspecialchars($u['email']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Title</label><input type="text" class="form-control" name="title" required></div>
                    <div class="form-group"><label>Message</label><textarea class="form-control" name="message" rows="2" required></textarea></div>
                    <div class="form-group">
                        <label>Type</label>
                        <select class="form-control" name="type">
                            <option value="info">ℹ️ Info</option>
                            <option value="success">✅ Success</option>
                            <option value="warning">⚠️ Warning</option>
                            <option value="danger">❌ Alert</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">📨 Send</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="section-header">
    <h2 class="section-title">All Notifications (<?= count($notifications) ?>)</h2>
    <form method="POST" onsubmit="return confirm('Clear all notifications?')">
        <?php csrfField(); ?><input type="hidden" name="action" value="clear_all">
        <button type="submit" class="btn btn-danger btn-sm">🗑️ Clear All</button>
    </form>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Title</th><th>User</th><th>Type</th><th>Read</th><th>Time</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($notifications)): ?>
            <tr><td colspan="6"><div class="empty-state"><span class="empty-icon">🔔</span><h3>No notifications</h3></div></td></tr>
            <?php else: foreach (array_slice($notifications, 0, 100) as $n): ?>
            <?php $user = findById($users, $n['user_id']); ?>
            <tr>
                <td>
                    <div style="font-weight:600;"><?= htmlspecialchars($n['title']) ?></div>
                    <div class="text-small text-muted"><?= htmlspecialchars(substr($n['message'], 0, 60)) ?></div>
                </td>
                <td><?= htmlspecialchars($user['username'] ?? $n['user_id']) ?></td>
                <td><span class="badge-status badge-<?= $n['type'] === 'danger' ? 'inactive' : ($n['type'] === 'success' ? 'active' : ($n['type'] === 'warning' ? 'pending' : 'open')) ?>"><?= ucfirst($n['type']) ?></span></td>
                <td><?= $n['read'] ? '✅' : '🔵' ?></td>
                <td class="text-small text-muted"><?= timeAgo($n['created_at']) ?></td>
                <td>
                    <form method="POST" style="display:inline;">
                        <?php csrfField(); ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $n['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger">🗑️</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php renderFooter(); ?>
