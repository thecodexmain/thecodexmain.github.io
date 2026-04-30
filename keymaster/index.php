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
    .result-header.pending  { background: rgba(88,166,255,.12); color: var(--primary); }
    .result-header.rejected { background: rgba(248,81,73,.10);  color: var(--danger);  }
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
    .badge.pending  { background: rgba(88,166,255,.18); color: var(--primary); }
    .badge.rejected { background: rgba(248,81,73,.18);  color: var(--danger);  }
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

    /* ── Flash ───────────────────────────────────────────────── */
    .flash { padding: 11px 16px; border-radius: 7px; font-size: .88rem; margin: 16px auto 0; max-width: 520px; width: 100%; }
    .flash.ok   { background: rgba(63,185,80,.12);  border: 1px solid rgba(63,185,80,.3);  color: var(--success); }
    .flash.err  { background: rgba(248,81,73,.12);  border: 1px solid rgba(248,81,73,.3);  color: var(--danger);  }
    .flash.warn { background: rgba(210,153,34,.12); border: 1px solid rgba(210,153,34,.3); color: var(--warn);    }
  </style>
</head>
<body>
<?php
require_once __DIR__ . '/config.php';

// ── Handle "Request Access" form submission ───────────────────
$req_flash = ['type' => '', 'msg' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'request_access') {
    $reg_open_chk = get_setting('registrations_open', '1') === '1';
    if (!$reg_open_chk) {
        $req_flash = ['type' => 'err', 'msg' => 'Registrations are currently closed.'];
    } else {
        $req_device = sanitise_device_id($_POST['req_device_id'] ?? '');
        $req_plan   = trim($_POST['req_plan'] ?? 'basic');
        $req_note   = substr(trim($_POST['req_note'] ?? ''), 0, 200);

        if ($req_device === '') {
            $req_flash = ['type' => 'err', 'msg' => 'Device ID is required.'];
        } else {
            $db_r = get_db();

            // Check for existing key
            $ck = $db_r->prepare("SELECT id FROM keys WHERE device_id = :d COLLATE NOCASE");
            $ck->execute([':d' => $req_device]);
            if ($ck->fetch()) {
                $req_flash = ['type' => 'err', 'msg' => 'A key for this device ID already exists. Use the checker above.'];
            } else {
                // Check for existing request
                $rk = $db_r->prepare("SELECT status FROM requests WHERE device_id = :d COLLATE NOCASE");
                $rk->execute([':d' => $req_device]);
                $ex = $rk->fetch();
                if ($ex) {
                    if ($ex['status'] === 'pending') {
                        $req_flash = ['type' => 'warn', 'msg' => '⏳ A request for this device ID is already pending admin approval.'];
                    } elseif ($ex['status'] === 'rejected') {
                        $req_flash = ['type' => 'err', 'msg' => '❌ Your previous request was rejected. Please contact support.'];
                    } else {
                        $req_flash = ['type' => 'err', 'msg' => 'A request for this device ID already exists (' . htmlspecialchars($ex['status']) . ').'];
                    }
                } else {
                    $ins = $db_r->prepare("INSERT INTO requests (device_id, plan, note) VALUES (:d, :p, :n)");
                    $ins->execute([':d' => $req_device, ':p' => $req_plan, ':n' => $req_note]);
                    $req_flash = ['type' => 'ok', 'msg' => '✅ Request submitted! An admin will review and approve your request shortly.'];
                }
            }
        }
    }
}

// ── Handle status lookup ──────────────────────────────────────
$result         = null;
$pending_result = null;
$err_msg        = '';

$query_device = isset($_GET['device_id']) ? sanitise_device_id($_GET['device_id']) : '';

