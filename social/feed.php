<?php
require_once __DIR__ . '/includes/auth.php';
socialRequireLogin();
$user = socialCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!socialVerifyCsrf()) {
        socialSetFlash('danger', 'Invalid security token.');
        socialRedirect('feed.php');
    }

    $action = $_POST['action'] ?? '';
    $targetUserId = (string)($_POST['target_user_id'] ?? '');
    $postId = (string)($_POST['post_id'] ?? '');

    if ($action === 'create_post') {
        [$mediaPath, $uploadError, $mediaKind] = socialStoreUpload($_FILES['media'] ?? [], true);
        if ($uploadError) {
            socialSetFlash('danger', $uploadError);
        } else {
            [$ok, $msg] = socialCreatePost($user['id'], (string)($_POST['caption'] ?? ''), $mediaPath, $mediaKind);
            socialSetFlash($ok ? 'success' : 'danger', $msg);
        }
    } elseif ($action === 'create_story') {
        [$mediaPath, $uploadError, $mediaKind] = socialStoreUpload($_FILES['story_media'] ?? [], true);
        if ($uploadError) {
            socialSetFlash('danger', $uploadError);
        } else {
            [$ok, $msg] = socialCreateStory($user['id'], (string)$mediaPath, (string)$mediaKind);
            socialSetFlash($ok ? 'success' : 'danger', $msg);
        }
    } elseif ($action === 'create_reel') {
        [$mediaPath, $uploadError, $mediaKind] = socialStoreUpload($_FILES['reel_media'] ?? [], true);
        if ($uploadError) {
            socialSetFlash('danger', $uploadError);
        } else {
            [$ok, $msg] = socialCreateReel($user['id'], (string)($_POST['reel_caption'] ?? ''), (string)$mediaPath, (string)$mediaKind);
            socialSetFlash($ok ? 'success' : 'danger', $msg);
        }
    } elseif ($action === 'toggle_follow' && $targetUserId) {
        $followed = socialToggleFollow($user['id'], $targetUserId);
        socialSetFlash('success', $followed ? 'Followed user.' : 'Unfollowed user.');
    } elseif ($action === 'toggle_like' && $postId) {
        $liked = socialToggleReaction('likes', $user['id'], $postId);
        if ($liked) {
            foreach (socialLoad('posts') as $p) {
                if (($p['id'] ?? '') === $postId) {
                    socialNotify($p['user_id'] ?? '', 'like', 'liked your post', $user['id']);
                    break;
                }
            }
        }
        socialSetFlash('success', $liked ? 'Post liked.' : 'Like removed.');
    } elseif ($action === 'toggle_save' && $postId) {
        $saved = socialToggleReaction('saves', $user['id'], $postId);
        socialSetFlash('success', $saved ? 'Saved.' : 'Removed from saved.');
    } elseif ($action === 'share_post' && $postId) {
        socialToggleReaction('shares', $user['id'], $postId);
        foreach (socialLoad('posts') as $p) {
            if (($p['id'] ?? '') === $postId) {
                socialNotify($p['user_id'] ?? '', 'share', 'shared your post', $user['id']);
                break;
            }
        }
        socialSetFlash('success', 'Post shared.');
    } elseif ($action === 'add_comment' && $postId) {
        [$ok, $msg] = socialCreateComment($user['id'], $postId, (string)($_POST['comment'] ?? ''));
        if ($ok) {
            foreach (socialLoad('posts') as $p) {
                if (($p['id'] ?? '') === $postId) {
                    socialNotify($p['user_id'] ?? '', 'comment', 'commented on your post', $user['id']);
                    break;
                }
            }
        }
        socialSetFlash($ok ? 'success' : 'danger', $msg);
    }
    socialRedirect('feed.php');
}

