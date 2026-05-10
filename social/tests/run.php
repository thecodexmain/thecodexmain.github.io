<?php
require_once __DIR__ . '/../includes/functions.php';

echo "Running social app functional tests...\n";

$testDir = __DIR__ . '/tmp-data';
@mkdir($testDir, 0777, true);

function t_assert(bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

// Use dedicated files for deterministic tests.
$files = ['users','posts','follows','likes','comments','saves','shares','stories','reels','messages','notifications','blocks','reports'];
foreach ($files as $f) {
    socialSave($f, []);
}

[$okA, ] = socialCreateUser('admin_user', 'Admin User', 'password123');
[$okB, ] = socialCreateUser('alice', 'Alice', 'password123');
[$okC, ] = socialCreateUser('bob', 'Bob', 'password123');
t_assert($okA && $okB && $okC, 'User creation failed');

$alice = socialFindUserByUsername('alice');
$bob = socialFindUserByUsername('bob');
t_assert($alice !== null && $bob !== null, 'User lookup failed');

[$okPost, ] = socialCreatePost($alice['id'], 'Hello #world @bob', '', 'none');
t_assert($okPost, 'Post creation should succeed');

$post = socialLoad('posts')[0] ?? null;
t_assert($post !== null, 'Post not persisted');
t_assert(in_array('world', $post['hashtags'] ?? [], true), 'Hashtag extraction failed');
t_assert(in_array('bob', $post['mentions'] ?? [], true), 'Mention extraction failed');

$followed = socialToggleFollow($bob['id'], $alice['id']);
t_assert($followed, 'Follow should be created');
t_assert(socialIsFollowing($bob['id'], $alice['id']), 'Follow state should be true');

$liked = socialToggleReaction('likes', $bob['id'], $post['id']);
t_assert($liked, 'Like should be created');
t_assert(socialHasReaction('likes', $bob['id'], $post['id']), 'Like state should be true');

[$okComment, ] = socialCreateComment($bob['id'], $post['id'], 'Nice post');
t_assert($okComment, 'Comment should succeed');

[$okMsg, ] = socialSendMessage($alice['id'], $bob['id'], 'hi');
t_assert($okMsg, 'Message should succeed');

[$okBlock, ] = socialBlockUser($bob['id'], $alice['id']);
t_assert($okBlock, 'Block should succeed');
[$okBlockedMsg, $blockedMsg] = socialSendMessage($alice['id'], $bob['id'], 'are you there?');
t_assert(!$okBlockedMsg && str_contains($blockedMsg, 'unavailable'), 'Blocked message should fail');

[$okReport, ] = socialCreateReport($alice['id'], $bob['id'], 'abuse', 'details');
t_assert($okReport, 'Report should succeed');
$open = socialOpenReports();
t_assert(count($open) === 1, 'Open report should be listed');

echo "All social app tests passed.\n";
