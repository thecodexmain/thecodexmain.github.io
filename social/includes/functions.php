<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function socialBaseUrl(): string {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/social'));
    $parts = array_values(array_filter(explode('/', trim($scriptDir, '/'))));
    $socialIdx = false;
    foreach (array_reverse($parts, true) as $i => $part) {
        if ($part === 'social') {
            $socialIdx = $i;
            break;
        }
    }
    if ($socialIdx !== false) {
        $baseParts = array_slice($parts, 0, $socialIdx + 1);
        return $protocol . '://' . $host . '/' . implode('/', $baseParts);
    }
    return $protocol . '://' . $host . '/social';
}

function socialDataPath(string $name): string {
    return __DIR__ . '/../data/' . $name . '.json';
}

function socialLoad(string $name): array {
    $path = socialDataPath($name);
    if (!file_exists($path)) {
        return [];
    }
    $raw = file_get_contents($path);
    if ($raw === false || trim($raw) === '') {
        return [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function socialSave(string $name, array $data): bool {
    $path = socialDataPath($name);
    return file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX) !== false;
}

function socialEnsureData(): void {
    $defaults = [
        'users' => [], 'posts' => [], 'follows' => [], 'likes' => [], 'comments' => [], 'saves' => [],
        'shares' => [], 'stories' => [], 'reels' => [], 'messages' => [], 'notifications' => [],
        'blocks' => [], 'reports' => []
    ];
    foreach ($defaults as $file => $value) {
        $path = socialDataPath($file);
        if (!file_exists($path)) {
            file_put_contents($path, json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
        }
    }
}

function socialGenerateId(string $prefix = 'id_'): string {
    return $prefix . bin2hex(random_bytes(6));
}

function socialSanitize(string $input): string {
    return trim(strip_tags($input));
}

function socialEsc(?string $text): string {
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

function socialCsrfToken(): string {
    if (empty($_SESSION['social_csrf'])) {
        $_SESSION['social_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['social_csrf'];
}

function socialVerifyCsrf(): bool {
    $token = $_POST['_csrf'] ?? '';
    return is_string($token) && hash_equals($_SESSION['social_csrf'] ?? '', $token);
}

function socialSetFlash(string $type, string $message): void {
    $_SESSION['social_flash'] = ['type' => $type, 'message' => $message];
}

function socialGetFlash(): ?array {
    if (!isset($_SESSION['social_flash'])) {
        return null;
    }
    $flash = $_SESSION['social_flash'];
    unset($_SESSION['social_flash']);
    return $flash;
}

function socialRedirect(string $path): void {
    header('Location: ' . socialBaseUrl() . '/' . ltrim($path, '/'));
    exit;
}

function socialCurrentUserId(): ?string {
    return $_SESSION['social_user_id'] ?? null;
}

function socialFindUserById(string $id): ?array {
    foreach (socialLoad('users') as $u) {
        if (($u['id'] ?? '') === $id) {
            return $u;
        }
    }
    return null;
}

function socialFindUserByUsername(string $username): ?array {
    $target = strtolower($username);
    foreach (socialLoad('users') as $u) {
        if (strtolower((string)($u['username'] ?? '')) === $target) {
            return $u;
        }
    }
    return null;
}

function socialCurrentUser(): ?array {
    $id = socialCurrentUserId();
    return $id ? socialFindUserById($id) : null;
}

function socialRequireLogin(): void {
    if (!socialCurrentUserId()) {
        socialRedirect('index.php');
    }
}

function socialCreateUser(string $username, string $displayName, string $password): array {
    $username = strtolower(preg_replace('/[^a-zA-Z0-9_.]/', '', $username));
    $displayName = socialSanitize($displayName);

    if (strlen($username) < 3 || strlen($username) > 20) {
        return [false, 'Username must be 3-20 characters.'];
    }
    if (strlen($displayName) < 2 || strlen($displayName) > 60) {
        return [false, 'Display name must be 2-60 characters.'];
    }
    if (strlen($password) < 8) {
        return [false, 'Password must be at least 8 characters.'];
    }
    if (socialFindUserByUsername($username)) {
        return [false, 'Username already exists.'];
    }

    $users = socialLoad('users');
    $isFirst = count($users) === 0;
    $users[] = [
        'id' => socialGenerateId('usr_'),
        'username' => $username,
        'display_name' => $displayName,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'bio' => '',
        'avatar' => '',
        'role' => $isFirst ? 'admin' : 'user',
        'privacy' => 'public',
        'created_at' => date('c')
    ];
    socialSave('users', $users);
    return [true, 'Account created successfully.'];
}

function socialLogin(string $username, string $password): bool {
    $user = socialFindUserByUsername($username);
    if (!$user || !password_verify($password, (string)($user['password_hash'] ?? ''))) {
        return false;
    }
    session_regenerate_id(true);
    $_SESSION['social_user_id'] = $user['id'];
    return true;
}

function socialLogout(): void {
    unset($_SESSION['social_user_id']);
    session_regenerate_id(true);
}

function socialStoreUpload(array $file, bool $allowVideo = true): array {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [null, null, null];
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return [null, 'Upload failed.', null];
    }

    $max = 50 * 1024 * 1024;
    if (($file['size'] ?? 0) > $max) {
        return [null, 'File too large (max 50MB).', null];
    }

    $tmp = $file['tmp_name'] ?? '';
    if (!is_uploaded_file($tmp)) {
        return [null, 'Invalid upload source.', null];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string)$finfo->file($tmp);
    $allowed = [
        'image/jpeg' => ['ext' => 'jpg', 'kind' => 'image'],
        'image/png' => ['ext' => 'png', 'kind' => 'image'],
        'image/webp' => ['ext' => 'webp', 'kind' => 'image'],
        'image/gif' => ['ext' => 'gif', 'kind' => 'image'],
        'video/mp4' => ['ext' => 'mp4', 'kind' => 'video'],
        'video/webm' => ['ext' => 'webm', 'kind' => 'video'],
        'video/quicktime' => ['ext' => 'mov', 'kind' => 'video']
    ];

    if (!isset($allowed[$mime])) {
        return [null, 'Unsupported file type.', null];
    }
    if (!$allowVideo && $allowed[$mime]['kind'] === 'video') {
        return [null, 'Only image files are allowed here.', null];
    }

    $dir = __DIR__ . '/../uploads/media';
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return [null, 'Upload directory not writable.', null];
    }

    $filename = socialGenerateId('media_') . '.' . $allowed[$mime]['ext'];
    $dest = $dir . '/' . $filename;
    if (!move_uploaded_file($tmp, $dest)) {
        return [null, 'Could not save uploaded file.', null];
    }

    return ['uploads/media/' . $filename, null, $allowed[$mime]['kind']];
}

function socialIsBlockedBetween(string $a, string $b): bool {
    foreach (socialLoad('blocks') as $row) {
        if ((($row['blocker_id'] ?? '') === $a && ($row['blocked_id'] ?? '') === $b) ||
            (($row['blocker_id'] ?? '') === $b && ($row['blocked_id'] ?? '') === $a)) {
            return true;
        }
    }
    return false;
}

function socialNotify(string $targetUserId, string $type, string $message, string $actorUserId = ''): void {
    if ($targetUserId === '' || $targetUserId === $actorUserId) {
        return;
    }
    $notifications = socialLoad('notifications');
    $notifications[] = [
        'id' => socialGenerateId('noti_'),
        'user_id' => $targetUserId,
        'actor_user_id' => $actorUserId,
        'type' => $type,
        'message' => $message,
        'is_read' => false,
        'created_at' => date('c')
    ];
    socialSave('notifications', $notifications);
}

function socialToggleFollow(string $followerId, string $followeeId): bool {
    if ($followerId === $followeeId || socialIsBlockedBetween($followerId, $followeeId)) {
        return false;
    }
    $rows = socialLoad('follows');
    foreach ($rows as $i => $r) {
        if (($r['follower_id'] ?? '') === $followerId && ($r['followee_id'] ?? '') === $followeeId) {
            unset($rows[$i]);
            socialSave('follows', array_values($rows));
            return false;
        }
    }
    $rows[] = ['id' => socialGenerateId('fol_'), 'follower_id' => $followerId, 'followee_id' => $followeeId, 'created_at' => date('c')];
    socialSave('follows', $rows);
    socialNotify($followeeId, 'follow', 'started following you', $followerId);
    return true;
}

function socialIsFollowing(string $followerId, string $followeeId): bool {
    foreach (socialLoad('follows') as $r) {
        if (($r['follower_id'] ?? '') === $followerId && ($r['followee_id'] ?? '') === $followeeId) {
            return true;
        }
    }
    return false;
}

function socialFollowersCount(string $userId): int {
    $count = 0;
    foreach (socialLoad('follows') as $r) {
        if (($r['followee_id'] ?? '') === $userId) {
            $count++;
        }
    }
    return $count;
}

function socialFollowingCount(string $userId): int {
    $count = 0;
    foreach (socialLoad('follows') as $r) {
        if (($r['follower_id'] ?? '') === $userId) {
            $count++;
        }
    }
    return $count;
}

function socialExtractHashtags(string $text): array {
    preg_match_all('/#([a-zA-Z0-9_]+)/', $text, $m);
    return array_values(array_unique(array_map('strtolower', $m[1] ?? [])));
}

function socialExtractMentions(string $text): array {
    preg_match_all('/@([a-zA-Z0-9_.]+)/', $text, $m);
    return array_values(array_unique(array_map('strtolower', $m[1] ?? [])));
}

function socialCreatePost(string $userId, string $caption, ?string $mediaPath, ?string $mediaKind): array {
    $caption = mb_substr(socialSanitize($caption), 0, 2200);
    if ($caption === '' && !$mediaPath) {
        return [false, 'Add a caption or media to create a post.'];
    }
    $user = socialFindUserById($userId);
    if (!$user) {
        return [false, 'Invalid user.'];
    }

    $posts = socialLoad('posts');
    $posts[] = [
        'id' => socialGenerateId('pst_'),
        'user_id' => $userId,
        'caption' => $caption,
        'hashtags' => socialExtractHashtags($caption),
        'mentions' => socialExtractMentions($caption),
        'media_path' => $mediaPath ?: '',
        'media_kind' => $mediaKind ?: 'none',
        'created_at' => date('c')
    ];
    socialSave('posts', $posts);

    foreach (socialExtractMentions($caption) as $uname) {
        $mentioned = socialFindUserByUsername($uname);
        if ($mentioned) {
            socialNotify($mentioned['id'], 'mention', 'mentioned you in a post', $userId);
        }
    }

    return [true, 'Post published.'];
}

function socialCreateStory(string $userId, string $mediaPath, string $mediaKind): array {
    if (!$mediaPath) {
        return [false, 'Story media is required.'];
    }
    $stories = socialLoad('stories');
    $stories[] = [
        'id' => socialGenerateId('sto_'),
        'user_id' => $userId,
        'media_path' => $mediaPath,
        'media_kind' => $mediaKind,
        'created_at' => date('c'),
        'expires_at' => date('c', strtotime('+24 hours'))
    ];
    socialSave('stories', $stories);
    return [true, 'Story shared.'];
}

function socialCreateReel(string $userId, string $caption, string $mediaPath, string $mediaKind): array {
    if ($mediaKind !== 'video') {
        return [false, 'Reels require a video file.'];
    }
    $caption = mb_substr(socialSanitize($caption), 0, 2200);
    $reels = socialLoad('reels');
    $reels[] = [
        'id' => socialGenerateId('rel_'),
        'user_id' => $userId,
        'caption' => $caption,
        'hashtags' => socialExtractHashtags($caption),
        'mentions' => socialExtractMentions($caption),
        'media_path' => $mediaPath,
        'media_kind' => $mediaKind,
        'created_at' => date('c')
    ];
    socialSave('reels', $reels);
    return [true, 'Reel published.'];
}

function socialUserCanSeeAuthor(string $viewerId, string $authorId): bool {
    if ($viewerId === $authorId) {
        return true;
    }
    if (socialIsBlockedBetween($viewerId, $authorId)) {
        return false;
    }
    $author = socialFindUserById($authorId);
    if (!$author) {
        return false;
    }
    if (($author['privacy'] ?? 'public') === 'private') {
        return socialIsFollowing($viewerId, $authorId);
    }
    return true;
}

function socialFeedPosts(string $viewerId): array {
    $following = [$viewerId => true];
    foreach (socialLoad('follows') as $r) {
        if (($r['follower_id'] ?? '') === $viewerId) {
            $following[$r['followee_id']] = true;
        }
    }

    $items = [];
    foreach (socialLoad('posts') as $p) {
        $authorId = $p['user_id'] ?? '';
        if (!isset($following[$authorId])) {
            continue;
        }
        if (!socialUserCanSeeAuthor($viewerId, $authorId)) {
            continue;
        }
        $items[] = $p;
    }

    usort($items, static fn($a, $b) => strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? '')));
    return $items;
}

function socialExplorePosts(string $viewerId): array {
    $items = [];
    foreach (socialLoad('posts') as $p) {
        $authorId = $p['user_id'] ?? '';
        if ($authorId === $viewerId) {
            continue;
        }
        if (!socialUserCanSeeAuthor($viewerId, $authorId)) {
            continue;
        }
        $items[] = $p;
    }
    usort($items, static fn($a, $b) => strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? '')));
    return array_slice($items, 0, 40);
}

