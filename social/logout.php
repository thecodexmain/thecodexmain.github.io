<?php
require_once __DIR__ . '/includes/auth.php';
socialLogout();
socialSetFlash('success', 'Logged out.');
socialRedirect('index.php');
