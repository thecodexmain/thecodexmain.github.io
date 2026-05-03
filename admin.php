<?php
declare(strict_types=1);

// Minimal admin panel for GitHub Pages-style hosting.
// If PHP is supported on the host, this persists settings and uploads to disk.

const ADMIN_KEY = 'ADMIN123';
const DATA_DIR = __DIR__ . '/admin_data';
const SETTINGS_FILE = DATA_DIR . '/settings.json';
const UPLOADS_DIR = DATA_DIR . '/uploads';

function ensureDirs(): void {
    if (!is_dir(DATA_DIR)) {
        mkdir(DATA_DIR, 0755, true);
    }
    if (!is_dir(UPLOADS_DIR)) {
        mkdir(UPLOADS_DIR, 0755, true);
    }
}

function readSettings(): array {
    $defaults = [
        'prefix' => 'Stream',
        'dp' => null,
        'apk' => null,
        'demo' => null,
    ];

    if (!is_file(SETTINGS_FILE)) {
        return $defaults;
    }

    $raw = file_get_contents(SETTINGS_FILE);
    if ($raw === false) {
        return $defaults;
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return $defaults;
    }

    return array_merge($defaults, $decoded);
}

function writeSettings(array $settings): void {
    file_put_contents(SETTINGS_FILE, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function safeBasename(string $name): string {
    $name = basename($name);
    $name = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $name);
    return $name ?: 'file';
}

function moveUpload(string $field, array $allowedExts): ?string {
    if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) {
        return null;
    }
    if (($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }

    $original = (string)($_FILES[$field]['name'] ?? 'file');
    $safe = safeBasename($original);
    $ext = strtolower(pathinfo($safe, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExts, true)) {
        return null;
    }

    $target = UPLOADS_DIR . '/' . time() . '_' . $safe;
    if (!move_uploaded_file((string)$_FILES[$field]['tmp_name'], $target)) {
        return null;
    }

    return 'admin_data/uploads/' . basename($target);
}

ensureDirs();
$settings = readSettings();

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $key = (string)($_POST['admin_key'] ?? '');
    if (!hash_equals(ADMIN_KEY, $key)) {
        $error = 'Invalid admin key.';
    } else {
        $prefix = trim((string)($_POST['prefix'] ?? ''));
        if ($prefix === '') {
            $prefix = 'Stream';
        }
        $settings['prefix'] = $prefix;

        $dp = moveUpload('dp', ['png', 'jpg', 'jpeg', 'webp', 'gif']);
        if ($dp) {
            $settings['dp'] = $dp;
        }

        $apk = moveUpload('apk', ['apk']);
        if ($apk) {
            $settings['apk'] = $apk;
        }

        $demo = moveUpload('demo', ['mp4', 'webm', 'mov', 'm4v', 'ogg']);
        if ($demo) {
            $settings['demo'] = $demo;
        }

        writeSettings($settings);
        $success = 'Saved successfully.';
    }
}

