<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();
$role = $_SESSION['user_role'] ?? 'user';
switch ($role) {
    case 'admin': redirect(BASE_URL . 'admin/'); break;
    case 'reseller': redirect(BASE_URL . 'reseller/'); break;
    default: redirect(BASE_URL . 'user/'); break;
}
