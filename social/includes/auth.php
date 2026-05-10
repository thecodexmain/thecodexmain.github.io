<?php
require_once __DIR__ . '/functions.php';

function socialRequireAdmin(): void {
    socialRequireLogin();
    $user = socialCurrentUser();
    if (($user['role'] ?? 'user') !== 'admin') {
        socialSetFlash('warning', 'Admin access required.');
        socialRedirect('feed.php');
    }
}
