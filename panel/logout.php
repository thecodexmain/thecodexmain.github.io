<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/json_db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

logout();
redirect('/panel/index.php');
