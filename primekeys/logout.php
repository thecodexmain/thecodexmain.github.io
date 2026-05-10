<?php
require_once __DIR__ . '/includes/auth.php';
$currentUser = primeGetCurrentUser();
if (!empty($currentUser['id'])) {
    primeAddAuditLog($currentUser, 'logout', 'Signed out of the PRIME KEYS portal.');
}
primeLogoutUser();
header('Location: ' . primeGetBaseUrl() . '/index.php');
exit;
