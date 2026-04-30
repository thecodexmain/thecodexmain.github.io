<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Keymaster – Admin Panel</title>
  <style>
    /* ── Reset & base ────────────────────────────────────────── */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --bg:      #0d1117;
      --card:    #161b22;
      --card2:   #1c2230;
      --border:  #30363d;
      --primary: #58a6ff;
      --success: #3fb950;
      --danger:  #f85149;
      --warn:    #d29922;
      --text:    #c9d1d9;
      --muted:   #8b949e;
      --radius:  8px;
    }
    body { background: var(--bg); color: var(--text); font-family: 'Segoe UI', system-ui, sans-serif; min-height: 100vh; }

    /* ── Nav ─────────────────────────────────────────────────── */
    nav {
      background: var(--card);
      border-bottom: 1px solid var(--border);
      padding: 14px 28px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 10px;
    }
    .brand { font-size: 1.15rem; font-weight: 700; color: var(--primary); }
    nav .nav-links { display: flex; gap: 14px; align-items: center; flex-wrap: wrap; }
    nav a { color: var(--muted); text-decoration: none; font-size: .85rem; transition: color .2s; }
    nav a:hover { color: var(--text); }
    nav a.btn-logout {
      background: rgba(248,81,73,.12);
      border: 1px solid rgba(248,81,73,.3);
      color: var(--danger);
      padding: 5px 12px;
      border-radius: 5px;
      font-weight: 600;
    }
    nav a.btn-logout:hover { background: rgba(248,81,73,.22); }

    /* ── Layout ──────────────────────────────────────────────── */
    main { max-width: 1200px; margin: 0 auto; padding: 28px 20px 60px; }

    /* ── Flash messages ──────────────────────────────────────── */
    .flash { padding: 12px 16px; border-radius: var(--radius); font-size: .88rem; margin-bottom: 20px; }
    .flash.ok  { background: rgba(63,185,80,.12);  border: 1px solid rgba(63,185,80,.3);  color: var(--success); }
    .flash.err { background: rgba(248,81,73,.12);  border: 1px solid rgba(248,81,73,.3);  color: var(--danger);  }

    /* ── Stats bar ───────────────────────────────────────────── */
    .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 28px; }
    .stat-card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 20px 22px;
    }
    .stat-card .val { font-size: 2rem; font-weight: 700; margin-bottom: 4px; }
    .stat-card .lbl { font-size: .8rem; color: var(--muted); text-transform: uppercase; letter-spacing: .5px; }

    /* ── Section heading ─────────────────────────────────────── */
    .section-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 10px;
      margin-bottom: 14px;
    }
    .section-header h2 { font-size: 1rem; font-weight: 700; }

    /* ── Action buttons ──────────────────────────────────────── */
    .btn {
      display: inline-block;
      padding: 8px 16px;
      border-radius: 5px;
      font-size: .82rem;
      font-weight: 600;
      cursor: pointer;
      border: none;
      text-decoration: none;
      transition: opacity .2s;
    }
    .btn:hover { opacity: .8; }
    .btn-primary { background: var(--primary);      color: #0d1117; }
    .btn-success { background: var(--success);      color: #0d1117; }
    .btn-danger  { background: var(--danger);       color: #fff; }
    .btn-warn    { background: rgba(210,153,34,.18); color: var(--warn); border: 1px solid rgba(210,153,34,.3); }
    .btn-ghost   { background: var(--card2);         color: var(--text); border: 1px solid var(--border); }

    /* ── Generate form ───────────────────────────────────────── */
    .gen-card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 22px 24px;
      margin-bottom: 28px;
    }
    .gen-card h3 { font-size: .9rem; font-weight: 700; margin-bottom: 16px; color: var(--muted); text-transform: uppercase; letter-spacing: .4px; }
    .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 14px; align-items: end; }
    .form-field label { display: block; font-size: .78rem; font-weight: 600; text-transform: uppercase; letter-spacing: .4px; color: var(--muted); margin-bottom: 5px; }
    .form-field input,
    .form-field select {
      width: 100%;
      padding: 9px 12px;
      border-radius: 5px;
      border: 1px solid var(--border);
      background: var(--bg);
      color: var(--text);
      font-size: .88rem;
      outline: none;
    }
    .form-field input:focus,
    .form-field select:focus { border-color: var(--primary); }

    /* ── Toggle switch ───────────────────────────────────────── */
    .toggle-row {
      display: flex;
      align-items: center;
      gap: 14px;
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 16px 22px;
      margin-bottom: 28px;
    }
    .toggle-row .tl { flex: 1; }
    .toggle-row .tl strong { display: block; font-size: .95rem; margin-bottom: 3px; }
    .toggle-row .tl span  { font-size: .82rem; color: var(--muted); }
    .switch { position: relative; display: inline-block; width: 50px; height: 26px; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider {
      position: absolute; inset: 0;
      background: var(--border);
      border-radius: 26px;
      cursor: pointer;
      transition: background .25s;
    }
    .slider::before {
      content: '';
      position: absolute;
      width: 20px; height: 20px;
      left: 3px; bottom: 3px;
      background: #fff;
      border-radius: 50%;
      transition: transform .25s;
    }
    input:checked + .slider { background: var(--success); }
    input:checked + .slider::before { transform: translateX(24px); }

    /* ── Table ───────────────────────────────────────────────── */
    .table-wrap {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      overflow: hidden;
    }
    .table-wrap table { width: 100%; border-collapse: collapse; font-size: .85rem; }
    .table-wrap thead th {
      padding: 12px 14px;
      text-align: left;
      font-size: .75rem;
      text-transform: uppercase;
      letter-spacing: .5px;
      color: var(--muted);
      background: var(--card2);
      border-bottom: 1px solid var(--border);
      white-space: nowrap;
    }
    .table-wrap tbody tr { border-bottom: 1px solid var(--border); transition: background .15s; }
    .table-wrap tbody tr:last-child { border-bottom: none; }
    .table-wrap tbody tr:hover { background: rgba(88,166,255,.04); }
    .table-wrap td { padding: 11px 14px; vertical-align: middle; }
    .table-wrap td.mono { font-family: 'Courier New', monospace; font-size: .8rem; word-break: break-all; max-width: 180px; }
    .badge {
      display: inline-block;
      padding: 3px 9px;
      border-radius: 20px;
      font-size: .72rem;
      font-weight: 700;
      letter-spacing: .4px;
      text-transform: uppercase;
    }
    .badge.active   { background: rgba(63,185,80,.15);  color: var(--success); }
    .badge.expired  { background: rgba(248,81,73,.15);  color: var(--danger);  }
    .badge.revoked  { background: rgba(210,153,34,.15); color: var(--warn);    }
    .days-pill {
      display: inline-block;
      padding: 2px 8px;
      border-radius: 20px;
      font-size: .75rem;
      font-weight: 700;
    }
    .days-pill.ok      { background: rgba(63,185,80,.15);  color: var(--success); }
    .days-pill.warning { background: rgba(210,153,34,.15); color: var(--warn);    }
    .days-pill.danger  { background: rgba(248,81,73,.15);  color: var(--danger);  }
    .mini-bar { display: flex; align-items: center; gap: 8px; }
    .mini-progress { flex: 1; height: 5px; background: var(--border); border-radius: 20px; overflow: hidden; min-width: 50px; }
    .mini-fill { height: 100%; border-radius: 20px; }
    .mini-fill.ok      { background: var(--success); }
    .mini-fill.warning { background: var(--warn);    }
    .mini-fill.danger  { background: var(--danger);  }

    /* ── Search box ──────────────────────────────────────────── */
    #searchInput {
      padding: 9px 13px;
      border-radius: 6px;
      border: 1px solid var(--border);
      background: var(--bg);
      color: var(--text);
      font-size: .85rem;
      outline: none;
      width: 220px;
    }
    #searchInput:focus { border-color: var(--primary); }

    /* ── Responsive ──────────────────────────────────────────── */
    @media (max-width: 700px) {
      .form-grid { grid-template-columns: 1fr 1fr; }
      #searchInput { width: 100%; }
      .section-header { flex-direction: column; align-items: flex-start; }
    }
  </style>
</head>
<body>
<?php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_admin();

$db      = get_db();
$flash   = ['type' => '', 'msg' => ''];
$action  = $_POST['action'] ?? ($_GET['action'] ?? '');

// ── Process POST actions ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    switch ($action) {

        case 'generate':
            $device_id = sanitise_device_id($_POST['device_id'] ?? '');
            $plan      = trim($_POST['plan'] ?? 'basic');
            $days      = max(1, (int)($_POST['days'] ?? 30));

            if ($device_id === '') {
                $flash = ['type' => 'err', 'msg' => 'Device ID is required.'];
                break;
            }

            if (get_setting('registrations_open', '1') !== '1') {
                $flash = ['type' => 'err', 'msg' => 'Registrations are closed. Open them first.'];
                break;
            }

            // Check duplicate
            $chk = $db->prepare("SELECT id FROM keys WHERE device_id = :d COLLATE NOCASE");
            $chk->execute([':d' => $device_id]);
            if ($chk->fetch()) {
                $flash = ['type' => 'err', 'msg' => "A key for device <strong>" . htmlspecialchars($device_id) . "</strong> already exists."];
                break;
            }

            $api_key    = generate_api_key();
            $expires_at = (new DateTime("+{$days} days"))->format('Y-m-d H:i:s');

            $ins = $db->prepare("
                INSERT INTO keys (device_id, api_key, plan, days, expires_at)
                VALUES (:device_id, :api_key, :plan, :days, :expires_at)
            ");
            $ins->execute([
                ':device_id'  => $device_id,
                ':api_key'    => $api_key,
                ':plan'       => $plan,
                ':days'       => $days,
                ':expires_at' => $expires_at,
            ]);

            $flash = ['type' => 'ok', 'msg' => "✅ Key generated for <strong>" . htmlspecialchars($device_id) . "</strong>: <code>" . htmlspecialchars($api_key) . "</code>"];
            break;

        case 'revoke':
            $device_id = sanitise_device_id($_POST['device_id'] ?? '');
            if ($device_id !== '') {
                $db->prepare("UPDATE keys SET status = 'revoked' WHERE device_id = :d")
                   ->execute([':d' => $device_id]);
                $flash = ['type' => 'ok', 'msg' => "Key for <strong>" . htmlspecialchars($device_id) . "</strong> has been revoked."];
            }
            break;

        case 'delete':
            $device_id = sanitise_device_id($_POST['device_id'] ?? '');
            if ($device_id !== '') {
                $db->prepare("DELETE FROM keys WHERE device_id = :d")
                   ->execute([':d' => $device_id]);
                $flash = ['type' => 'ok', 'msg' => "Key for <strong>" . htmlspecialchars($device_id) . "</strong> deleted."];
            }
            break;

        case 'toggle_reg':
            $val = ($_POST['reg_open'] ?? '0') === '1' ? '1' : '0';
            set_setting('registrations_open', $val);
            $flash = ['type' => 'ok', 'msg' => 'Registration setting updated.'];
            break;

        case 'change_password':
            $cur  = $_POST['cur_pass']   ?? '';
            $new1 = $_POST['new_pass']   ?? '';
            $new2 = $_POST['new_pass2']  ?? '';
            $hash = get_setting('admin_pass_hash');
            if (!password_verify($cur, $hash)) {
                $flash = ['type' => 'err', 'msg' => 'Current password is incorrect.'];
            } elseif (strlen($new1) < 8) {
                $flash = ['type' => 'err', 'msg' => 'New password must be at least 8 characters.'];
            } elseif ($new1 !== $new2) {
                $flash = ['type' => 'err', 'msg' => 'New passwords do not match.'];
            } else {
                set_setting('admin_pass_hash', password_hash($new1, PASSWORD_DEFAULT));
                $flash = ['type' => 'ok', 'msg' => 'Password updated successfully.'];
            }
            break;
    }
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin_login.php');
    exit;
}