function socialToggleReaction(string $file, string $userId, string $postId): bool {
    $rows = socialLoad($file);
    foreach ($rows as $i => $row) {
        if (($row['user_id'] ?? '') === $userId && ($row['post_id'] ?? '') === $postId) {
            unset($rows[$i]);
            socialSave($file, array_values($rows));
            return false;
        }
    }
    $rows[] = ['id' => socialGenerateId($file . '_'), 'user_id' => $userId, 'post_id' => $postId, 'created_at' => date('c')];
    socialSave($file, $rows);
    return true;
}

function socialCountByPost(string $file, string $postId): int {
    $count = 0;
    foreach (socialLoad($file) as $row) {
        if (($row['post_id'] ?? '') === $postId) {
            $count++;
        }
    }
    return $count;
}

function socialHasReaction(string $file, string $userId, string $postId): bool {
    foreach (socialLoad($file) as $row) {
        if (($row['user_id'] ?? '') === $userId && ($row['post_id'] ?? '') === $postId) {
            return true;
        }
    }
    return false;
}

function socialCreateComment(string $userId, string $postId, string $content): array {
    $content = mb_substr(socialSanitize($content), 0, 500);
    if ($content === '') {
        return [false, 'Comment cannot be empty.'];
    }
    $comments = socialLoad('comments');
    $comments[] = [
        'id' => socialGenerateId('cmt_'),
        'user_id' => $userId,
        'post_id' => $postId,
        'content' => $content,
        'created_at' => date('c')
    ];
    socialSave('comments', $comments);
    return [true, 'Comment added.'];
}

