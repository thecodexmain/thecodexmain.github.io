<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
$role = $_SESSION['role'] ?? '';
$base = getBaseUrl();
switch ($role) {
    case 'super_admin':
    case 'admin':
        header("Location: $base/admin/dashboard.php"); break;
    case 'teacher':
        header("Location: $base/teacher/dashboard.php"); break;
    case 'student':
        header("Location: $base/student/dashboard.php"); break;
    case 'parent':
        header("Location: $base/parent/dashboard.php"); break;
    case 'accountant':
        header("Location: $base/accountant/dashboard.php"); break;
    default:
        header("Location: $base/index.php");
}
exit;