// ── Fetch all keys ────────────────────────────────────────────
$all_keys  = $db->query("SELECT * FROM keys ORDER BY created_at DESC")->fetchAll();
$reg_open  = get_setting('registrations_open', '1') === '1';
$now_dt    = new DateTime('now');

// Enrich rows
$count_active  = 0;
$count_expired = 0;
$count_revoked = 0;

foreach ($all_keys as &$r) {
    $exp            = new DateTime($r['expires_at']);
    $diff           = $now_dt->diff($exp);
    $r['days_left'] = ($exp > $now_dt) ? (int)$diff->days : 0;
    $r['is_expired']= ($exp <= $now_dt);
    if ($r['is_expired'] && $r['status'] === 'active') {
        $r['status'] = 'expired';
        // Sync DB quietly
        $db->prepare("UPDATE keys SET status = 'expired' WHERE device_id = :d")
           ->execute([':d' => $r['device_id']]);
    }
    if ($r['status'] === 'active')  $count_active++;
    if ($r['status'] === 'expired') $count_expired++;
    if ($r['status'] === 'revoked') $count_revoked++;
}
unset($r);
?>

<!-- Nav -->
<nav>
  <span class="brand">🔑 <?= htmlspecialchars(APP_NAME) ?> Admin</span>
  <div class="nav-links">
    <a href="index.php">← Public Page</a>
    <a href="?logout=1" class="btn-logout">Logout</a>
  </div>
