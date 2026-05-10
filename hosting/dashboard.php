<?php
require_once __DIR__ . '/includes/auth.php';
hostingRequireLogin();

$role = $_SESSION['hosting_role'] ?? 'user';
$base = hostingGetBaseUrl();
if ($role === 'admin') {
    header("Location: {$base}/admin/dashboard.php");
} else {
    header("Location: {$base}/user/dashboard.php");
}
exit;