if ($query_device !== '') {
    $row = get_key_status($query_device);
    if ($row) {
        $result = $row;
    } else {
        // Check if a request exists
        $db_r = get_db();
        $rchk = $db_r->prepare("SELECT * FROM requests WHERE device_id = :d COLLATE NOCASE");
        $rchk->execute([':d' => $query_device]);
        $req_row = $rchk->fetch();
        if ($req_row) {
            $pending_result = $req_row;
        } else {
            $err_msg = "No key or request found for device ID: <strong>" . htmlspecialchars($query_device) . "</strong>";
        }
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

<?php if ($pending_result): ?>
<?php
  $pr_status = $pending_result['status'];
  $pr_icon   = match($pr_status) {
      'pending'  => '⏳',
      'rejected' => '❌',
      default    => '❓',
  };
  $pr_label  = match($pr_status) {
      'pending'  => 'Request Pending Approval',
      'rejected' => 'Request Rejected',
      default    => 'Request ' . ucfirst($pr_status),
  };
?>
<div class="result-card" style="max-width:520px;margin:24px auto 0;">
  <div class="result-header <?= $pr_status ?>">
    <?= $pr_icon ?> <?= $pr_label ?>
  </div>
  <div class="result-body">
    <div class="result-row">
      <span class="rk">Device ID</span>
      <span class="rv"><?= htmlspecialchars($pending_result['device_id']) ?></span>
    </div>
    <div class="result-row">
      <span class="rk">Requested Plan</span>
      <span class="rv"><?= htmlspecialchars(ucfirst($pending_result['plan'])) ?></span>
    </div>
    <div class="result-row">
      <span class="rk">Status</span>
      <span class="rv"><span class="badge <?= $pr_status ?>"><?= $pr_status ?></span></span>
    </div>
    <div class="result-row">
      <span class="rk">Requested At</span>
      <span class="rv" style="font-size:.8rem;"><?= htmlspecialchars($pending_result['requested_at']) ?> UTC</span>
    </div>
    <?php if ($pr_status === 'pending'): ?>
    <p style="margin-top:12px;font-size:.82rem;color:var(--muted);">
      ⏳ Your request is waiting for admin approval. Check back later or contact support.
    </p>
    <?php elseif ($pr_status === 'rejected'): ?>
    <p style="margin-top:12px;font-size:.82rem;color:var(--danger);">
      Your request was rejected. Please contact support for more information.
    </p>
    <?php endif; ?>
  </div>
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

<!-- Request Access -->
<?php if ($reg_open): ?>
<div class="card" style="margin-top:28px;">
  <p style="font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--primary);margin-bottom:14px;">📬 Request Access</p>
  <?php if ($req_flash['msg']): ?>
  <div class="flash <?= $req_flash['type'] ?>" style="margin:0 0 14px;"><?= $req_flash['msg'] ?></div>
  <?php endif; ?>
  <form method="POST" action="">
    <input type="hidden" name="action" value="request_access"/>
    <label for="req_device_id">Device ID</label>
    <input type="text"
           id="req_device_id"
           name="req_device_id"
           placeholder="e.g. ANDROID-ABC123"
           autocomplete="off"
           required
           style="margin-bottom:12px;"/>
    <label for="req_plan">Plan</label>
    <select id="req_plan" name="req_plan"
            style="width:100%;padding:11px 14px;border-radius:6px;border:1px solid var(--border);background:var(--bg);color:var(--text);font-size:.95rem;outline:none;margin-bottom:12px;">
      <option value="basic">Basic</option>
      <option value="standard">Standard</option>
      <option value="premium">Premium</option>
      <option value="lifetime">Lifetime</option>
    </select>
    <label for="req_note">Note <span style="color:var(--muted);font-weight:400;">(optional)</span></label>
    <input type="text"
           id="req_note"
           name="req_note"
           placeholder="e.g. Purchase order #1234"
           maxlength="200"
           autocomplete="off"
           style="margin-bottom:0;"/>
    <button type="submit" style="margin-top:16px;">Submit Request</button>
  </form>
</div>
<?php elseif ($req_flash['msg']): ?>
<div class="flash <?= $req_flash['type'] ?>"><?= $req_flash['msg'] ?></div>
<?php endif; ?>

<!-- API Documentation -->
<div class="api-section">

  <!-- ══ FOR YOUR APP ══════════════════════════════════════════ -->
  <div style="background:rgba(88,166,255,.06);border:1px solid rgba(88,166,255,.22);border-radius:10px;padding:22px 24px;margin-bottom:28px;">
    <p style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--primary);margin-bottom:4px;">📱 For Your App</p>
    <p style="font-size:.9rem;color:var(--muted);margin:0;">The approval workflow has two public endpoints: <strong style="color:var(--text);">request</strong> (submit a registration request) and <strong style="color:var(--text);">status</strong> (check if the request was approved and the key is active). Replace <code>YOUR_DOMAIN</code> with your server's URL.</p>
  </div>

  <!-- Step 0 – Submit a request ───────────────────────────── -->
  <h2 style="margin-bottom:14px;">Step 0 — Submit a Registration Request (first run only)</h2>

  <div class="endpoint" style="margin-bottom:10px;">
    <div class="endpoint-header">
      <span class="method get">GET</span>
      <span class="endpoint-url">https://YOUR_DOMAIN/keymaster/api.php?action=request&amp;device_id=DEVICE_ID&amp;plan=PLAN</span>
    </div>
    <div class="endpoint-body">
      <p style="margin-bottom:10px;">Call this once when the app is first installed. No token required. The request sits in <strong>pending</strong> state until an admin approves it.</p>
      <table style="width:100%;border-collapse:collapse;font-size:.83rem;">
        <thead>
          <tr style="border-bottom:1px solid var(--border);">
            <th style="padding:6px 10px;text-align:left;color:var(--muted);font-weight:600;">Parameter</th>
            <th style="padding:6px 10px;text-align:left;color:var(--muted);font-weight:600;">Required</th>
            <th style="padding:6px 10px;text-align:left;color:var(--muted);font-weight:600;">Description</th>
          </tr>
        </thead>
        <tbody>
          <tr><td style="padding:6px 10px;font-family:monospace;">action</td><td style="padding:6px 10px;color:var(--success);">Yes</td><td style="padding:6px 10px;color:var(--muted);">Must be <code>request</code></td></tr>
          <tr style="border-top:1px solid var(--border);"><td style="padding:6px 10px;font-family:monospace;">device_id</td><td style="padding:6px 10px;color:var(--success);">Yes</td><td style="padding:6px 10px;color:var(--muted);">Unique device identifier</td></tr>
          <tr style="border-top:1px solid var(--border);"><td style="padding:6px 10px;font-family:monospace;">plan</td><td style="padding:6px 10px;color:var(--muted);">No</td><td style="padding:6px 10px;color:var(--muted);"><code>basic</code> / <code>standard</code> / <code>premium</code> / <code>lifetime</code> (default: <code>basic</code>)</td></tr>
          <tr style="border-top:1px solid var(--border);"><td style="padding:6px 10px;font-family:monospace;">note</td><td style="padding:6px 10px;color:var(--muted);">No</td><td style="padding:6px 10px;color:var(--muted);">Short message to the admin (max 200 chars)</td></tr>
        </tbody>
      </table>
<pre style="margin-top:10px;">{
  "success": true,
  "message": "Request submitted. Awaiting admin approval.",
  "device_id": "ANDROID-ABC123",
  "plan": "premium",
  "status": "pending"
}</pre>
      <p style="font-size:.8rem;color:var(--muted);margin-top:8px;">HTTP 409 if a request already exists for this device_id. HTTP 403 if registrations are closed.</p>
    </div>
  </div>

  <!-- Step 1 – The request ─────────────────────────────────── -->
  <h2 style="margin:28px 0 14px;">Step 1 — Check Status (call on every app start)</h2>

  <div class="endpoint">
    <div class="endpoint-header">
      <span class="method get">GET</span>
      <span class="endpoint-url">https://YOUR_DOMAIN/keymaster/api.php?action=status&amp;device_id=DEVICE_ID</span>
    </div>
    <div class="endpoint-body">
      <p style="margin-bottom:10px;">Send the user's <strong>Device&nbsp;ID</strong> as a query parameter. No API key or token required. The server replies with JSON.</p>
      <table style="width:100%;border-collapse:collapse;font-size:.83rem;">
        <thead>
          <tr style="border-bottom:1px solid var(--border);">
            <th style="padding:6px 10px;text-align:left;color:var(--muted);font-weight:600;">Parameter</th>
            <th style="padding:6px 10px;text-align:left;color:var(--muted);font-weight:600;">Required</th>
            <th style="padding:6px 10px;text-align:left;color:var(--muted);font-weight:600;">Description</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td style="padding:6px 10px;font-family:monospace;">action</td>
            <td style="padding:6px 10px;color:var(--success);">Yes</td>
            <td style="padding:6px 10px;color:var(--muted);">Must be <code>status</code></td>
          </tr>
          <tr style="border-top:1px solid var(--border);">
            <td style="padding:6px 10px;font-family:monospace;">device_id</td>
            <td style="padding:6px 10px;color:var(--success);">Yes</td>
            <td style="padding:6px 10px;color:var(--muted);">The unique device identifier you assigned to this user</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Step 2 – Response states ─────────────────────────────── -->
  <h2 style="margin:28px 0 14px;">Step 2 — Read the Response</h2>

  <p style="font-size:.88rem;color:var(--muted);margin-bottom:16px;">
    Check <strong style="color:var(--text);">three things</strong>:
    <code>success === true</code> <em>and</em>
    <code>status === "active"</code> <em>and</em>
    <code>days_left &gt; 0</code>.
    Only when all three are true should you allow access to premium features.
    If <code>status === "pending"</code> tell the user their request is awaiting approval.
  </p>

  <!-- ✅ Active -->
  <div class="endpoint" style="margin-bottom:10px;">
    <div class="endpoint-header" style="background:rgba(63,185,80,.08);border-bottom:1px solid rgba(63,185,80,.15);">
      <span style="font-size:.78rem;font-weight:700;color:var(--success);">✅ ACTIVE — allow access</span>
    </div>
    <div class="endpoint-body">
<pre>{
  "success": true,
  "device_id": "ANDROID-ABC123",
  "api_key": "A1B2C3D4E5F6...",
  "plan": "premium",
  "status": "active",
  "message": "Key Active",
  "days_left": 27,
  "expires_at": "2025-05-28 10:00:00",
  "created_at": "2025-04-28 10:00:00"
}</pre>
    </div>
  </div>

  <!-- ❌ Expired -->
  <div class="endpoint" style="margin-bottom:10px;">
    <div class="endpoint-header" style="background:rgba(248,81,73,.08);border-bottom:1px solid rgba(248,81,73,.15);">
      <span style="font-size:.78rem;font-weight:700;color:var(--danger);">❌ EXPIRED — block access, show renewal message</span>
    </div>
    <div class="endpoint-body">
<pre>{
  "success": true,
  "device_id": "ANDROID-ABC123",
  "api_key": "A1B2C3D4E5F6...",
  "plan": "premium",
  "status": "expired",
  "message": "Key Expired",
  "days_left": 0,
  "expires_at": "2025-03-01 10:00:00",
  "created_at": "2025-02-01 10:00:00"
}</pre>
    </div>
  </div>

  <!-- ⚠️ Revoked -->
  <div class="endpoint" style="margin-bottom:10px;">
    <div class="endpoint-header" style="background:rgba(210,153,34,.08);border-bottom:1px solid rgba(210,153,34,.15);">
      <span style="font-size:.78rem;font-weight:700;color:var(--warn);">⚠️ REVOKED — block access</span>
    </div>
    <div class="endpoint-body">
<pre>{
  "success": true,
  "device_id": "ANDROID-ABC123",
  "api_key": "A1B2C3D4E5F6...",
  "plan": "basic",
  "status": "revoked",
  "message": "Key Revoked",
  "days_left": 0,
  "expires_at": "2025-06-01 10:00:00",
  "created_at": "2025-04-01 10:00:00"
}</pre>
    </div>
  </div>

  <!-- 🔍 Not found -->
  <div class="endpoint" style="margin-bottom:10px;">
    <div class="endpoint-header" style="background:rgba(248,81,73,.06);border-bottom:1px solid rgba(248,81,73,.12);">
      <span style="font-size:.78rem;font-weight:700;color:var(--danger);">🔍 NOT FOUND (HTTP 404) — no key registered for this device</span>
    </div>
    <div class="endpoint-body">
<pre>{
  "success": false,
  "message": "No key found for this device_id."
}</pre>
    </div>
  </div>

  <!-- ⏳ Pending -->
  <div class="endpoint" style="margin-bottom:10px;">
    <div class="endpoint-header" style="background:rgba(88,166,255,.06);border-bottom:1px solid rgba(88,166,255,.15);">
      <span style="font-size:.78rem;font-weight:700;color:var(--primary);">⏳ PENDING (HTTP 202) — request submitted, awaiting admin approval</span>
    </div>
    <div class="endpoint-body">
<pre>{
  "success": false,
  "device_id": "ANDROID-ABC123",
  "plan": "premium",
  "status": "pending",
  "message": "Request Pending Approval",
  "requested_at": "2025-04-28 10:00:00"
}</pre>
      <p style="font-size:.8rem;color:var(--muted);margin-top:6px;">Show the user a "Waiting for approval" message. Do NOT grant access.</p>
    </div>
  </div>

  <!-- ❌ Rejected -->
  <div class="endpoint" style="margin-bottom:28px;">
    <div class="endpoint-header" style="background:rgba(248,81,73,.06);border-bottom:1px solid rgba(248,81,73,.12);">
      <span style="font-size:.78rem;font-weight:700;color:var(--danger);">❌ REJECTED (HTTP 403) — admin rejected the request</span>
    </div>
    <div class="endpoint-body">
<pre>{
  "success": false,
  "device_id": "ANDROID-ABC123",
  "plan": "premium",
  "status": "rejected",
  "message": "Request Rejected",
  "requested_at": "2025-04-28 10:00:00"
}</pre>
      <p style="font-size:.8rem;color:var(--muted);margin-top:6px;">Tell the user their request was rejected and to contact support.</p>
    </div>
  </div>

  <!-- Step 3 – Response field reference ──────────────────────── -->
  <h2 style="margin-bottom:14px;">Step 3 — Response Field Reference</h2>
  <div class="endpoint" style="margin-bottom:28px;">
    <div class="endpoint-body" style="padding:0;">
      <table style="width:100%;border-collapse:collapse;font-size:.83rem;">
        <thead>
          <tr style="background:var(--card2);">
            <th style="padding:10px 14px;text-align:left;color:var(--muted);font-weight:600;border-bottom:1px solid var(--border);">Field</th>
            <th style="padding:10px 14px;text-align:left;color:var(--muted);font-weight:600;border-bottom:1px solid var(--border);">Type</th>
            <th style="padding:10px 14px;text-align:left;color:var(--muted);font-weight:600;border-bottom:1px solid var(--border);">Description</th>
          </tr>
        </thead>
        <tbody>
          <tr style="border-bottom:1px solid var(--border);"><td style="padding:9px 14px;font-family:monospace;">success</td><td style="padding:9px 14px;color:var(--muted);">boolean</td><td style="padding:9px 14px;color:var(--muted);"><code>true</code> only when an approved active key was found</td></tr>
          <tr style="border-bottom:1px solid var(--border);"><td style="padding:9px 14px;font-family:monospace;">device_id</td><td style="padding:9px 14px;color:var(--muted);">string</td><td style="padding:9px 14px;color:var(--muted);">The device identifier</td></tr>
          <tr style="border-bottom:1px solid var(--border);"><td style="padding:9px 14px;font-family:monospace;">api_key</td><td style="padding:9px 14px;color:var(--muted);">string</td><td style="padding:9px 14px;color:var(--muted);">The generated licence key (32-char hex) — only present after approval</td></tr>
          <tr style="border-bottom:1px solid var(--border);"><td style="padding:9px 14px;font-family:monospace;">plan</td><td style="padding:9px 14px;color:var(--muted);">string</td><td style="padding:9px 14px;color:var(--muted);"><code>basic</code>, <code>standard</code>, <code>premium</code>, or <code>lifetime</code></td></tr>
          <tr style="border-bottom:1px solid var(--border);"><td style="padding:9px 14px;font-family:monospace;">status</td><td style="padding:9px 14px;color:var(--muted);">string</td><td style="padding:9px 14px;color:var(--muted);"><code>active</code> · <code>expired</code> · <code>revoked</code> · <code>pending</code> · <code>rejected</code></td></tr>
          <tr style="border-bottom:1px solid var(--border);"><td style="padding:9px 14px;font-family:monospace;">message</td><td style="padding:9px 14px;color:var(--muted);">string</td><td style="padding:9px 14px;color:var(--muted);">Human-readable status: <em>Key Active</em>, <em>Expired</em>, <em>Revoked</em>, <em>Request Pending Approval</em>, <em>Request Rejected</em></td></tr>
          <tr style="border-bottom:1px solid var(--border);"><td style="padding:9px 14px;font-family:monospace;">days_left</td><td style="padding:9px 14px;color:var(--muted);">integer</td><td style="padding:9px 14px;color:var(--muted);">Days remaining (0 when expired or revoked; absent for pending/rejected)</td></tr>
          <tr style="border-bottom:1px solid var(--border);"><td style="padding:9px 14px;font-family:monospace;">expires_at</td><td style="padding:9px 14px;color:var(--muted);">string</td><td style="padding:9px 14px;color:var(--muted);">Expiry date/time in UTC (<code>YYYY-MM-DD HH:MM:SS</code>); absent for pending/rejected</td></tr>
          <tr><td style="padding:9px 14px;font-family:monospace;">requested_at</td><td style="padding:9px 14px;color:var(--muted);">string</td><td style="padding:9px 14px;color:var(--muted);">Request submission time (UTC) — present for pending/rejected responses</td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Step 4 – Code examples ──────────────────────────────────── -->
  <h2 style="margin-bottom:14px;">Step 4 — Code Examples</h2>

  <!-- Tab bar -->
  <div style="display:flex;gap:6px;margin-bottom:-1px;flex-wrap:wrap;" id="codeTabs">
    <button class="code-tab active" onclick="showTab('curl')">cURL</button>
    <button class="code-tab" onclick="showTab('java')">Android Java</button>
    <button class="code-tab" onclick="showTab('kotlin')">Android Kotlin</button>
    <button class="code-tab" onclick="showTab('python')">Python</button>
    <button class="code-tab" onclick="showTab('php')">PHP</button>
  </div>

  <div class="endpoint" style="border-radius:0 8px 8px 8px;margin-bottom:28px;">
    <div class="endpoint-body" style="padding:0;">

      <!-- cURL -->
      <div id="tab-curl" class="code-panel">
<pre style="margin:0;border-radius:0 6px 6px 6px;"># ── Step 0: Submit registration request (first run only) ───────
curl "https://YOUR_DOMAIN/keymaster/api.php?action=request&device_id=ANDROID-ABC123&plan=premium"
# {"success":true,"status":"pending","message":"Request submitted. Awaiting admin approval.",...}

# ── Step 1: Check subscription (every app start) ────────────────
curl "https://YOUR_DOMAIN/keymaster/api.php?action=status&device_id=ANDROID-ABC123"

# Shell script that handles all states
DEVICE_ID="ANDROID-ABC123"
RESPONSE=$(curl -s "https://YOUR_DOMAIN/keymaster/api.php?action=status&device_id=$DEVICE_ID")
STATUS=$(echo "$RESPONSE" | python3 -c "import sys,json; print(json.load(sys.stdin).get('status',''))")
SUCCESS=$(echo "$RESPONSE" | python3 -c "import sys,json; d=json.load(sys.stdin); print(str(d.get('success',False)).lower())")

if [ "$SUCCESS" = "true" ] && [ "$STATUS" = "active" ]; then
  echo "✅ Subscription valid"
elif [ "$STATUS" = "pending" ]; then
  echo "⏳ Awaiting admin approval"
elif [ "$STATUS" = "rejected" ]; then
  echo "❌ Request rejected — contact support"
else
  echo "❌ Subscription invalid: $STATUS"
fi</pre>
      </div>

      <!-- Android Java -->
      <div id="tab-java" class="code-panel" style="display:none;">
<pre style="margin:0;border-radius:0 6px 6px 6px;">// Add to build.gradle: implementation 'com.squareup.okhttp3:okhttp:4.12.0'

import okhttp3.*;
import org.json.JSONObject;

public class SubscriptionChecker {

    private static final String BASE_URL =
        "https://YOUR_DOMAIN/keymaster/api.php";

    public enum KeyState { ACTIVE, PENDING, REJECTED, EXPIRED, REVOKED, NOT_FOUND, ERROR }

    public interface Callback {
        void onResult(KeyState state, String plan, int daysLeft, String message);
        void onError(String error);
    }

    /** Submit a registration request (call once on first install). */
    public static void submitRequest(String deviceId, String plan, Callback callback) {
        OkHttpClient client = new OkHttpClient();
        String url = BASE_URL + "?action=request&device_id=" + deviceId + "&plan=" + plan;
        client.newCall(new Request.Builder().url(url).build()).enqueue(new okhttp3.Callback() {
            @Override public void onFailure(Call call, IOException e) { callback.onError(e.getMessage()); }
            @Override public void onResponse(Call call, Response r) throws IOException {
                try {
                    JSONObject json = new JSONObject(r.body().string());
                    String status  = json.optString("status", "");
                    String message = json.optString("message", "");
                    KeyState state = "pending".equals(status) ? KeyState.PENDING : KeyState.ERROR;
                    callback.onResult(state, json.optString("plan",""), 0, message);
                } catch (Exception e) { callback.onError(e.getMessage()); }
            }
        });
    }

    /** Check subscription status (call on every app start). */
    public static void checkSubscription(String deviceId, Callback callback) {
        OkHttpClient client = new OkHttpClient();
        String url = BASE_URL + "?action=status&device_id=" + deviceId;

        client.newCall(new Request.Builder().url(url).build()).enqueue(new okhttp3.Callback() {
            @Override
            public void onFailure(Call call, IOException e) {
                callback.onError("Network error: " + e.getMessage());
            }

            @Override
            public void onResponse(Call call, Response response) throws IOException {
                try {
                    String body = response.body().string();
                    JSONObject json = new JSONObject(body);

                    boolean success  = json.optBoolean("success", false);
                    String  status   = json.optString("status", "");
                    int     daysLeft = json.optInt("days_left", 0);
                    String  plan     = json.optString("plan", "");
                    String  message  = json.optString("message", "");

                    KeyState state;
                    if (success &amp;&amp; "active".equals(status) &amp;&amp; daysLeft > 0) {
                        state = KeyState.ACTIVE;
                    } else if ("pending".equals(status)) {
                        state = KeyState.PENDING;
                    } else if ("rejected".equals(status)) {
                        state = KeyState.REJECTED;
                    } else if ("expired".equals(status)) {
                        state = KeyState.EXPIRED;
                    } else if ("revoked".equals(status)) {
                        state = KeyState.REVOKED;
                    } else {
                        state = KeyState.NOT_FOUND;
                    }

                    callback.onResult(state, plan, daysLeft, message);

                } catch (Exception e) {
                    callback.onError("Parse error: " + e.getMessage());
                }
            }
        });
    }
}

// ── Usage in Activity ──────────────────────────────────────────
SubscriptionChecker.checkSubscription(deviceId, new SubscriptionChecker.Callback() {
    @Override
    public void onResult(SubscriptionChecker.KeyState state, String plan, int daysLeft, String message) {
        runOnUiThread(() -> {
            switch (state) {
                case ACTIVE:
                    showPremiumContent(plan, daysLeft);  // ✅ allow access
                    break;
                case PENDING:
                    showMessage("⏳ Your request is awaiting admin approval.");
                    break;
                case REJECTED:
                    showMessage("❌ Your request was rejected. Contact support.");
                    break;
                default:
                    showSubscriptionError(message);      // expired/revoked/not found
            }
        });
    }
    @Override
    public void onError(String error) { runOnUiThread(() -> showNetworkError(error)); }
});</pre>
      </div>

      <!-- Android Kotlin -->
      <div id="tab-kotlin" class="code-panel" style="display:none;">
<pre style="margin:0;border-radius:0 6px 6px 6px;">// Add to build.gradle:
// implementation("com.squareup.okhttp3:okhttp:4.12.0")
// implementation("org.jetbrains.kotlinx:kotlinx-coroutines-android:1.7.3")

import kotlinx.coroutines.*
import okhttp3.OkHttpClient
import okhttp3.Request
import org.json.JSONObject

object SubscriptionChecker {

    private const val BASE_URL = "https://YOUR_DOMAIN/keymaster/api.php"
    private val client = OkHttpClient()

    enum class KeyState { ACTIVE, PENDING, REJECTED, EXPIRED, REVOKED, NOT_FOUND, ERROR }

    data class SubscriptionResult(
        val state    : KeyState,
        val plan     : String,
        val daysLeft : Int,
        val message  : String
    )

    /** Submit a registration request — call once on first install. */
    suspend fun submitRequest(deviceId: String, plan: String = "basic"): SubscriptionResult? =
        withContext(Dispatchers.IO) {
            try {
                val url  = "$BASE_URL?action=request&device_id=$deviceId&plan=$plan"
                val body = client.newCall(Request.Builder().url(url).build()).execute().body!!.string()
                val json = JSONObject(body)
                val status = json.optString("status", "")
                SubscriptionResult(
                    state    = if (status == "pending") KeyState.PENDING else KeyState.ERROR,
                    plan     = json.optString("plan", ""),
                    daysLeft = 0,
                    message  = json.optString("message", "")
                )
            } catch (e: Exception) { null }
        }

    /** Check subscription status — call on every app start. */
    suspend fun check(deviceId: String): SubscriptionResult? =
        withContext(Dispatchers.IO) {
            try {
                val url  = "$BASE_URL?action=status&device_id=$deviceId"
                val body = client.newCall(Request.Builder().url(url).build()).execute().body!!.string()
                val json = JSONObject(body)

                val success  = json.optBoolean("success", false)
                val status   = json.optString("status", "")
                val daysLeft = json.optInt("days_left", 0)

                val state = when {
                    success &amp;&amp; status == "active" &amp;&amp; daysLeft > 0 -> KeyState.ACTIVE
                    status == "pending"  -> KeyState.PENDING
                    status == "rejected" -> KeyState.REJECTED
                    status == "expired"  -> KeyState.EXPIRED
                    status == "revoked"  -> KeyState.REVOKED
                    else                 -> KeyState.NOT_FOUND
                }

                SubscriptionResult(state, json.optString("plan",""), daysLeft, json.optString("message",""))
            } catch (e: Exception) { null }
        }
}

// ── Usage in ViewModel / Activity ─────────────────────────────
viewModelScope.launch {
    val result = SubscriptionChecker.check(deviceId) ?: run { showNetworkError(); return@launch }

    when (result.state) {
        SubscriptionChecker.KeyState.ACTIVE   -> showPremiumContent(result.plan, result.daysLeft)
        SubscriptionChecker.KeyState.PENDING  -> showMessage("⏳ Your request is awaiting admin approval.")
        SubscriptionChecker.KeyState.REJECTED -> showMessage("❌ Your request was rejected. Contact support.")
        else                                  -> showSubscriptionError(result.message)
    }
}</pre>
      </div>

      <!-- Python -->
      <div id="tab-python" class="code-panel" style="display:none;">
<pre style="margin:0;border-radius:0 6px 6px 6px;">import requests
from enum import Enum

BASE_URL = "https://YOUR_DOMAIN/keymaster/api.php"

class KeyState(Enum):
    ACTIVE    = "active"
    PENDING   = "pending"
    REJECTED  = "rejected"
    EXPIRED   = "expired"
    REVOKED   = "revoked"
    NOT_FOUND = "not_found"

def submit_request(device_id: str, plan: str = "basic") -> dict:
    """Submit a registration request (call once on first install)."""
    resp = requests.get(BASE_URL, params={"action": "request", "device_id": device_id, "plan": plan}, timeout=10)
    return resp.json()

def check_subscription(device_id: str) -> dict:
    """
    Check subscription status (call on every app start).
    Returns: { state: KeyState, plan, days_left, message }
    """
    resp = requests.get(BASE_URL, params={"action": "status", "device_id": device_id}, timeout=10)
    data     = resp.json()
    success  = data.get("success", False)
    status   = data.get("status", "")
    days_left = data.get("days_left", 0)

    if success and status == "active" and days_left > 0:
        state = KeyState.ACTIVE
    elif status == "pending":
        state = KeyState.PENDING
    elif status == "rejected":
        state = KeyState.REJECTED
    elif status == "expired":
        state = KeyState.EXPIRED
    elif status == "revoked":
        state = KeyState.REVOKED
    else:
        state = KeyState.NOT_FOUND

    return {"state": state, "plan": data.get("plan",""), "days_left": days_left, "message": data.get("message","")}

# ── Usage ──────────────────────────────────────────────────────
result = check_subscription("ANDROID-ABC123")

if result["state"] == KeyState.ACTIVE:
    print(f"✅ Active  |  Plan: {result['plan']}  |  {result['days_left']} days left")
elif result["state"] == KeyState.PENDING:
    print("⏳ Request pending admin approval")
elif result["state"] == KeyState.REJECTED:
    print("❌ Request rejected — contact support")
else:
    print(f"❌ Blocked |  {result['message']}")</pre>
      </div>

      <!-- PHP -->
      <div id="tab-php" class="code-panel" style="display:none;">
<pre style="margin:0;border-radius:0 6px 6px 6px;">&lt;?php
define('KEYMASTER_URL', 'https://YOUR_DOMAIN/keymaster/api.php');

/** Submit a registration request — call once on first install. */
function submit_request(string $device_id, string $plan = 'basic'): array
{
    $url  = KEYMASTER_URL . '?' . http_build_query(['action' => 'request', 'device_id' => $device_id, 'plan' => $plan]);
    $ctx  = stream_context_create(['http' => ['timeout' => 10]]);
    $body = @file_get_contents($url, false, $ctx);
    return $body !== false ? (json_decode($body, true) ?? []) : ['success' => false, 'message' => 'Network error'];
}

/** Check subscription status — call on every app start. */
function check_subscription(string $device_id): array
{
    $url  = KEYMASTER_URL . '?' . http_build_query(['action' => 'status', 'device_id' => $device_id]);
    $ctx  = stream_context_create(['http' => ['timeout' => 10]]);
    $body = @file_get_contents($url, false, $ctx);

    if ($body === false) {
        return ['state' => 'error', 'message' => 'Network error'];
    }

    $data      = json_decode($body, true) ?? [];
    $success   = $data['success']   ?? false;
    $status    = $data['status']    ?? '';
    $days_left = $data['days_left'] ?? 0;

    $state = match(true) {
        $success &amp;&amp; $status === 'active' &amp;&amp; $days_left > 0 => 'active',
        $status === 'pending'  => 'pending',
        $status === 'rejected' => 'rejected',
        $status === 'expired'  => 'expired',
        $status === 'revoked'  => 'revoked',
        default                => 'not_found',
    };

    return ['state' => $state, 'plan' => $data['plan'] ?? '', 'days_left' => $days_left, 'message' => $data['message'] ?? ''];
}

// ── Usage ──────────────────────────────────────────────────────
$result = check_subscription('ANDROID-ABC123');

match($result['state']) {
    'active'   => print("✅ Active | Plan: {$result['plan']} | {$result['days_left']} days left\n"),
    'pending'  => print("⏳ Request pending admin approval\n"),
    'rejected' => print("❌ Request rejected — contact support\n"),
    default    => print("❌ Blocked | {$result['message']}\n"),
};
</pre>
      </div>

    </div>
  </div>

  <!-- ══ ADMIN ENDPOINTS ══════════════════════════════════════ -->
  <h2 style="margin-bottom:14px;">📡 Admin API Reference</h2>

  <div class="endpoint">
    <div class="endpoint-header">
      <span class="method get">GET</span>
      <span class="endpoint-url">api.php?action=generate&amp;device_id={ID}&amp;plan={PLAN}&amp;days={N}&amp;admin_token={TOKEN}</span>
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
      <span class="endpoint-url">api.php?action=revoke&amp;device_id={ID}&amp;admin_token={TOKEN}</span>
    </div>
    <div class="endpoint-body">Revoke an existing key. Requires <code>admin_token</code>.</div>
  </div>

  <div class="endpoint" style="margin-top:10px;">
    <div class="endpoint-header">
      <span class="method get">GET</span>
      <span class="endpoint-url">api.php?action=approve&amp;device_id={ID}&amp;days={N}&amp;admin_token={TOKEN}</span>
    </div>
    <div class="endpoint-body">Approve a pending request and generate its key. Optional <code>plan</code> and <code>days</code> override the requested values. Requires <code>admin_token</code>.</div>
  </div>

  <div class="endpoint" style="margin-top:10px;">
    <div class="endpoint-header">
      <span class="method get">GET</span>
      <span class="endpoint-url">api.php?action=reject&amp;device_id={ID}&amp;admin_token={TOKEN}</span>
    </div>
    <div class="endpoint-body">Reject a pending request. Requires <code>admin_token</code>.</div>
  </div>

  <div class="endpoint" style="margin-top:10px;">
    <div class="endpoint-header">
      <span class="method get">GET</span>
      <span class="endpoint-url">api.php?action=list_requests&amp;status=pending|approved|rejected|all&amp;admin_token={TOKEN}</span>
    </div>
    <div class="endpoint-body">List registration requests filtered by status (default <code>pending</code>). Requires <code>admin_token</code>.</div>
  </div>

  <div class="endpoint" style="margin-top:10px;">
    <div class="endpoint-header">
      <span class="method get">GET</span>
      <span class="endpoint-url">api.php?action=toggle_reg&amp;value=0|1&amp;admin_token={TOKEN}</span>
    </div>
    <div class="endpoint-body">Open (<code>value=1</code>) or close (<code>value=0</code>) new registrations. Requires <code>admin_token</code>.</div>
  </div>

  <div class="endpoint" style="margin-top:10px;">
    <div class="endpoint-header">
      <span class="method get">GET</span>
      <span class="endpoint-url">api.php?action=list&amp;admin_token={TOKEN}</span>
    </div>
    <div class="endpoint-body">List all keys with days_left and status. Requires <code>admin_token</code>.</div>
  </div>

</div>

<style>
  .code-tab {
    padding: 7px 16px;
    border-radius: 6px 6px 0 0;
    border: 1px solid var(--border);
    border-bottom: none;
    background: var(--card);
    color: var(--muted);
    font-size: .8rem;
    font-weight: 600;
    cursor: pointer;
    transition: background .15s, color .15s;
  }
  .code-tab:hover { background: var(--card2); color: var(--text); }
  .code-tab.active { background: var(--card2); color: var(--primary); border-color: var(--border); }
</style>
<script>
function showTab(name) {
  document.querySelectorAll('.code-panel').forEach(p => p.style.display = 'none');
  document.querySelectorAll('.code-tab').forEach(t => t.classList.remove('active'));
  document.getElementById('tab-' + name).style.display = 'block';
  event.currentTarget.classList.add('active');
}
</script>

<footer>
  <?= htmlspecialchars(APP_NAME) ?> v<?= APP_VERSION ?> &nbsp;|&nbsp; <?= date('Y') ?>
</footer>
</body>
</html>