</nav>

<main>

  <!-- Flash message -->
  <?php if ($flash['msg']): ?>
  <div class="flash <?= $flash['type'] ?>"><?= $flash['msg'] ?></div>
  <?php endif; ?>

  <!-- Stats -->
  <div class="stats">
    <div class="stat-card">
      <div class="val"><?= count($all_keys) ?></div>
      <div class="lbl">Total Keys</div>
    </div>
    <div class="stat-card">
      <div class="val" style="color:var(--success)"><?= $count_active ?></div>
      <div class="lbl">Active</div>
    </div>
    <div class="stat-card">
      <div class="val" style="color:var(--danger)"><?= $count_expired ?></div>
      <div class="lbl">Expired</div>
    </div>
    <div class="stat-card">
      <div class="val" style="color:var(--warn)"><?= $count_revoked ?></div>
      <div class="lbl">Revoked</div>
    </div>
    <div class="stat-card">
      <div class="val" style="color:<?= $reg_open ? 'var(--success)' : 'var(--danger)' ?>"><?= $reg_open ? 'Open' : 'Closed' ?></div>
      <div class="lbl">Registrations</div>
    </div>
  </div>

  <!-- Toggle registrations -->
  <div class="toggle-row">
    <div class="tl">
      <strong>Allow New Registrations</strong>
      <span>When off, the API will reject all new key generation requests.</span>
    </div>
    <form method="POST" action="">
      <input type="hidden" name="action" value="toggle_reg"/>
      <label class="switch">
        <input type="checkbox"
               name="reg_open"
               value="1"
               <?= $reg_open ? 'checked' : '' ?>
               onchange="this.form.submit()"/>
        <span class="slider"></span>
      </label>
    </form>
  </div>

  <!-- Generate key form -->
  <div class="gen-card">
    <h3>Generate New Key</h3>
    <form method="POST" action="">
      <input type="hidden" name="action" value="generate"/>
      <div class="form-grid">
        <div class="form-field">
          <label for="g_device_id">Device ID</label>
          <input type="text" id="g_device_id" name="device_id" placeholder="ANDROID-ABC123" required/>
        </div>
        <div class="form-field">
          <label for="g_plan">Plan</label>
          <select id="g_plan" name="plan">
            <option value="basic">Basic</option>
            <option value="standard">Standard</option>
            <option value="premium">Premium</option>
            <option value="lifetime">Lifetime</option>
          </select>
        </div>
        <div class="form-field">
          <label for="g_days">Days</label>
          <input type="number" id="g_days" name="days" value="30" min="1" max="36500"/>
        </div>
        <div class="form-field" style="display:flex;align-items:flex-end;">
          <button type="submit" class="btn btn-success" style="width:100%;">Generate Key</button>
        </div>
      </div>
    </form>
  </div>

  <!-- Keys table -->
  <div class="section-header">
    <h2>All Device Keys</h2>
    <input type="text" id="searchInput" placeholder="Search device ID, key, plan…" oninput="filterTable()"/>
  </div>

  <div class="table-wrap">
    <table id="keysTable">
      <thead>
        <tr>
          <th>#</th>
          <th>Device ID</th>
          <th>API Key</th>
          <th>Plan</th>
          <th>Status</th>
          <th>Days Left</th>
          <th>Expires At</th>
          <th>Created At</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($all_keys)): ?>
        <tr><td colspan="9" style="text-align:center;padding:28px;color:var(--muted);">No keys found. Generate one above.</td></tr>
        <?php else: ?>
        <?php foreach ($all_keys as $i => $r):
          $sc = 'active';
          if ($r['status'] === 'revoked') $sc = 'revoked';
          elseif ($r['status'] === 'expired' || $r['is_expired']) $sc = 'expired';

          $pct = ($r['days'] > 0) ? min(100, (int)(($r['days_left'] / $r['days']) * 100)) : 0;
          $bc  = $pct > 50 ? 'ok' : ($pct > 20 ? 'warning' : 'danger');
          if ($sc !== 'active') { $pct = 0; $bc = 'danger'; }
        ?>
        <tr>
          <td style="color:var(--muted);font-size:.8rem;"><?= $i + 1 ?></td>
          <td class="mono"><?= htmlspecialchars($r['device_id']) ?></td>
          <td class="mono"><?= htmlspecialchars($r['api_key']) ?></td>
          <td><?= htmlspecialchars(ucfirst($r['plan'])) ?></td>
          <td><span class="badge <?= $sc ?>"><?= $sc ?></span></td>
          <td>
            <div class="mini-bar">
              <span class="days-pill <?= $sc === 'active' ? $bc : 'danger' ?>">
                <?= $r['days_left'] ?>d
              </span>
              <div class="mini-progress">
                <div class="mini-fill <?= $bc ?>" style="width:<?= $pct ?>%"></div>
              </div>
            </div>
          </td>
          <td style="font-size:.8rem;color:var(--muted);"><?= htmlspecialchars($r['expires_at']) ?></td>
          <td style="font-size:.8rem;color:var(--muted);"><?= htmlspecialchars($r['created_at']) ?></td>
          <td>
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
              <?php if ($r['status'] === 'active' && !$r['is_expired']): ?>
              <form method="POST" action="" onsubmit="return confirm('Revoke key for ' + <?= json_encode($r['device_id']) ?> + '?')">
                <input type="hidden" name="action" value="revoke"/>
                <input type="hidden" name="device_id" value="<?= htmlspecialchars($r['device_id']) ?>"/>
                <button type="submit" class="btn btn-warn" style="padding:5px 11px;font-size:.75rem;">Revoke</button>
              </form>
              <?php endif; ?>
              <form method="POST" action="" onsubmit="return confirm('Permanently delete key for ' + <?= json_encode($r['device_id']) ?> + '?')">
                <input type="hidden" name="action" value="delete"/>
                <input type="hidden" name="device_id" value="<?= htmlspecialchars($r['device_id']) ?>"/>
                <button type="submit" class="btn btn-danger" style="padding:5px 11px;font-size:.75rem;">Delete</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Change password -->
  <div class="gen-card" style="margin-top:32px;">
    <h3>Change Admin Password</h3>
    <form method="POST" action="">
      <input type="hidden" name="action" value="change_password"/>
      <div class="form-grid">
        <div class="form-field">
          <label>Current Password</label>
          <input type="password" name="cur_pass" required/>
        </div>
        <div class="form-field">
          <label>New Password</label>
          <input type="password" name="new_pass" required minlength="8"/>
        </div>
        <div class="form-field">
          <label>Confirm New Password</label>
          <input type="password" name="new_pass2" required/>
        </div>
        <div class="form-field" style="display:flex;align-items:flex-end;">
          <button type="submit" class="btn btn-ghost" style="width:100%;">Update Password</button>
        </div>
      </div>
    </form>
  </div>

  <!-- Admin Token display -->
  <div style="margin-top:24px;background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:16px 20px;">
    <p style="font-size:.82rem;color:var(--muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:.4px;font-weight:600;">API Admin Token</p>
    <code style="font-size:.88rem;word-break:break-all;"><?= htmlspecialchars(ADMIN_TOKEN) ?></code>
    <p style="font-size:.78rem;color:var(--muted);margin-top:6px;">Change this in <code>config.php</code> before going live.</p>
  </div>

</main>

<script>
function filterTable() {
  const q = document.getElementById('searchInput').value.toLowerCase();
  const rows = document.querySelectorAll('#keysTable tbody tr');
  rows.forEach(row => {
    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}
</script>
</body>
</html>
