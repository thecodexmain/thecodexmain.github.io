<?php
/**
 * admin/dashboard.php – Admin control panel.
 * Handles: settings edit, video/image upload, live preview iframe.
 */

require_once __DIR__ . '/../config.php';

session_name(SESSION_NAME);
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();

// Auth guard
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$success = '';
$errors  = [];

// ── Handle logout ────────────────────────────────────────────────
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

// ── Handle POST ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        $errors[] = 'Invalid CSRF token. Please refresh and try again.';
    } else {
        $s = read_settings();

        // ── Text fields ──────────────────────────────────────────
        $textFields = [
            'headline', 'subheadline', 'btn_primary',
            'btn_secondary', 'badge_text', 'download_link',
        ];
        foreach ($textFields as $f) {
            if (isset($_POST[$f])) {
                $s[$f] = mb_substr(trim($_POST[$f]), 0, 512);
            }
        }

        // ── Numeric fields ───────────────────────────────────────
        if (isset($_POST['countdown'])) {
            $s['countdown'] = max(0, (int) $_POST['countdown']);
        }
        if (isset($_POST['slots'])) {
            $s['slots'] = max(0, (int) $_POST['slots']);
        }

        // ── Toggle ───────────────────────────────────────────────
        $s['animations'] = !empty($_POST['animations']);

        // ── Video upload ─────────────────────────────────────────
        if (!empty($_FILES['video']['name'])) {
            $result = handle_upload('video', ALLOWED_VIDEO_MIME, 'uploads/');
            if ($result['ok']) {
                $s['video'] = $result['path'];
            } else {
                $errors[] = 'Video: ' . $result['error'];
            }
        }

        // ── Fallback image upload ─────────────────────────────────
        if (!empty($_FILES['fallback_image']['name'])) {
            $result = handle_upload('fallback_image', ALLOWED_IMAGE_MIME, 'uploads/');
            if ($result['ok']) {
                $s['fallback_image'] = $result['path'];
            } else {
                $errors[] = 'Image: ' . $result['error'];
            }
        }

        if (empty($errors)) {
            if (write_settings($s)) {
                $success = 'Settings saved successfully!';
            } else {
                $errors[] = 'Could not write settings.json. Check folder permissions.';
            }
        }
    }
}

// Reload fresh settings
$s = read_settings();

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// Click stats
$clicks = [];
if (file_exists(CLICKS_FILE)) {
    $raw = file_get_contents(CLICKS_FILE);
    $clicks = json_decode($raw, true) ?: [];
}
$totalClicks = array_sum($clicks);
$todayClicks = $clicks[date('Y-m-d')] ?? 0;

// ── Helper: safe file upload ──────────────────────────────────────
function handle_upload(string $field, array $allowedMime, string $relPath): array {
    $file = $_FILES[$field];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Upload error code ' . $file['error']];
    }
    if ($file['size'] > MAX_UPLOAD_BYTES) {
        return ['ok' => false, 'error' => 'File too large (max 50 MB).'];
    }

    // MIME check (finfo is more reliable than browser-provided type)
    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    if (!in_array($mimeType, $allowedMime, true)) {
        return ['ok' => false, 'error' => "Invalid file type ($mimeType)."];
    }

    // Build safe filename
    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $safeName = $field . '_' . time() . '.' . $ext;
    $dest     = UPLOADS_DIR . $safeName;

    if (!is_dir(UPLOADS_DIR)) {
        mkdir(UPLOADS_DIR, 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['ok' => false, 'error' => 'Could not move uploaded file.'];
    }

    return ['ok' => true, 'path' => $relPath . $safeName];
}

