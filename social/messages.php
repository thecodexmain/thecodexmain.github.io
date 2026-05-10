<?php
require_once __DIR__ . '/includes/auth.php';
socialRequireLogin();
$user = socialCurrentUser();
$peerUsername = (string)($_GET['u'] ?? '');
$peer = $peerUsername !== '' ? socialFindUserByUsername($peerUsername) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!socialVerifyCsrf()) {
        socialSetFlash('danger', 'Invalid security token.');
        socialRedirect('messages.php');
    }
    $targetId = (string)($_POST['target_user_id'] ?? '');
    [$ok, $msg] = socialSendMessage($user['id'], $targetId, (string)($_POST['content'] ?? ''));
    socialSetFlash($ok ? 'success' : 'danger', $msg);
    if ($ok) {
        $targetUser = socialFindUserById($targetId);
        socialRedirect('messages.php?u=' . urlencode((string)($targetUser['username'] ?? '')));
    }
    socialRedirect('messages.php');
}

$users = socialLoad('users');
$convo = $peer ? socialConversation($user['id'], $peer['id']) : [];

socialRenderHeader('PhotoSocial - Messages', 'messages');
$token = socialCsrfToken();
?>
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card"><div class="card-body">
            <h6 class="fw-bold">Chats</h6>
            <?php foreach ($users as $u): if (($u['id'] ?? '') === $user['id']) continue; if (socialIsBlockedBetween($user['id'], $u['id'])) continue; ?>
                <div class="mb-2"><a class="text-decoration-none" href="<?php echo socialEsc(socialBaseUrl()); ?>/messages.php?u=<?php echo urlencode((string)$u['username']); ?>">@<?php echo socialEsc($u['username']); ?></a></div>
            <?php endforeach; ?>
        </div></div>
    </div>
    <div class="col-lg-8">
        <div class="card"><div class="card-body">
            <?php if (!$peer): ?>
                <p class="text-muted mb-0">Select a user to start chatting.</p>
            <?php else: ?>
                <h6 class="fw-bold mb-3">Chat with @<?php echo socialEsc($peer['username']); ?></h6>
                <div class="mb-3" style="max-height:360px;overflow:auto;">
                    <?php foreach ($convo as $m): ?>
                        <div class="d-flex mb-2 <?php echo ($m['from_user_id'] ?? '') === $user['id'] ? 'justify-content-end' : 'justify-content-start'; ?>">
                            <div class="message-bubble <?php echo ($m['from_user_id'] ?? '') === $user['id'] ? 'message-me' : 'message-other'; ?>">
                                <?php echo socialEsc($m['content'] ?? ''); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <form method="post">
                    <input type="hidden" name="_csrf" value="<?php echo socialEsc($token); ?>">
                    <input type="hidden" name="target_user_id" value="<?php echo socialEsc($peer['id']); ?>">
                    <div class="input-group">
                        <input class="form-control" name="content" maxlength="1000" placeholder="Type message...">
                        <button class="btn btn-dark">Send</button>
                    </div>
                </form>
            <?php endif; ?>
        </div></div>
    </div>
</div>
<?php socialRenderFooter(); ?>
