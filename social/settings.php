<?php
require_once __DIR__ . '/includes/auth.php';
socialRequireLogin();
$user = socialCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!socialVerifyCsrf()) {
        socialSetFlash('danger', 'Invalid security token.');
        socialRedirect('settings.php');
    }

    $action = $_POST['action'] ?? '';
    if ($action === 'set_privacy') {
        socialSetPrivacy($user['id'], (string)($_POST['privacy'] ?? 'public'));
        socialSetFlash('success', 'Privacy updated.');
    } elseif ($action === 'block_user') {
        $target = socialFindUserByUsername((string)($_POST['username'] ?? ''));
        if (!$target) {
            socialSetFlash('danger', 'User not found.');
        } else {
            [$ok, $msg] = socialBlockUser($user['id'], $target['id']);
            socialSetFlash($ok ? 'success' : 'danger', $msg);
        }
    } elseif ($action === 'report_user') {
        $target = socialFindUserByUsername((string)($_POST['username'] ?? ''));
        if (!$target) {
            socialSetFlash('danger', 'User not found.');
        } else {
            [$ok, $msg] = socialCreateReport($user['id'], $target['id'], (string)($_POST['reason'] ?? ''), (string)($_POST['details'] ?? ''));
            socialSetFlash($ok ? 'success' : 'danger', $msg);
        }
    } elseif ($action === 'resolve_report' && ($user['role'] ?? '') === 'admin') {
        socialResolveReport($user['id'], (string)($_POST['report_id'] ?? ''));
        socialSetFlash('success', 'Report resolved.');
    }

    socialRedirect('settings.php');
}

$user = socialCurrentUser();
$openReports = ($user['role'] ?? '') === 'admin' ? socialOpenReports() : [];
socialRenderHeader('PhotoSocial - Settings', 'settings');
$token = socialCsrfToken();
?>
<div class="row g-3">
    <div class="col-lg-6">
        <div class="card"><div class="card-body">
            <h6 class="fw-bold">Privacy</h6>
            <form method="post" class="mb-3">
                <input type="hidden" name="_csrf" value="<?php echo socialEsc($token); ?>">
                <input type="hidden" name="action" value="set_privacy">
                <select class="form-select mb-2" name="privacy">
                    <option value="public" <?php echo ($user['privacy'] ?? 'public') === 'public' ? 'selected' : ''; ?>>Public</option>
                    <option value="private" <?php echo ($user['privacy'] ?? 'public') === 'private' ? 'selected' : ''; ?>>Private</option>
                </select>
                <button class="btn btn-dark">Update</button>
            </form>

            <h6 class="fw-bold">Block user</h6>
            <form method="post" class="mb-3">
                <input type="hidden" name="_csrf" value="<?php echo socialEsc($token); ?>">
                <input type="hidden" name="action" value="block_user">
                <input class="form-control mb-2" name="username" placeholder="username to block" required>
                <button class="btn btn-outline-dark">Block</button>
            </form>

            <h6 class="fw-bold">Report user</h6>
            <form method="post">
                <input type="hidden" name="_csrf" value="<?php echo socialEsc($token); ?>">
                <input type="hidden" name="action" value="report_user">
                <input class="form-control mb-2" name="username" placeholder="username to report" required>
                <input class="form-control mb-2" name="reason" maxlength="80" placeholder="reason" required>
                <textarea class="form-control mb-2" name="details" maxlength="1000" rows="3" placeholder="details"></textarea>
                <button class="btn btn-outline-dark">Submit report</button>
            </form>
        </div></div>
    </div>

    <?php if (($user['role'] ?? '') === 'admin'): ?>
    <div class="col-lg-6">
        <div class="card"><div class="card-body">
            <h6 class="fw-bold">Moderation queue</h6>
            <?php foreach ($openReports as $r): $reporter = socialFindUserById($r['reporter_id'] ?? ''); $target = socialFindUserById($r['target_user_id'] ?? ''); ?>
                <div class="border rounded p-2 mb-2">
                    <div class="small text-muted">By <?php echo $reporter ? '@' . socialEsc($reporter['username']) : 'unknown'; ?> against <?php echo $target ? '@' . socialEsc($target['username']) : 'unknown'; ?></div>
                    <div><strong><?php echo socialEsc($r['reason'] ?? ''); ?></strong></div>
                    <div class="small mb-2"><?php echo socialEsc($r['details'] ?? ''); ?></div>
                    <form method="post" class="m-0">
                        <input type="hidden" name="_csrf" value="<?php echo socialEsc($token); ?>">
                        <input type="hidden" name="action" value="resolve_report">
                        <input type="hidden" name="report_id" value="<?php echo socialEsc($r['id']); ?>">
                        <button class="btn btn-sm btn-dark">Resolve</button>
                    </form>
                </div>
            <?php endforeach; ?>
            <?php if (!$openReports): ?><p class="text-muted mb-0">No open reports.</p><?php endif; ?>
        </div></div>
    </div>
    <?php endif; ?>
</div>
<?php socialRenderFooter(); ?>
