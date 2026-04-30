<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Keymaster – Key Status Checker</title>
  <style>
    /* ── Reset & base ────────────────────────────────────────── */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --bg:       #0d1117;
      --card:     #161b22;
      --border:   #30363d;
      --primary:  #58a6ff;
      --success:  #3fb950;
      --danger:   #f85149;
      --warn:     #d29922;
      --text:     #c9d1d9;
      --muted:    #8b949e;
      --radius:   10px;
    }
    body { background: var(--bg); color: var(--text); font-family: 'Segoe UI', system-ui, sans-serif; min-height: 100vh; display: flex; flex-direction: column; }

    /* ── Nav ─────────────────────────────────────────────────── */
    nav {
      background: var(--card);
      border-bottom: 1px solid var(--border);
      padding: 14px 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    nav .brand { font-size: 1.25rem; font-weight: 700; color: var(--primary); letter-spacing: .5px; }
    nav a { color: var(--muted); text-decoration: none; font-size: .9rem; transition: color .2s; }
    nav a:hover { color: var(--text); }

    /* ── Hero ────────────────────────────────────────────────── */
    .hero {
      text-align: center;
      padding: 60px 24px 40px;
    }
    .hero h1 { font-size: 2rem; font-weight: 700; margin-bottom: 10px; }
    .hero p  { color: var(--muted); font-size: 1rem; max-width: 520px; margin: 0 auto; }

    /* ── Card ────────────────────────────────────────────────── */
    .card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 28px 32px;
      max-width: 520px;
      width: 100%;
      margin: 0 auto;
    }

    /* ── Form ────────────────────────────────────────────────── */
    label { display: block; margin-bottom: 6px; font-size: .85rem; color: var(--muted); font-weight: 600; letter-spacing: .4px; text-transform: uppercase; }
    input[type="text"] {
      width: 100%;
      padding: 11px 14px;
      border-radius: 6px;
      border: 1px solid var(--border);
      background: var(--bg);
      color: var(--text);
      font-size: .95rem;
      outline: none;
      transition: border-color .2s;
    }
    input[type="text"]:focus { border-color: var(--primary); }
    button[type="submit"] {
      margin-top: 16px;
      width: 100%;
      padding: 12px;
      border: none;
      border-radius: 6px;
      background: var(--primary);
      color: #0d1117;
      font-weight: 700;
      font-size: .95rem;
      cursor: pointer;
      transition: opacity .2s;
    }
    button[type="submit"]:hover { opacity: .85; }

    /* ── Result card ─────────────────────────────────────────── */
    .result-card {
      max-width: 520px;
      width: 100%;
      margin: 24px auto 0;
      border-radius: var(--radius);
      border: 1px solid var(--border);
      overflow: hidden;
    }
    .result-header {
      padding: 14px 20px;
      font-weight: 700;
      font-size: 1rem;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .result-header.active   { background: rgba(63,185,80,.15);  color: var(--success); }
    .result-header.expired  { background: rgba(248,81,73,.15);  color: var(--danger);  }
    .result-header.revoked  { background: rgba(210,153,34,.15); color: var(--warn);    }
    .result-header.error    { background: rgba(248,81,73,.10);  color: var(--danger);  }
    .result-body { background: var(--card); padding: 18px 20px; }
    .result-row { display: flex; justify-content: space-between; align-items: center; padding: 7px 0; border-bottom: 1px solid var(--border); font-size: .9rem; }
    .result-row:last-child { border-bottom: none; }
    .result-row .rk { color: var(--muted); }
    .result-row .rv { font-weight: 600; font-family: 'Courier New', monospace; word-break: break-all; text-align: right; }
    .badge {
      display: inline-block;
      padding: 3px 10px;
      border-radius: 20px;
      font-size: .78rem;
      font-weight: 700;
      letter-spacing: .4px;
      text-transform: uppercase;
    }
    .badge.active   { background: rgba(63,185,80,.18);  color: var(--success); }
    .badge.expired  { background: rgba(248,81,73,.18);  color: var(--danger);  }
    .badge.revoked  { background: rgba(210,153,34,.18); color: var(--warn);    }
    .days-bar { margin-top: 14px; }
    .days-label { font-size: .82rem; color: var(--muted); margin-bottom: 5px; }
    .progress { background: var(--border); border-radius: 20px; height: 8px; overflow: hidden; }
    .progress-fill { height: 100%; border-radius: 20px; transition: width .4s; }
    .progress-fill.ok      { background: var(--success); }
    .progress-fill.warning { background: var(--warn); }
    .progress-fill.danger  { background: var(--danger); }

    /* ── API docs ────────────────────────────────────────────── */
    .api-section {
      max-width: 720px;
      width: 100%;
      margin: 40px auto 60px;
      padding: 0 24px;
    }
    .api-section h2 { font-size: 1.1rem; margin-bottom: 14px; color: var(--text); }
    .endpoint {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      margin-bottom: 14px;
      overflow: hidden;
    }
    .endpoint-header {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 16px;
      border-bottom: 1px solid var(--border);
    }
    .method { font-size: .75rem; font-weight: 700; padding: 3px 9px; border-radius: 4px; letter-spacing: .5px; }
    .method.get  { background: rgba(88,166,255,.15); color: var(--primary); }
    .method.post { background: rgba(63,185,80,.15);  color: var(--success); }
    .endpoint-url { font-family: 'Courier New', monospace; font-size: .88rem; color: var(--text); }
    .endpoint-body { padding: 12px 16px; font-size: .85rem; color: var(--muted); line-height: 1.6; }
    pre { background: var(--bg); border: 1px solid var(--border); border-radius: 6px; padding: 12px 14px; font-size: .82rem; overflow-x: auto; margin-top: 8px; }

    /* ── Footer ──────────────────────────────────────────────── */
    footer { text-align: center; padding: 20px; font-size: .8rem; color: var(--muted); margin-top: auto; }
  </style>
</head>
<body>
<?php
require_once __DIR__ . '/config.php';

// ── Handle form submission ────────────────────────────────────
$result  = null;
$err_msg = '';

$query_device = isset($_GET['device_id']) ? sanitise_device_id($_GET['device_id']) : '';

if ($query_device !== '') {
    $row = get_key_status($query_device);
    if ($row) {
        $result = $row;
    } else {
        $err_msg = "No key found for device ID: <strong>" . htmlspecialchars($query_device) . "</strong>";
    }
}

// Determine status class
$status_class = 'error';
if ($result) {
    if ($result['status'] === 'revoked') {
        $status_class = 'revoked';
    } elseif ($result['is_expired'] || $result['status'] === 'expired') {
        $status_class = 'expired';
    } else {
        $status_class = 'active';
    }
}

$reg_open = get_setting('registrations_open', '1') === '1';
?>

<!-- Nav -->
<nav>
  <span class="brand">🔑 <?= htmlspecialchars(APP_NAME) ?></span>
  <a href="admin_login.php">Admin Panel →</a>
</nav>

<!-- Hero -->
<div class="hero">
  <h1>Key Status Checker</h1>
  <p>Enter your Device&nbsp;ID below to check your licence key status, plan, and remaining days.</p>
  <?php if (!$reg_open): ?>
  <p style="margin-top:12px;padding:8px 16px;background:rgba(248,81,73,.12);border:1px solid rgba(248,81,73,.3);border-radius:6px;color:#f85149;display:inline-block;font-size:.85rem;">
    ⛔ Registrations are currently <strong>closed</strong>.
  </p>
  <?php endif; ?>
</div>

<!-- Lookup form -->
<div class="card">
  <form method="GET" action="">
    <label for="device_id">Device ID</label>
    <input type="text"
           id="device_id"
           name="device_id"
           placeholder="e.g. ANDROID-ABC123"
           value="<?= htmlspecialchars($query_device) ?>"
           autocomplete="off"
           required />
    <button type="submit">Check Status</button>
  </form>
</div>

<?php if ($err_msg): ?>
<div class="result-card" style="max-width:520px;margin:24px auto 0;">
  <div class="result-header error">❌ Not Found</div>
  <div class="result-body" style="font-size:.9rem;"><?= $err_msg ?></div>
</div>
<?php endif; ?>

<?php if ($result): ?>
<?php
  $icon = match($status_class) {
      'active'  => '✅',
      'expired' => '❌',
      'revoked' => '⚠️',
      default   => '❓',
  };
  $label = match($status_class) {
      'active'  => 'Key Active',
      'expired' => 'Key Expired',
      'revoked' => 'Key Revoked',
      default   => 'Unknown',
  };

  // Days progress bar
  $pct = 0;
  if ($result['days'] > 0) {
      $pct = min(100, (int)(($result['days_left'] / $result['days']) * 100));
  }
  $bar_class = $pct > 50 ? 'ok' : ($pct > 20 ? 'warning' : 'danger');
?>
<div class="result-card">
  <div class="result-header <?= $status_class ?>">
    <?= $icon ?> <?= $label ?>
  </div>
  <div class="result-body">
    <div class="result-row">
      <span class="rk">Device ID</span>
      <span class="rv"><?= htmlspecialchars($result['device_id']) ?></span>
    </div>
    <div class="result-row">
      <span class="rk">API Key</span>
      <span class="rv"><?= htmlspecialchars($result['api_key']) ?></span>
    </div>
    <div class="result-row">
      <span class="rk">Plan</span>
      <span class="rv"><?= htmlspecialchars(ucfirst($result['plan'])) ?></span>
    </div>
    <div class="result-row">
      <span class="rk">Status</span>
      <span class="rv">
        <span class="badge <?= $status_class ?>"><?= $status_class ?></span>
      </span>
    </div>
    <div class="result-row">
      <span class="rk">Days Left</span>
      <span class="rv">
        <?php if ($result['is_expired'] || $result['status'] === 'revoked'): ?>
          <span style="color:var(--danger);">0 days</span>
        <?php else: ?>
          <?= $result['days_left'] ?> day<?= $result['days_left'] != 1 ? 's' : '' ?>
        <?php endif; ?>
      </span>
    </div>
    <div class="result-row">
      <span class="rk">Expires At</span>
      <span class="rv"><?= htmlspecialchars($result['expires_at']) ?> UTC</span>
    </div>
    <div class="result-row">
      <span class="rk">Created</span>
      <span class="rv"><?= htmlspecialchars($result['created_at']) ?> UTC</span>
    </div>

    <?php if (!$result['is_expired'] && $result['status'] === 'active'): ?>
    <div class="days-bar">
      <div class="days-label"><?= $result['days_left'] ?> / <?= $result['days'] ?> days remaining</div>
      <div class="progress">
        <div class="progress-fill <?= $bar_class ?>" style="width:<?= $pct ?>%"></div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<!-- API Documentation -->
<div class="api-section">
  <h2>📡 API Reference</h2>

  <div class="endpoint">
    <div class="endpoint-header">
      <span class="method get">GET</span>
      <span class="endpoint-url">api.php?action=status&device_id={DEVICE_ID}</span>
    </div>
    <div class="endpoint-body">
      Check the status of a key by Device ID. No authentication required.
      <pre>{
  "success": true,
  "device_id": "ANDROID-ABC123",
  "api_key": "A1B2C3D4...",
  "plan": "basic",
  "status": "active",
  "message": "Key Active",
  "days_left": 27,
  "expires_at": "2025-05-28 10:00:00",
  "created_at": "2025-04-28 10:00:00"
}</pre>
    </div>
  </div>

  <div class="endpoint">
    <div class="endpoint-header">
      <span class="method get">GET</span>
      <span class="endpoint-url">api.php?action=generate&device_id={ID}&plan={PLAN}&days={N}&admin_token={TOKEN}</span>
    </div>
    <div class="endpoint-body">
      Generate a new key for a device. Requires <code>admin_token</code>.
      <pre>{
  "success": true,
  "device_id": "ANDROID-ABC123",
  "api_key": "A1B2C3D4...",
  "plan": "premium",
  "days": 30,
  "expires_at": "2025-05-28 10:00:00"
}</pre>
    </div>
  </div>

  <div class="endpoint">
    <div class="endpoint-header">
      <span class="method get">GET</span>
      <span class="endpoint-url">api.php?action=revoke&device_id={ID}&admin_token={TOKEN}</span>
    </div>
    <div class="endpoint-body">Revoke an existing key. Requires <code>admin_token</code>.</div>
  </div>
</div>

<footer>
  <?= htmlspecialchars(APP_NAME) ?> v<?= APP_VERSION ?> &nbsp;|&nbsp; <?= date('Y') ?>
</footer>
</body>
</html>
