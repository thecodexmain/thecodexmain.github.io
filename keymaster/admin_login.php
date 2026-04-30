<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Keymaster – Admin Login</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --bg:      #0d1117;
      --card:    #161b22;
      --border:  #30363d;
      --primary: #58a6ff;
      --danger:  #f85149;
      --text:    #c9d1d9;
      --muted:   #8b949e;
      --radius:  10px;
    }
    body {
      background: var(--bg);
      color: var(--text);
      font-family: 'Segoe UI', system-ui, sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .login-box {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 36px 40px;
      width: 340px;
      text-align: center;
    }
    .login-box h1 { font-size: 1.5rem; margin-bottom: 6px; }
    .login-box p  { font-size: .85rem; color: var(--muted); margin-bottom: 24px; }
    .field { text-align: left; margin-bottom: 16px; }
    label { display: block; font-size: .8rem; font-weight: 600; text-transform: uppercase; letter-spacing: .4px; color: var(--muted); margin-bottom: 6px; }
    input[type="text"],
    input[type="password"] {
      width: 100%;
      padding: 10px 13px;
      border-radius: 6px;
      border: 1px solid var(--border);
      background: var(--bg);
      color: var(--text);
      font-size: .9rem;
      outline: none;
      transition: border-color .2s;
    }
    input:focus { border-color: var(--primary); }
    button {
      width: 100%;
      padding: 11px;
      border: none;
      border-radius: 6px;
      background: var(--primary);
      color: #0d1117;
      font-weight: 700;
      font-size: .95rem;
      cursor: pointer;
      transition: opacity .2s;
      margin-top: 4px;
    }
    button:hover { opacity: .85; }
    .error-msg {
      background: rgba(248,81,73,.12);
      border: 1px solid rgba(248,81,73,.3);
      color: var(--danger);
      border-radius: 6px;
      padding: 10px 14px;
      font-size: .85rem;
      margin-bottom: 18px;
    }
    .back { display: inline-block; margin-top: 18px; font-size: .8rem; color: var(--muted); text-decoration: none; }
    .back:hover { color: var(--text); }
  </style>
</head>
<body>
<?php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Already logged in
if (!empty($_SESSION['admin_ok'])) {
    header('Location: admin.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $hash = get_setting('admin_pass_hash');

    if (
        hash_equals(ADMIN_USER, $username) &&
        password_verify($password, $hash)
    ) {
        session_regenerate_id(true);
        $_SESSION['admin_ok'] = true;
        header('Location: admin.php');
        exit;
    } else {
        $error = 'Invalid username or password.';
    }
}
?>

<div class="login-box">
  <h1>🔑 Keymaster</h1>
  <p>Admin Panel Login</p>

  <?php if ($error): ?>
  <div class="error-msg">⚠️ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" action="">
    <div class="field">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" autocomplete="username" required />
    </div>
    <div class="field">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" autocomplete="current-password" required />
    </div>
    <button type="submit">Sign In</button>
  </form>

  <a class="back" href="index.php">← Back to Key Checker</a>
</div>
</body>
</html>
