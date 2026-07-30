<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

logout();
redirect(BASE_URL . 'login.php?msg=logged_out');
