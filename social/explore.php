<?php
require_once __DIR__ . '/includes/auth.php';
socialRequireLogin();
$user = socialCurrentUser();
$q = trim((string)($_GET['q'] ?? ''));
$userResults = $q === '' ? [] : socialSearchUsers($q, $user['id']);
$postResults = $q === '' ? socialExplorePosts($user['id']) : socialSearchPosts($q, $user['id']);

socialRenderHeader('PhotoSocial - Explore', 'explore');
?>
<div class="card mb-3"><div class="card-body">
    <form class="row g-2">
        <div class="col-md-10"><input class="form-control" name="q" value="<?php echo socialEsc($q); ?>" placeholder="Search users, captions, #hashtags, @mentions"></div>
        <div class="col-md-2"><button class="btn btn-dark w-100">Search</button></div>
    </form>
</div></div>

<?php if ($q !== ''): ?>
<div class="card mb-3"><div class="card-body">
    <h6 class="fw-bold">Users</h6>
    <?php foreach ($userResults as $u): ?>
        <div class="mb-1"><a href="<?php echo socialEsc(socialBaseUrl()); ?>/profile.php?u=<?php echo urlencode((string)$u['username']); ?>" class="text-decoration-none">@<?php echo socialEsc($u['username']); ?></a> · <?php echo socialEsc($u['display_name']); ?></div>
    <?php endforeach; ?>
    <?php if (!$userResults): ?><p class="text-muted mb-0">No users found.</p><?php endif; ?>
</div></div>
<?php endif; ?>

<div class="row g-3">
<?php foreach ($postResults as $p): $author = socialFindUserById($p['user_id'] ?? ''); if(!$author) continue; ?>
    <div class="col-md-6 col-lg-4">
        <div class="card"><div class="card-body">
            <div class="small mb-1"><a class="text-decoration-none" href="<?php echo socialEsc(socialBaseUrl()); ?>/profile.php?u=<?php echo urlencode((string)$author['username']); ?>">@<?php echo socialEsc($author['username']); ?></a></div>
            <p class="small mb-2"><?php echo socialEsc($p['caption'] ?? ''); ?></p>
            <?php if (!empty($p['media_path'])): ?>
                <div class="media-box">
                    <?php if (($p['media_kind'] ?? '') === 'video'): ?>
                        <video controls src="<?php echo socialEsc(socialBaseUrl() . '/' . $p['media_path']); ?>"></video>
                    <?php else: ?>
                        <img src="<?php echo socialEsc(socialBaseUrl() . '/' . $p['media_path']); ?>" alt="post">
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div></div>
    </div>
<?php endforeach; ?>
</div>
<?php socialRenderFooter(); ?>
