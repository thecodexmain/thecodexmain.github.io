<?php
require_once __DIR__ . '/includes/auth.php';
socialRequireLogin();
$user = socialCurrentUser();
$notifications = [];
foreach (socialLoad('notifications') as $n) {
    if (($n['user_id'] ?? '') === $user['id']) {
        $notifications[] = $n;
    }
}
usort($notifications, static fn($a, $b) => strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? '')));

socialRenderHeader('PhotoSocial - Notifications', 'notifications');
?>
<div class="card"><div class="card-body">
    <h5 class="fw-bold">Notifications</h5>
    <?php foreach ($notifications as $n): $actor = socialFindUserById($n['actor_user_id'] ?? ''); ?>
        <div class="border rounded p-2 mb-2">
            <div><strong><?php echo $actor ? '@' . socialEsc($actor['username']) : 'System'; ?></strong> <?php echo socialEsc($n['message'] ?? ''); ?></div>
            <div class="small text-muted"><?php echo socialEsc(date('d M Y H:i', strtotime((string)$n['created_at']))); ?></div>
        </div>
    <?php endforeach; ?>
    <?php if (!$notifications): ?><p class="text-muted mb-0">No notifications yet.</p><?php endif; ?>
</div></div>
<?php socialRenderFooter(); ?>