function v(string $key, array $s): string {
    return htmlspecialchars((string)($s[$key] ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Dashboard</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <style>
    /* ═══════════════════════════════════════════ RESET */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --bg:       #0d0d18;
      --sidebar:  #0a0a14;
      --card:     rgba(255,255,255,0.045);
      --brd:      rgba(255,255,255,0.09);
      --text:     #e8e8f8;
      --muted:    rgba(232,232,248,0.5);
      --accent:   #7209b7;
      --pink:     #f72585;
      --cyan:     #4cc9f0;
      --grad:     linear-gradient(135deg,#f72585,#7209b7);
      --font:     'Poppins', sans-serif;
      --sidebar-w: 220px;
    }
    body {
      font-family: var(--font);
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
      display: flex;
    }

    /* ═══════════════════════════════════════════ SIDEBAR */
    .sidebar {
      width: var(--sidebar-w);
      background: var(--sidebar);
      border-right: 1px solid var(--brd);
      display: flex;
      flex-direction: column;
      padding: 28px 16px;
      position: fixed;
      top: 0; left: 0; bottom: 0;
      z-index: 100;
      transition: transform 0.3s;
    }
    .sidebar-logo {
      font-size: 1.2rem;
      font-weight: 700;
      background: var(--grad);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 30px;
      padding-left: 4px;
    }
    .sidebar nav a {
      display: flex;
      align-items: center;
      gap: 10px;
      color: var(--muted);
      text-decoration: none;
      font-size: 0.85rem;
      padding: 9px 12px;
      border-radius: 10px;
      transition: background 0.18s, color 0.18s;
      margin-bottom: 4px;
    }
    .sidebar nav a:hover, .sidebar nav a.active {
      background: rgba(255,255,255,0.07);
      color: var(--text);
    }
    .sidebar nav a span.icon { font-size: 1rem; }
    .sidebar .logout-btn {
      margin-top: auto;
      display: flex;
      align-items: center;
      gap: 8px;
      background: rgba(247,37,133,0.1);
      border: 1px solid rgba(247,37,133,0.25);
      color: #ff8fab;
      text-decoration: none;
      font-size: 0.83rem;
      font-weight: 600;
      padding: 9px 14px;
      border-radius: 10px;
      transition: background 0.18s;
    }
    .sidebar .logout-btn:hover { background: rgba(247,37,133,0.2); }

    /* ═══════════════════════════════════════════ MAIN */
    .main {
      margin-left: var(--sidebar-w);
      flex: 1;
      padding: 32px 28px;
      max-width: 900px;
    }

    /* ═══════════════════════════════════════════ TOP BAR */
    .topbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 28px;
    }
    .topbar h1 {
      font-size: 1.35rem;
      font-weight: 700;
    }
    .topbar .preview-btn {
      background: var(--grad);
      color: #fff;
      font-family: var(--font);
      font-size: 0.8rem;
      font-weight: 600;
      border: none;
      border-radius: 999px;
      padding: 8px 20px;
      cursor: pointer;
      transition: transform 0.18s, box-shadow 0.18s;
      text-decoration: none;
    }
    .topbar .preview-btn:hover { transform: scale(1.04); box-shadow: 0 0 20px rgba(247,37,133,0.55); }

    /* ═══════════════════════════════════════════ STATS */
    .stats-row {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
      gap: 14px;
      margin-bottom: 28px;
    }
    .stat-card {
      background: var(--card);
      border: 1px solid var(--brd);
      border-radius: 14px;
      padding: 18px 16px;
      backdrop-filter: blur(10px);
    }
    .stat-card .stat-label {
      font-size: 0.7rem;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: var(--muted);
      margin-bottom: 6px;
    }
    .stat-card .stat-val {
      font-size: 1.6rem;
      font-weight: 700;
    }
    .stat-card .stat-val.pink { color: var(--pink); }
    .stat-card .stat-val.cyan { color: var(--cyan); }
    .stat-card .stat-val.purple { color: #b5179e; }

    /* ═══════════════════════════════════════════ SECTIONS */
    .section {
      background: var(--card);
      border: 1px solid var(--brd);
      border-radius: 16px;
      padding: 24px;
      margin-bottom: 20px;
      backdrop-filter: blur(10px);
    }
    .section-title {
      font-size: 0.9rem;
      font-weight: 700;
      letter-spacing: 0.04em;
      margin-bottom: 18px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .section-title .dot {
      width: 8px; height: 8px;
      border-radius: 50%;
      background: var(--grad);
    }

    /* ═══════════════════════════════════════════ FORM ELEMENTS */
    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
    }
    .form-grid.one { grid-template-columns: 1fr; }

    .field {
      display: flex;
      flex-direction: column;
      gap: 6px;
    }
    .field label {
      font-size: 0.73rem;
      font-weight: 600;
      color: var(--muted);
      letter-spacing: 0.06em;
      text-transform: uppercase;
    }
    .field input[type="text"],
    .field input[type="number"],
    .field input[type="url"],
    .field textarea {
      background: rgba(255,255,255,0.04);
      border: 1px solid var(--brd);
      border-radius: 10px;
      color: var(--text);
      font-family: var(--font);
      font-size: 0.88rem;
      padding: 10px 12px;
      outline: none;
      transition: border-color 0.2s, box-shadow 0.2s;
      resize: vertical;
    }
    .field input:focus, .field textarea:focus {
      border-color: rgba(114,9,183,0.7);
      box-shadow: 0 0 0 3px rgba(114,9,183,0.18);
    }
    .field textarea { min-height: 68px; }

    /* File input */
    .file-wrap {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }
    .file-wrap label.file-label {
      font-size: 0.73rem;
      font-weight: 600;
      color: var(--muted);
      letter-spacing: 0.06em;
      text-transform: uppercase;
    }
    .file-wrap input[type="file"] {
      background: rgba(255,255,255,0.04);
      border: 1px dashed var(--brd);
      border-radius: 10px;
      color: var(--text);
      font-family: var(--font);
      font-size: 0.82rem;
      padding: 10px 12px;
      cursor: pointer;
      transition: border-color 0.2s;
    }
    .file-wrap input[type="file"]:hover { border-color: var(--cyan); }
    .file-current {
      font-size: 0.73rem;
      color: var(--cyan);
      word-break: break-all;
    }

    /* Toggle switch */
    .toggle-row {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .toggle-row label.toggle-label {
      font-size: 0.85rem;
      color: var(--text);
      cursor: pointer;
    }
    .switch { position: relative; display: inline-block; width: 44px; height: 24px; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider {
      position: absolute; inset: 0;
      background: rgba(255,255,255,0.1);
      border-radius: 999px;
      transition: background 0.25s;
      cursor: pointer;
    }
    .slider::before {
      content: '';
      position: absolute;
      width: 18px; height: 18px;
      left: 3px; top: 3px;
      background: #fff;
      border-radius: 50%;
      transition: transform 0.25s;
    }
    .switch input:checked + .slider { background: var(--accent); }
    .switch input:checked + .slider::before { transform: translateX(20px); }

    /* ═══════════════════════════════════════════ SAVE BUTTON */
    .save-btn {
      background: linear-gradient(135deg,#f72585,#7209b7);
      color: #fff;
      font-family: var(--font);
      font-size: 0.95rem;
      font-weight: 700;
      border: none;
      border-radius: 999px;
      padding: 13px 40px;
      cursor: pointer;
      transition: transform 0.18s, box-shadow 0.18s;
      box-shadow: 0 0 22px rgba(247,37,133,0.5);
      display: block;
      margin-top: 24px;
    }
    .save-btn:hover { transform: scale(1.03); box-shadow: 0 0 32px rgba(247,37,133,0.75); }
    .save-btn:active { transform: scale(0.97); }

    /* ═══════════════════════════════════════════ ALERTS */
    .alert {
      border-radius: 12px;
      padding: 12px 16px;
      margin-bottom: 20px;
      font-size: 0.85rem;
    }
    .alert-success {
      background: rgba(76,201,176,0.12);
      border: 1px solid rgba(76,201,176,0.35);
      color: #4dd0a0;
    }
    .alert-error {
      background: rgba(247,37,133,0.1);
      border: 1px solid rgba(247,37,133,0.3);
      color: #ff8fab;
    }
    .alert ul { padding-left: 16px; }

    /* ═══════════════════════════════════════════ PREVIEW */
    .preview-wrap {
      border: 1px solid var(--brd);
      border-radius: 14px;
      overflow: hidden;
      margin-top: 8px;
    }
    .preview-wrap iframe {
      width: 100%;
      height: 420px;
      border: none;
      display: block;
    }

    /* ═══════════════════════════════════════════ RESPONSIVE */
    @media (max-width: 700px) {
      .sidebar { transform: translateX(-100%); }
      .sidebar.open { transform: translateX(0); }
      .main { margin-left: 0; padding: 20px 14px; }
      .form-grid { grid-template-columns: 1fr; }
      .menu-toggle {
        display: flex;
        position: fixed; top: 14px; left: 14px; z-index: 200;
        background: var(--grad);
        border: none;
        border-radius: 8px;
        padding: 8px 10px;
        cursor: pointer;
        font-size: 1.1rem;
        color: #fff;
      }
    }
    @media (min-width: 701px) { .menu-toggle { display: none; } }
  </style>
</head>
<body>

<!-- Mobile menu toggle -->
<button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">☰</button>

<!-- ── Sidebar ── -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">⚙️ TheCodex Admin</div>
  <nav>
    <a href="dashboard.php" class="active">
      <span class="icon">🎛️</span> Dashboard
    </a>
    <a href="../index.php" target="_blank">
      <span class="icon">🌐</span> View Site
    </a>
  </nav>
  <a href="?logout=1" class="logout-btn">
    <span>🚪</span> Log Out
  </a>
</aside>

<!-- ── Main content ── -->
<div class="main">
  <div class="topbar">
    <h1>Dashboard</h1>
    <a href="../index.php" target="_blank" class="preview-btn">🔍 Live Preview</a>
  </div>

  <!-- Stats -->
  <div class="stats-row">
    <div class="stat-card">
      <div class="stat-label">Total Clicks</div>
      <div class="stat-val pink"><?= number_format($totalClicks) ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Today's Clicks</div>
      <div class="stat-val cyan"><?= number_format($todayClicks) ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Slots Remaining</div>
      <div class="stat-val purple"><?= (int)$s['slots'] ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Countdown (s)</div>
      <div class="stat-val"><?= (int)$s['countdown'] ?></div>
    </div>
  </div>

  <!-- Flash messages -->
  <?php if ($success): ?>
    <div class="alert alert-success">✅ <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
  <?php endif; ?>
  <?php if ($errors): ?>
    <div class="alert alert-error">
      <strong>⚠️ Errors:</strong>
      <ul><?php foreach ($errors as $e): ?>
        <li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li>
      <?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <!-- ════════ FORM ════════ -->
  <form method="POST" action="dashboard.php"
        enctype="multipart/form-data" novalidate>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

    <!-- Text Content -->
    <div class="section">
      <div class="section-title"><span class="dot"></span> Text Content</div>
      <div class="form-grid">
        <div class="field">
          <label for="headline">Headline</label>
          <input type="text" id="headline" name="headline"
                 value="<?= v('headline', $s) ?>"
                 placeholder="Your App Title" maxlength="120" />
        </div>
        <div class="field">
          <label for="badge_text">Badge Text</label>
          <input type="text" id="badge_text" name="badge_text"
                 value="<?= v('badge_text', $s) ?>"
                 placeholder="Limited slots available!" maxlength="80" />
        </div>
        <div class="field" style="grid-column:1/-1">
          <label for="subheadline">Subheadline</label>
          <textarea id="subheadline" name="subheadline"
                    placeholder="Catchy subtitle here 🔥"
                    maxlength="280"><?= v('subheadline', $s) ?></textarea>
        </div>
      </div>
    </div>

    <!-- Buttons & Links -->
    <div class="section">
      <div class="section-title"><span class="dot"></span> Buttons &amp; Links</div>
      <div class="form-grid">
        <div class="field">
          <label for="btn_primary">Primary Button Text</label>
          <input type="text" id="btn_primary" name="btn_primary"
                 value="<?= v('btn_primary', $s) ?>"
                 placeholder="Download Now" maxlength="60" />
        </div>
        <div class="field">
          <label for="btn_secondary">Secondary Button Text</label>
          <input type="text" id="btn_secondary" name="btn_secondary"
                 value="<?= v('btn_secondary', $s) ?>"
                 placeholder="Learn More" maxlength="60" />
        </div>
        <div class="field" style="grid-column:1/-1">
          <label for="download_link">Download / CTA Link (URL or APK path)</label>
          <input type="text" id="download_link" name="download_link"
                 value="<?= v('download_link', $s) ?>"
                 placeholder="https://example.com/app.apk" maxlength="512" />
        </div>
      </div>
    </div>

    <!-- Countdown & Slots -->
    <div class="section">
      <div class="section-title"><span class="dot"></span> Countdown &amp; Slots</div>
      <div class="form-grid">
        <div class="field">
          <label for="countdown">Countdown Duration (seconds)</label>
          <input type="number" id="countdown" name="countdown"
                 value="<?= (int)$s['countdown'] ?>"
                 min="0" max="86400" />
        </div>
        <div class="field">
          <label for="slots">Slots Available</label>
          <input type="number" id="slots" name="slots"
                 value="<?= (int)$s['slots'] ?>"
                 min="0" max="9999" />
        </div>
      </div>
    </div>

    <!-- Media Uploads -->
    <div class="section">
      <div class="section-title"><span class="dot"></span> Background Media</div>
      <div class="form-grid">
        <div class="file-wrap">
          <label class="file-label" for="video">Background Video (MP4, max 50 MB)</label>
          <input type="file" id="video" name="video" accept="video/mp4" />
          <span class="file-current">Current: <?= v('video', $s) ?></span>
        </div>
        <div class="file-wrap">
          <label class="file-label" for="fallback_image">Fallback Image (JPG / PNG / WebP)</label>
          <input type="file" id="fallback_image" name="fallback_image"
                 accept="image/jpeg,image/png,image/webp" />
          <span class="file-current">Current: <?= v('fallback_image', $s) ?></span>
        </div>
      </div>
    </div>

    <!-- Animations -->
    <div class="section">
      <div class="section-title"><span class="dot"></span> Animations</div>
      <div class="toggle-row">
        <label class="switch">
          <input type="checkbox" name="animations" id="animations"
                 <?= $s['animations'] ? 'checked' : '' ?> />
          <span class="slider"></span>
        </label>
        <label class="toggle-label" for="animations">Enable all page animations</label>
      </div>
    </div>

    <button type="submit" class="save-btn">💾 Save Settings</button>
  </form>

  <!-- ════════ LIVE PREVIEW ════════ -->
  <div class="section" style="margin-top:28px">
    <div class="section-title"><span class="dot"></span> Live Preview</div>
    <p style="font-size:0.78rem;color:var(--muted);margin-bottom:10px;">
      Shows the landing page as it currently appears (after saving).
    </p>
    <div class="preview-wrap">
      <iframe src="../index.php" title="Live preview" loading="lazy"
              sandbox="allow-scripts allow-same-origin"></iframe>
    </div>
  </div>
</div><!-- /.main -->

<script>
// Mobile sidebar toggle
const toggle  = document.getElementById('menuToggle');
const sidebar = document.getElementById('sidebar');
toggle?.addEventListener('click', () => sidebar.classList.toggle('open'));

// Close sidebar when clicking outside
document.addEventListener('click', e => {
  if (window.innerWidth <= 700
      && sidebar.classList.contains('open')
      && !sidebar.contains(e.target)
      && e.target !== toggle) {
    sidebar.classList.remove('open');
  }
});
</script>
</body>
</html>