function socialCommentsByPost(string $postId): array {
    $items = [];
    foreach (socialLoad('comments') as $c) {
        if (($c['post_id'] ?? '') === $postId) {
            $items[] = $c;
        }
    }
    usort($items, static fn($a, $b) => strcmp((string)($a['created_at'] ?? ''), (string)($b['created_at'] ?? '')));
    return $items;
}

function socialActiveStories(string $viewerId): array {
    $now = date('c');
    $items = [];
    foreach (socialLoad('stories') as $s) {
        if (($s['expires_at'] ?? '') < $now) {
            continue;
        }
        if (!socialUserCanSeeAuthor($viewerId, $s['user_id'] ?? '')) {
            continue;
        }
        $items[] = $s;
    }
    usort($items, static fn($a, $b) => strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? '')));
    return $items;
}

function socialAllReels(string $viewerId): array {
    $items = [];
    foreach (socialLoad('reels') as $r) {
        if (!socialUserCanSeeAuthor($viewerId, $r['user_id'] ?? '')) {
            continue;
        }
        $items[] = $r;
    }
    usort($items, static fn($a, $b) => strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? '')));
    return array_slice($items, 0, 30);
}

function socialSendMessage(string $fromUserId, string $toUserId, string $content): array {
    $content = mb_substr(socialSanitize($content), 0, 1000);
    if ($toUserId === $fromUserId) {
        return [false, 'Cannot message yourself.'];
    }
    if ($content === '') {
        return [false, 'Message cannot be empty.'];
    }
    if (socialIsBlockedBetween($fromUserId, $toUserId)) {
        return [false, 'Messaging unavailable for this user.'];
    }

    $messages = socialLoad('messages');
    $messages[] = [
        'id' => socialGenerateId('msg_'),
        'from_user_id' => $fromUserId,
        'to_user_id' => $toUserId,
        'content' => $content,
        'is_read' => false,
        'created_at' => date('c')
    ];
    socialSave('messages', $messages);
    socialNotify($toUserId, 'message', 'sent you a message', $fromUserId);
    return [true, 'Message sent.'];
}

