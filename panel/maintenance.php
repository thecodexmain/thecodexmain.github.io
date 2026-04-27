<?php
// Standalone maintenance page — shown to non-admin visitors during maintenance
$configFile = __DIR__ . '/data/settings.json';
$settings = [];
if (file_exists($configFile)) {
    $settings = json_decode(file_get_contents($configFile), true) ?? [];
}
$siteName = $settings['site_name'] ?? 'Prime Webs';
$maintenanceMsg = $settings['maintenance_message'] ?? 'We are currently performing scheduled maintenance. We\'ll be back shortly!';
$themeColor = $settings['theme_color'] ?? '#6c5ce7';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($siteName) ?> — Maintenance</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, <?= htmlspecialchars($themeColor) ?>22 0%, #0a0a0a 60%);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            color: #e0e0e0;
        }
        .box {
            text-align: center;
            max-width: 480px;
            padding: 48px 40px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 20px;
            backdrop-filter: blur(20px);
        }
        .icon { font-size: 72px; margin-bottom: 24px; }
        h1 { font-size: 28px; font-weight: 700; color: <?= htmlspecialchars($themeColor) ?>; margin-bottom: 12px; }
        p { font-size: 15px; color: #aaa; line-height: 1.6; margin-bottom: 24px; }
        .brand { font-size: 13px; color: #555; margin-top: 32px; }
        .loader {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-bottom: 8px;
        }
        .dot {
            width: 10px; height: 10px;
            border-radius: 50%;
            background: <?= htmlspecialchars($themeColor) ?>;
            animation: bounce 1.2s ease-in-out infinite;
        }
        .dot:nth-child(2) { animation-delay: 0.2s; }
        .dot:nth-child(3) { animation-delay: 0.4s; }
        @keyframes bounce {
            0%, 80%, 100% { transform: scale(0.8); opacity: 0.5; }
            40% { transform: scale(1.2); opacity: 1; }
        }
    </style>
</head>
<body>
    <div class="box">
        <div class="icon">🔧</div>
        <h1>Under Maintenance</h1>
        <p><?= htmlspecialchars($maintenanceMsg) ?></p>
        <div class="loader"><div class="dot"></div><div class="dot"></div><div class="dot"></div></div>
        <div class="brand">© <?= date('Y') ?> <?= htmlspecialchars($siteName) ?> — made by @PrimeTheOfficial</div>
    </div>
</body>
</html>
