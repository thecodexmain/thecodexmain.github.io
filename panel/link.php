<?php
// Short link redirect handler
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/links_helper.php';

$code = sanitize($_GET['code'] ?? '');

if (!$code) {
    http_response_code(404);
    exit('Not found');
}

$links = readJson(DATA_PATH . 'links.json');
$link = null;
foreach ($links as $l) { if ($l['code'] === $code) { $link = $l; break; } }

if (!$link) {
    http_response_code(404);
    $siteName = getSetting('site_name', 'Prime Webs');
    echo "<!DOCTYPE html><html><head><title>Link Not Found — {$siteName}</title></head><body style='font-family:sans-serif;text-align:center;padding:60px;'><h1>🔗 Link Not Found</h1><p>This short link does not exist or has been removed.</p><a href='" . BASE_URL . "'>← Home</a></body></html>";
    exit;
}

// Check status
if (($link['status'] ?? 'active') !== 'active') {
    http_response_code(410);
    echo "<!DOCTYPE html><html><head><title>Link Disabled</title></head><body style='font-family:sans-serif;text-align:center;padding:60px;'><h1>⛔ Link Disabled</h1><p>This link has been disabled.</p></body></html>";
    exit;
}

// Check expiry
if (!empty($link['expiry']) && strtotime($link['expiry']) < time()) {
    http_response_code(410);
    echo "<!DOCTYPE html><html><head><title>Link Expired</title></head><body style='font-family:sans-serif;text-align:center;padding:60px;'><h1>⏰ Link Expired</h1><p>This short link has expired.</p></body></html>";
    exit;
}

// Check max clicks
if (!empty($link['max_clicks']) && ($link['clicks'] ?? 0) >= $link['max_clicks']) {
    http_response_code(410);
    echo "<!DOCTYPE html><html><head><title>Link Limit Reached</title></head><body style='font-family:sans-serif;text-align:center;padding:60px;'><h1>🎯 Click Limit Reached</h1><p>This link has reached its maximum click count.</p></body></html>";
    exit;
}

// Check password
$passwordRequired = !empty($link['password']);
$passwordOk = false;

if ($passwordRequired) {
    if (!empty($_POST['link_pass']) && $_POST['link_pass'] === $link['password']) {
        $passwordOk = true;
    } elseif (!empty($_SESSION['link_pass_' . $code]) && $_SESSION['link_pass_' . $code] === $link['password']) {
        $passwordOk = true;
    }
}

if ($passwordRequired && !$passwordOk) {
    $siteName = getSetting('site_name', 'Prime Webs');
    $themeColor = getSetting('theme_color', '#6c5ce7');
    $passwordError = isset($_POST['link_pass']) ? 'Incorrect password. Try again.' : '';
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Password Required — <?= htmlspecialchars($siteName) ?></title>
        <style>
            * { margin:0;padding:0;box-sizing:border-box; }
            body { min-height:100vh;display:flex;align-items:center;justify-content:center;background:#0a0a0a;font-family:'Segoe UI',sans-serif;color:#e0e0e0; }
            .box { max-width:400px;width:100%;padding:40px;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.08);border-radius:16px;text-align:center; }
            h1 { font-size:22px;margin-bottom:8px;color:<?= htmlspecialchars($themeColor) ?>; }
            p { font-size:13px;color:#888;margin-bottom:20px; }
            .error { background:#ff4d4d22;color:#ff4d4d;padding:10px;border-radius:8px;margin-bottom:16px;font-size:13px; }
            input { width:100%;padding:12px;background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.1);border-radius:8px;color:#e0e0e0;font-size:15px;margin-bottom:12px;outline:none; }
            button { width:100%;padding:12px;background:<?= htmlspecialchars($themeColor) ?>;color:#fff;border:none;border-radius:8px;font-size:15px;cursor:pointer;font-weight:600; }
            button:hover { opacity:0.9; }
        </style>
    </head>
    <body>
        <div class="box">
            <div style="font-size:48px;margin-bottom:16px;">🔒</div>
            <h1>Password Required</h1>
            <p>This link is password protected.</p>
            <?php if ($passwordError): ?><div class="error"><?= htmlspecialchars($passwordError) ?></div><?php endif; ?>
            <form method="POST">
                <input type="password" name="link_pass" placeholder="Enter password..." autofocus required>
                <button type="submit">Unlock →</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Store password in session to avoid re-entry
if ($passwordRequired) {
    $_SESSION['link_pass_' . $code] = $link['password'];
}

// Track click
trackClick($link['id']);

// Redirect
header('Location: ' . $link['target'], true, 302);
exit;