function socialConversation(string $a, string $b): array {
    $items = [];
    foreach (socialLoad('messages') as $m) {
        $from = $m['from_user_id'] ?? '';
        $to = $m['to_user_id'] ?? '';
        if (($from === $a && $to === $b) || ($from === $b && $to === $a)) {
            $items[] = $m;
        }
    }
    usort($items, static fn($x, $y) => strcmp((string)($x['created_at'] ?? ''), (string)($y['created_at'] ?? '')));
    return $items;
}

function socialSearchUsers(string $query, string $viewerId): array {
    $q = strtolower(trim($query));
    if ($q === '') {
        return [];
    }
    $items = [];
    foreach (socialLoad('users') as $u) {
        $id = $u['id'] ?? '';
        if ($id === $viewerId || socialIsBlockedBetween($viewerId, $id)) {
            continue;
        }
        $hay = strtolower(($u['username'] ?? '') . ' ' . ($u['display_name'] ?? '') . ' ' . ($u['bio'] ?? ''));
        if (str_contains($hay, $q)) {
            $items[] = $u;
        }
    }
    return array_slice($items, 0, 20);
}

function socialSearchPosts(string $query, string $viewerId): array {
    $q = strtolower(trim($query));
    if ($q === '') {
        return [];
    }
    $items = [];
    foreach (socialLoad('posts') as $p) {
        $authorId = $p['user_id'] ?? '';
        if (!socialUserCanSeeAuthor($viewerId, $authorId)) {
            continue;
        }
        $caption = strtolower((string)($p['caption'] ?? ''));
        $tags = implode(' ', $p['hashtags'] ?? []);
        $mentions = implode(' ', $p['mentions'] ?? []);
        if (str_contains($caption, $q) || str_contains($tags, ltrim($q, '#')) || str_contains($mentions, ltrim($q, '@'))) {
            $items[] = $p;
        }
    }
    usort($items, static fn($a, $b) => strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? '')));
    return array_slice($items, 0, 30);
}

