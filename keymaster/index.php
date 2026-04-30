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

  <!-- ══ FOR YOUR APP ══════════════════════════════════════════ -->
  <div style="background:rgba(88,166,255,.06);border:1px solid rgba(88,166,255,.22);border-radius:10px;padding:22px 24px;margin-bottom:28px;">
    <p style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--primary);margin-bottom:4px;">📱 For Your App</p>
    <p style="font-size:.9rem;color:var(--muted);margin:0;">Use the <strong style="color:var(--text);">status</strong> endpoint to verify a user's subscription every time your app starts. It's public — no token needed. Replace <code>YOUR_DOMAIN</code> with your server's URL.</p>
  </div>

  <!-- Step 1 – The request ─────────────────────────────────── -->
  <h2 style="margin-bottom:14px;">Step 1 — Make the Request</h2>

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
    Always check <strong style="color:var(--text);">two things</strong>:
    <code>success === true</code> <em>and</em>
    <code>status === "active"</code> <em>and</em>
    <code>days_left &gt; 0</code>.
    Only when all three are true should you allow access to premium features.
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
  <div class="endpoint" style="margin-bottom:28px;">
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
          <tr style="border-bottom:1px solid var(--border);"><td style="padding:9px 14px;font-family:monospace;">success</td><td style="padding:9px 14px;color:var(--muted);">boolean</td><td style="padding:9px 14px;color:var(--muted);"><code>true</code> if a key record was found</td></tr>
          <tr style="border-bottom:1px solid var(--border);"><td style="padding:9px 14px;font-family:monospace;">device_id</td><td style="padding:9px 14px;color:var(--muted);">string</td><td style="padding:9px 14px;color:var(--muted);">The device identifier</td></tr>
          <tr style="border-bottom:1px solid var(--border);"><td style="padding:9px 14px;font-family:monospace;">api_key</td><td style="padding:9px 14px;color:var(--muted);">string</td><td style="padding:9px 14px;color:var(--muted);">The generated licence key (32-char hex)</td></tr>
          <tr style="border-bottom:1px solid var(--border);"><td style="padding:9px 14px;font-family:monospace;">plan</td><td style="padding:9px 14px;color:var(--muted);">string</td><td style="padding:9px 14px;color:var(--muted);"><code>basic</code>, <code>standard</code>, <code>premium</code>, or <code>lifetime</code></td></tr>
          <tr style="border-bottom:1px solid var(--border);"><td style="padding:9px 14px;font-family:monospace;">status</td><td style="padding:9px 14px;color:var(--muted);">string</td><td style="padding:9px 14px;color:var(--muted);"><code>active</code> · <code>expired</code> · <code>revoked</code></td></tr>
          <tr style="border-bottom:1px solid var(--border);"><td style="padding:9px 14px;font-family:monospace;">message</td><td style="padding:9px 14px;color:var(--muted);">string</td><td style="padding:9px 14px;color:var(--muted);">Human-readable status: <em>Key Active</em>, <em>Key Expired</em>, or <em>Key Revoked</em></td></tr>
          <tr style="border-bottom:1px solid var(--border);"><td style="padding:9px 14px;font-family:monospace;">days_left</td><td style="padding:9px 14px;color:var(--muted);">integer</td><td style="padding:9px 14px;color:var(--muted);">Days remaining (0 when expired or revoked)</td></tr>
          <tr style="border-bottom:1px solid var(--border);"><td style="padding:9px 14px;font-family:monospace;">expires_at</td><td style="padding:9px 14px;color:var(--muted);">string</td><td style="padding:9px 14px;color:var(--muted);">Expiry date/time in UTC (<code>YYYY-MM-DD HH:MM:SS</code>)</td></tr>
          <tr><td style="padding:9px 14px;font-family:monospace;">created_at</td><td style="padding:9px 14px;color:var(--muted);">string</td><td style="padding:9px 14px;color:var(--muted);">Key creation date/time in UTC</td></tr>
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
<pre style="margin:0;border-radius:0 6px 6px 6px;"># Check subscription
curl "https://YOUR_DOMAIN/keymaster/api.php?action=status&device_id=ANDROID-ABC123"

# Example response when active:
# {"success":true,"status":"active","days_left":27,...}

# Shell script usage
DEVICE_ID="ANDROID-ABC123"
RESPONSE=$(curl -s "https://YOUR_DOMAIN/keymaster/api.php?action=status&device_id=$DEVICE_ID")
STATUS=$(echo "$RESPONSE" | python3 -c "import sys,json; print(json.load(sys.stdin).get('status',''))")

if [ "$STATUS" = "active" ]; then
  echo "✅ Subscription valid"
else
  echo "❌ Subscription invalid: $STATUS"
fi</pre>
      </div>

      <!-- Android Java -->
      <div id="tab-java" class="code-panel" style="display:none;">
<pre style="margin:0;border-radius:0 6px 6px 6px;">// Add to build.gradle: implementation 'com.squareup.okhttp3:okhttp:4.12.0'
// Call this method in a background thread or use AsyncTask / ExecutorService

import okhttp3.*;
import org.json.JSONObject;

public class SubscriptionChecker {

    private static final String BASE_URL =
        "https://YOUR_DOMAIN/keymaster/api.php";

    public interface Callback {
        void onResult(boolean isActive, String plan, int daysLeft, String message);
        void onError(String error);
    }

    public static void checkSubscription(String deviceId, Callback callback) {
        OkHttpClient client = new OkHttpClient();

        String url = BASE_URL + "?action=status&device_id=" + deviceId;
        Request request = new Request.Builder().url(url).build();

        client.newCall(request).enqueue(new okhttp3.Callback() {
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

                    // ✅ Valid subscription = success AND active AND days_left > 0
                    boolean isActive = success
                        &amp;&amp; "active".equals(status)
                        &amp;&amp; daysLeft > 0;

                    callback.onResult(isActive, plan, daysLeft, message);

                } catch (Exception e) {
                    callback.onError("Parse error: " + e.getMessage());
                }
            }
        });
    }
}

