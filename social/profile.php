<?php
require_once __DIR__ . '/includes/auth.php';
socialRequireLogin();
$current = socialCurrentUser();
$viewUsername = (string)($_GET['u'] ?? $current['username']);
$profile = socialFindUserByUsername($viewUsername);

if (!$profile) {
    socialSetFlash('danger', 'Profile not found.');
    socialRedirect('feed.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!socialVerifyCsrf()) {
        socialSetFlash('danger', 'Invalid security token.');
        socialRedirect('profile.php?u=' . urlencode($profile['username']));
    }
    $action = $_POST['action'] ?? '';
    if ($action === 'toggle_follow' && $profile['id'] !== $current['id']) {
        $followed = socialToggleFollow($current['id'], $profile['id']);
        socialSetFlash('success', $followed ? 'Following now.' : 'Unfollowed.');
    }
    socialRedirect('profile.php?u=' . urlencode($profile['username']));
}

$posts = [];
foreach (socialLoad('posts') as $p) {
    if (($p['user_id'] ?? '') === $profile['id'] && socialUserCanSeeAuthor($current['id'], $profile['id'])) {
        $posts[] = $p;
    }
}
usort($posts, static fn($a, $b) => strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? '')));

socialRenderHeader('PhotoSocial - Profile', 'profile');
$token = socialCsrfToken();
?>
<div class="card mb-3"><div class="card-body">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h4 class="mb-1"><?php echo socialEsc($profile['display_name']); ?></h4>
            <div class="text-muted">@<?php echo socialEsc($profile['username']); ?> · <?php echo socialEsc($profile['privacy'] ?? 'public'); ?> profile</div>
            <p class="mt-2 mb-0"><?php echo socialEsc($profile['bio'] ?? ''); ?></p>
        </div>
        <?php if ($profile['id'] !== $current['id']): ?>
            <form method="post" class="m-0">
                <input type="hidden" name="_csrf" value="<?php echo socialEsc($token); ?>">
                <input type="hidden" name="action" value="toggle_follow">
                <button class="btn btn-dark"><?php echo socialIsFollowing($current['id'], $profile['id']) ? 'Following' : 'Follow'; ?></button>
            </form>
        <?php endif; ?>
    </div>
    <div class="mt-3 d-flex gap-3">
        <span class="badge text-bg-dark small-pill">Posts <?php echo count($posts); ?></span>
        <span class="badge text-bg-secondary small-pill">Followers <?php echo socialFollowersCount($profile['id']); ?></span>
        <span class="badge text-bg-secondary small-pill">Following <?php echo socialFollowingCount($profile['id']); ?></span>
    </div>
</div></div>

<div class="row g-3">
<?php foreach ($posts as $p): ?>
    <div class="col-md-6 col-lg-4">
        <div class="card"><div class="card-body">
            <p class="small mb-2"><?php echo socialEsc($p['caption'] ?? ''); ?></p>
            <?php if (!empty($p['media_path'])): ?>
                <div class="media-box mb-2">
                    <?php if (($p['media_kind'] ?? '') === 'video'): ?>
                        <video controls src="<?php echo socialEsc(socialBaseUrl() . '/' . $p['media_path']); ?>"></video>
                    <?php else: ?>
                        <img src="<?php echo socialEsc(socialBaseUrl() . '/' . $p['media_path']); ?>" alt="media">
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <div class="small text-muted">❤ <?php echo socialCountByPost('likes', $p['id']); ?> · 💬 <?php echo socialCountByPost('comments', $p['id']); ?></div>
        </div></div>
    </div>
<?php endforeach; ?>
</div>
<?php socialRenderFooter(); ?>