function socialSetPrivacy(string $userId, string $privacy): void {
    $privacy = $privacy === 'private' ? 'private' : 'public';
    $users = socialLoad('users');
    foreach ($users as &$u) {
        if (($u['id'] ?? '') === $userId) {
            $u['privacy'] = $privacy;
            break;
        }
    }
    unset($u);
    socialSave('users', $users);
}

function socialBlockUser(string $blockerId, string $blockedId): array {
    if ($blockerId === $blockedId) {
        return [false, 'Cannot block yourself.'];
    }
    $blocks = socialLoad('blocks');
    foreach ($blocks as $b) {
        if (($b['blocker_id'] ?? '') === $blockerId && ($b['blocked_id'] ?? '') === $blockedId) {
            return [false, 'User already blocked.'];
        }
    }
    $blocks[] = ['id' => socialGenerateId('blk_'), 'blocker_id' => $blockerId, 'blocked_id' => $blockedId, 'created_at' => date('c')];
    socialSave('blocks', $blocks);
    return [true, 'User blocked.'];
}

function socialCreateReport(string $reporterId, string $targetUserId, string $reason, string $details): array {
    $reason = mb_substr(socialSanitize($reason), 0, 80);
    $details = mb_substr(socialSanitize($details), 0, 1000);
    if ($reason === '') {
        return [false, 'Report reason is required.'];
    }
    $reports = socialLoad('reports');
    $reports[] = [
        'id' => socialGenerateId('rpt_'),
        'reporter_id' => $reporterId,
        'target_user_id' => $targetUserId,
        'reason' => $reason,
        'details' => $details,
        'status' => 'open',
        'created_at' => date('c')
    ];
    socialSave('reports', $reports);
    return [true, 'Report submitted.'];
}