$users = socialLoad('users');
$stories = socialActiveStories($user['id']);
$reels = socialAllReels($user['id']);
$feed = socialFeedPosts($user['id']);
socialRenderHeader('PhotoSocial - Feed', 'feed');
$token = socialCsrfToken();
?>
<div class="row g-3">
    <div class="col-lg-3">
        <div class="card"><div class="card-body">
            <h6 class="fw-bold">People</h6>
            <?php foreach (array_slice($users, 0, 12) as $uRow): if (($uRow['id'] ?? '') === $user['id']) continue; if (socialIsBlockedBetween($user['id'], $uRow['id'])) continue; ?>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <a href="<?php echo socialEsc(socialBaseUrl()); ?>/profile.php?u=<?php echo urlencode((string)$uRow['username']); ?>" class="text-decoration-none">@<?php echo socialEsc($uRow['username']); ?></a>
                    <form method="post" class="m-0">
                        <input type="hidden" name="_csrf" value="<?php echo socialEsc($token); ?>">
                        <input type="hidden" name="action" value="toggle_follow">
                        <input type="hidden" name="target_user_id" value="<?php echo socialEsc($uRow['id']); ?>">
                        <button class="btn btn-sm btn-outline-dark"><?php echo socialIsFollowing($user['id'], $uRow['id']) ? 'Following' : 'Follow'; ?></button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div></div>
    </div>

    <div class="col-lg-6">
        <div class="card mb-3"><div class="card-body">
            <h6 class="fw-bold">Create post</h6>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="_csrf" value="<?php echo socialEsc($token); ?>">
                <input type="hidden" name="action" value="create_post">
                <textarea class="form-control mb-2" name="caption" rows="3" maxlength="2200" placeholder="Write a caption, #hashtag, @mention"></textarea>
                <input type="file" class="form-control mb-2" name="media" accept="image/*,video/*">
                <button class="btn btn-dark">Publish</button>
            </form>
        </div></div>

        <?php foreach ($feed as $p): $author = socialFindUserById($p['user_id'] ?? ''); if (!$author) continue; ?>
            <div class="card mb-3"><div class="card-body">
                <div class="d-flex justify-content-between">
                    <div><a class="fw-semibold text-decoration-none" href="<?php echo socialEsc(socialBaseUrl()); ?>/profile.php?u=<?php echo urlencode((string)$author['username']); ?>">@<?php echo socialEsc($author['username']); ?></a></div>
                    <span class="post-meta"><?php echo socialEsc(date('d M Y H:i', strtotime((string)$p['created_at']))); ?></span>
                </div>
                <p class="mt-2 mb-2"><?php echo nl2br(socialEsc((string)($p['caption'] ?? ''))); ?></p>
                <?php if (!empty($p['media_path'])): ?>
                <div class="media-box mb-2">
                    <?php if (($p['media_kind'] ?? '') === 'video'): ?>
                        <video controls src="<?php echo socialEsc(socialBaseUrl() . '/' . $p['media_path']); ?>"></video>
                    <?php else: ?>
                        <img src="<?php echo socialEsc(socialBaseUrl() . '/' . $p['media_path']); ?>" alt="post media">
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div class="d-flex gap-2 flex-wrap mb-2">
                    <form method="post" class="m-0"><input type="hidden" name="_csrf" value="<?php echo socialEsc($token); ?>"><input type="hidden" name="action" value="toggle_like"><input type="hidden" name="post_id" value="<?php echo socialEsc($p['id']); ?>"><button class="btn btn-sm btn-outline-dark"><?php echo socialHasReaction('likes', $user['id'], $p['id']) ? 'Unlike' : 'Like'; ?> (<?php echo socialCountByPost('likes', $p['id']); ?>)</button></form>
                    <form method="post" class="m-0"><input type="hidden" name="_csrf" value="<?php echo socialEsc($token); ?>"><input type="hidden" name="action" value="toggle_save"><input type="hidden" name="post_id" value="<?php echo socialEsc($p['id']); ?>"><button class="btn btn-sm btn-outline-secondary"><?php echo socialHasReaction('saves', $user['id'], $p['id']) ? 'Saved' : 'Save'; ?> (<?php echo socialCountByPost('saves', $p['id']); ?>)</button></form>
                    <form method="post" class="m-0"><input type="hidden" name="_csrf" value="<?php echo socialEsc($token); ?>"><input type="hidden" name="action" value="share_post"><input type="hidden" name="post_id" value="<?php echo socialEsc($p['id']); ?>"><button class="btn btn-sm btn-outline-secondary">Share (<?php echo socialCountByPost('shares', $p['id']); ?>)</button></form>
                </div>

                <form method="post" class="mb-2">
                    <input type="hidden" name="_csrf" value="<?php echo socialEsc($token); ?>">
                    <input type="hidden" name="action" value="add_comment">
                    <input type="hidden" name="post_id" value="<?php echo socialEsc($p['id']); ?>">
                    <div class="input-group input-group-sm">
                        <input class="form-control" name="comment" maxlength="500" placeholder="Add a comment">
                        <button class="btn btn-dark">Comment</button>
                    </div>
                </form>
                <?php foreach (array_slice(socialCommentsByPost($p['id']), -3) as $c): $cu = socialFindUserById($c['user_id'] ?? ''); if(!$cu) continue; ?>
                    <div class="small mb-1"><span class="fw-semibold">@<?php echo socialEsc($cu['username']); ?></span> <?php echo socialEsc($c['content']); ?></div>
                <?php endforeach; ?>
            </div></div>
        <?php endforeach; ?>
    </div>

    <div class="col-lg-3">
        <div class="card mb-3"><div class="card-body">
            <h6 class="fw-bold">Stories</h6>
            <form method="post" enctype="multipart/form-data" class="mb-2">
                <input type="hidden" name="_csrf" value="<?php echo socialEsc($token); ?>">
                <input type="hidden" name="action" value="create_story">
                <input type="file" class="form-control form-control-sm mb-2" name="story_media" accept="image/*,video/*" required>
                <button class="btn btn-sm btn-dark">Add story</button>
            </form>
            <div class="story-scroll">
                <?php foreach ($stories as $s): $su = socialFindUserById($s['user_id'] ?? ''); if (!$su) continue; ?>
                    <div class="story-item">
                        <div class="small fw-semibold mb-1">@<?php echo socialEsc($su['username']); ?></div>
                        <?php if (($s['media_kind'] ?? '') === 'video'): ?>
                            <video controls src="<?php echo socialEsc(socialBaseUrl() . '/' . $s['media_path']); ?>"></video>
                        <?php else: ?>
                            <img src="<?php echo socialEsc(socialBaseUrl() . '/' . $s['media_path']); ?>" alt="story">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div></div>

        <div class="card"><div class="card-body">
            <h6 class="fw-bold">Reels</h6>
            <form method="post" enctype="multipart/form-data" class="mb-2">
                <input type="hidden" name="_csrf" value="<?php echo socialEsc($token); ?>">
                <input type="hidden" name="action" value="create_reel">
                <input type="text" class="form-control form-control-sm mb-2" name="reel_caption" placeholder="Reel caption">
                <input type="file" class="form-control form-control-sm mb-2" name="reel_media" accept="video/*" required>
                <button class="btn btn-sm btn-dark">Publish reel</button>
            </form>
            <?php foreach (array_slice($reels, 0, 4) as $r): $ru = socialFindUserById($r['user_id'] ?? ''); if(!$ru) continue; ?>
                <div class="mb-2">
                    <div class="small fw-semibold">@<?php echo socialEsc($ru['username']); ?></div>
                    <video controls src="<?php echo socialEsc(socialBaseUrl() . '/' . $r['media_path']); ?>"></video>
                </div>
            <?php endforeach; ?>
        </div></div>
    </div>
</div>
<?php socialRenderFooter(); ?>