$brand = htmlspecialchars($settings['prefix'], ENT_QUOTES, 'UTF-8');
$dp = $settings['dp'] ? htmlspecialchars($settings['dp'], ENT_QUOTES, 'UTF-8') : null;
$apk = $settings['apk'] ? htmlspecialchars($settings['apk'], ENT_QUOTES, 'UTF-8') : null;
$demo = $settings['demo'] ? htmlspecialchars($settings['demo'], ENT_QUOTES, 'UTF-8') : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin – <?= $brand ?>FLIX</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet" />
  <style>
    :root { --bg:#050506; --card:rgba(255,255,255,0.07); --stroke:rgba(255,255,255,0.12); --red:#e50914; }
    *{box-sizing:border-box}
    body{margin:0;background:radial-gradient(1100px 700px at 20% 0%, rgba(229,9,20,.14), transparent 55%), linear-gradient(180deg, #000, var(--bg));color:#fff;font-family:Inter,system-ui,sans-serif}
    .container{width:min(920px,calc(100% - 32px));margin:0 auto;padding:28px 0 48px}
    .top{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:16px}
    .brand{font-weight:900;letter-spacing:-.03em;font-size:18px}
    .brand .flix{color:var(--red)}
    .card{background:var(--card);border:1px solid var(--stroke);border-radius:18px;box-shadow:0 18px 55px rgba(0,0,0,.65);overflow:hidden}
    .card h2{margin:0;padding:16px;font-size:16px;border-bottom:1px solid rgba(255,255,255,0.10)}
    form{padding:16px;display:grid;gap:12px}
    label{font-size:12.5px;color:rgba(255,255,255,0.65)}
    input[type="text"],input[type="password"],input[type="file"]{width:100%;padding:12px;border-radius:14px;border:1px solid rgba(255,255,255,0.14);background:rgba(0,0,0,0.35);color:rgba(255,255,255,0.92);outline:none}
    .row{display:grid;gap:8px}
    .actions{display:flex;gap:10px;flex-wrap:wrap}
    .btn{height:40px;padding:0 14px;border-radius:999px;border:1px solid rgba(255,255,255,0.16);background:rgba(255,255,255,0.08);color:#fff;font-weight:900;cursor:pointer}
    .btn.primary{border-color:rgba(229,9,20,0.35);background:rgba(229,9,20,0.20)}
    .hint{font-size:12.5px;color:rgba(255,255,255,0.55);line-height:1.35}
    .msg{padding:12px 16px;border-top:1px solid rgba(255,255,255,0.10);font-size:12.5px}
    .msg.ok{color:rgba(148,255,203,0.9)}
    .msg.err{color:rgba(255,160,160,0.95)}
    .preview{display:flex;gap:12px;align-items:center;flex-wrap:wrap}
    .dp{width:46px;height:46px;border-radius:14px;border:1px solid rgba(255,255,255,0.12);background:#000;object-fit:cover}
    code{background:rgba(0,0,0,0.35);border:1px solid rgba(255,255,255,0.12);padding:2px 6px;border-radius:8px}
  </style>
</head>
<body>
  <div class="container">
    <div class="top">
      <div class="brand"><?= $brand ?><span class="flix">FLIX</span> Admin</div>
      <div class="hint"><a href="/" style="color:rgba(255,255,255,0.8)">← Back to site</a></div>
    </div>

    <div class="card">
      <h2>Branding & Downloads</h2>
      <form method="post" enctype="multipart/form-data">
        <div class="row">
          <label for="admin_key">Admin key</label>
          <input id="admin_key" name="admin_key" type="password" placeholder="Enter admin key" autocomplete="off" required />
        </div>

        <div class="row">
          <label for="prefix">App name prefix (FLIX stays red automatically)</label>
          <input id="prefix" name="prefix" type="text" value="<?= $brand ?>" placeholder="e.g. Net, Se, Pro" />
          <div class="hint">Displayed as <code><?= $brand ?>FLIX</code> on the landing page.</div>
        </div>

        <div class="row">
          <label for="dp">App display picture (logo)</label>
          <input id="dp" name="dp" type="file" accept="image/*" />
          <div class="preview">
            <?php if ($dp): ?>
              <img class="dp" src="<?= $dp ?>" alt="Current app display picture" />
              <div class="hint">Current: <code><?= $dp ?></code></div>
            <?php else: ?>
              <div class="hint">No DP uploaded yet.</div>
            <?php endif; ?>
          </div>
        </div>

        <div class="row">
          <label for="apk">APK file</label>
          <input id="apk" name="apk" type="file" accept=".apk,application/vnd.android.package-archive" />
          <div class="hint">Current: <?= $apk ? '<code>' . $apk . '</code>' : 'No APK uploaded yet.' ?></div>
        </div>

        <div class="row">
          <label for="demo">Demo video (how to install)</label>
          <input id="demo" name="demo" type="file" accept="video/*" />
          <div class="hint">Current: <?= $demo ? '<code>' . $demo . '</code>' : 'No video uploaded yet.' ?></div>
        </div>

        <div class="actions">
          <button class="btn primary" type="submit">Save</button>
        </div>

        <div class="hint">Note: This works only on hosting that runs PHP. If hosted on GitHub Pages (static), use a PHP-capable host or a backend.</div>
      </form>

      <?php if ($success): ?>
        <div class="msg ok"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
      <?php elseif ($error): ?>
        <div class="msg err"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>

