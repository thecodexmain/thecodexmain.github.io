<?php
require_once __DIR__ . '/includes/auth.php';
hostingRequireLogin();
$base = hostingGetBaseUrl();
if (($_SESSION['hosting_role'] ?? '') === 'admin') {
    header('Location: ' . $base . '/admin/dashboard.php');
} else {
    header('Location: ' . $base . '/user/dashboard.php');
}
exit;