function socialResolveReport(string $resolverId, string $reportId): bool {
    $reports = socialLoad('reports');
    $changed = false;
    foreach ($reports as &$r) {
        if (($r['id'] ?? '') === $reportId) {
            $r['status'] = 'resolved';
            $r['resolved_by'] = $resolverId;
            $r['resolved_at'] = date('c');
            $changed = true;
            break;
        }
    }
    unset($r);
    if ($changed) {
        socialSave('reports', $reports);
    }
    return $changed;
}

function socialOpenReports(): array {
    $items = [];
    foreach (socialLoad('reports') as $r) {
        if (($r['status'] ?? '') === 'open') {
            $items[] = $r;
        }
    }
    usort($items, static fn($a, $b) => strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? '')));
    return $items;
}

function socialRenderHeader(string $title, string $active = ''): void {
    $user = socialCurrentUser();
    $flash = socialGetFlash();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo socialEsc($title); ?></title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="<?php echo socialEsc(socialBaseUrl()); ?>/assets/css/style.css">
    </head>
    <body>
    <nav class="navbar navbar-expand-lg border-bottom bg-white sticky-top">
      <div class="container-fluid px-3">
        <a class="navbar-brand fw-bold" href="<?php echo socialEsc(socialBaseUrl()); ?>/feed.php">PhotoSocial</a>
        <?php if ($user): ?>
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link <?php echo $active === 'feed' ? 'active' : ''; ?>" href="<?php echo socialEsc(socialBaseUrl()); ?>/feed.php">Feed</a></li>
                <li class="nav-item"><a class="nav-link <?php echo $active === 'explore' ? 'active' : ''; ?>" href="<?php echo socialEsc(socialBaseUrl()); ?>/explore.php">Explore</a></li>
                <li class="nav-item"><a class="nav-link <?php echo $active === 'messages' ? 'active' : ''; ?>" href="<?php echo socialEsc(socialBaseUrl()); ?>/messages.php">Messages</a></li>
                <li class="nav-item"><a class="nav-link <?php echo $active === 'notifications' ? 'active' : ''; ?>" href="<?php echo socialEsc(socialBaseUrl()); ?>/notifications.php">Notifications</a></li>
                <li class="nav-item"><a class="nav-link <?php echo $active === 'profile' ? 'active' : ''; ?>" href="<?php echo socialEsc(socialBaseUrl()); ?>/profile.php">Profile</a></li>
                <li class="nav-item"><a class="nav-link <?php echo $active === 'settings' ? 'active' : ''; ?>" href="<?php echo socialEsc(socialBaseUrl()); ?>/settings.php">Settings</a></li>
            </ul>
            <div class="d-flex align-items-center gap-2">
                <span class="small text-muted">@<?php echo socialEsc($user['username'] ?? ''); ?></span>
                <a class="btn btn-sm btn-outline-dark" href="<?php echo socialEsc(socialBaseUrl()); ?>/logout.php">Logout</a>
            </div>
        <?php endif; ?>
      </div>
    </nav>
    <main class="container py-3">
      <?php if ($flash): ?>
          <div class="alert alert-<?php echo socialEsc($flash['type']); ?> alert-dismissible fade show" role="alert">
              <?php echo socialEsc($flash['message']); ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
      <?php endif; ?>
    <?php
}

function socialRenderFooter(): void {
    ?>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
}

socialEnsureData();