// ── Usage in Activity ──────────────────────────────────────────
// String myDeviceId = Settings.Secure.getString(
//     getContentResolver(), Settings.Secure.ANDROID_ID);

SubscriptionChecker.checkSubscription("ANDROID-ABC123", new SubscriptionChecker.Callback() {
    @Override
    public void onResult(boolean isActive, String plan, int daysLeft, String message) {
        runOnUiThread(() -> {
            if (isActive) {
                // ✅ Allow premium access
                showPremiumContent(plan, daysLeft);
            } else {
                // ❌ Block access — show message (Expired / Revoked / Not Found)
                showSubscriptionError(message);
            }
        });
    }

    @Override
    public void onError(String error) {
        runOnUiThread(() -> showNetworkError(error));
    }
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

    data class SubscriptionResult(
        val isActive : Boolean,
        val plan     : String,
        val daysLeft : Int,
        val message  : String
    )

    /**
     * Call from a coroutine (e.g. viewModelScope.launch { ... })
     * Returns null on network / parse error.
     */
    suspend fun check(deviceId: String): SubscriptionResult? =
        withContext(Dispatchers.IO) {
            try {
                val url  = "$BASE_URL?action=status&device_id=$deviceId"
                val req  = Request.Builder().url(url).build()
                val body = client.newCall(req).execute().body!!.string()
                val json = JSONObject(body)

                val success  = json.optBoolean("success", false)
                val status   = json.optString("status", "")
                val daysLeft = json.optInt("days_left", 0)
                val plan     = json.optString("plan", "")
                val message  = json.optString("message", "")

                // ✅ Valid = success AND active AND days_left > 0
                val isActive = success &amp;&amp; status == "active" &amp;&amp; daysLeft > 0

                SubscriptionResult(isActive, plan, daysLeft, message)
            } catch (e: Exception) {
                null   // handle network / JSON errors upstream
            }
        }
}

// ── Usage in ViewModel / Activity ─────────────────────────────
// val deviceId = Settings.Secure.getString(
//     contentResolver, Settings.Secure.ANDROID_ID)

viewModelScope.launch {
    val result = SubscriptionChecker.check("ANDROID-ABC123")

    if (result == null) {
        showNetworkError()
        return@launch
    }

    if (result.isActive) {
        // ✅ Allow premium access
        showPremiumContent(result.plan, result.daysLeft)
    } else {
        // ❌ result.message = "Key Expired" / "Key Revoked" / etc.
        showSubscriptionError(result.message)
    }
}</pre>
      </div>

      <!-- Python -->
      <div id="tab-python" class="code-panel" style="display:none;">
<pre style="margin:0;border-radius:0 6px 6px 6px;">import requests

BASE_URL = "https://YOUR_DOMAIN/keymaster/api.php"

def check_subscription(device_id: str) -> dict:
    """
    Returns a dict with keys:
      is_active (bool), plan (str), days_left (int), message (str)
    Raises requests.RequestException on network errors.
    """
    resp = requests.get(BASE_URL, params={
        "action":    "status",
        "device_id": device_id,
    }, timeout=10)

    data     = resp.json()
    success  = data.get("success", False)
    status   = data.get("status", "")
    days_left = data.get("days_left", 0)

    # ✅ Valid subscription = success AND active AND days_left > 0
    is_active = success and status == "active" and days_left > 0

    return {
        "is_active": is_active,
        "plan":      data.get("plan", ""),
        "days_left": days_left,
        "message":   data.get("message", ""),
    }

# ── Usage ──────────────────────────────────────────────────────
result = check_subscription("ANDROID-ABC123")

if result["is_active"]:
    print(f"✅ Active  |  Plan: {result['plan']}  |  {result['days_left']} days left")
else:
    print(f"❌ Blocked |  {result['message']}")</pre>
      </div>

      <!-- PHP -->
      <div id="tab-php" class="code-panel" style="display:none;">
<pre style="margin:0;border-radius:0 6px 6px 6px;">&lt;?php
// Verify a subscription from PHP (e.g. a backend or bot)

define('KEYMASTER_URL', 'https://YOUR_DOMAIN/keymaster/api.php');

function check_subscription(string $device_id): array
{
    $url  = KEYMASTER_URL . '?' . http_build_query([
        'action'    => 'status',
        'device_id' => $device_id,
    ]);

    $ctx  = stream_context_create(['http' => ['timeout' => 10]]);
    $body = @file_get_contents($url, false, $ctx);

    if ($body === false) {
        return ['is_active' => false, 'message' => 'Network error'];
    }

    $data     = json_decode($body, true) ?? [];
    $success  = $data['success']   ?? false;
    $status   = $data['status']    ?? '';
    $days_left = $data['days_left'] ?? 0;

    // ✅ Valid = success AND active AND days_left > 0
    $is_active = $success &amp;&amp; $status === 'active' &amp;&amp; $days_left > 0;

    return [
        'is_active' => $is_active,
        'plan'      => $data['plan']    ?? '',
        'days_left' => $days_left,
        'message'   => $data['message'] ?? '',
    ];
}

// ── Usage ──────────────────────────────────────────────────────
$result = check_subscription('ANDROID-ABC123');

if ($result['is_active']) {
    echo "✅ Active | Plan: {$result['plan']} | {$result['days_left']} days left\n";
} else {
    echo "❌ Blocked | {$result['message']}\n";
}
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
