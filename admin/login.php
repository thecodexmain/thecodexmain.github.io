<?php
/**
 * admin/login.php – Admin login page.
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

// Already logged in?
if (!empty($_SESSION['admin_logged_in'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF token check
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        $error = 'Invalid request. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === ADMIN_USERNAME
            && password_verify($password, ADMIN_PASSWORD_HASH)) {
            session_regenerate_id(true);
            $_SESSION['admin_logged_in'] = true;
            header('Location: dashboard.php');
            exit;
        }
        $error = 'Invalid username or password.';
    }
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Login</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet" />
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --bg:      #0a0a12;
      --card:    rgba(255,255,255,0.06);
      --brd:     rgba(255,255,255,0.12);
      --text:    #f0f0ff;
      --muted:   rgba(240,240,255,0.55);
      --accent:  #7209b7;
      --grad:    linear-gradient(135deg,#f72585,#7209b7,#4cc9f0);
      --font:    'Poppins', sans-serif;
    }
    body {
      font-family: var(--font);
      background: var(--bg);
      color: var(--text);
      min-height: 100dvh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
      /* subtle animated gradient bg */
      background: radial-gradient(ellipse at 30% 20%, rgba(114,9,183,0.18) 0%, transparent 60%),
                  radial-gradient(ellipse at 70% 80%, rgba(247,37,133,0.12) 0%, transparent 60%),
                  #0a0a12;
    }
    .card {
      background: var(--card);
      border: 1px solid var(--brd);
      border-radius: 20px;
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      padding: 44px 36px;
      width: 100%;
      max-width: 380px;
      box-shadow: 0 12px 48px rgba(0,0,0,0.5);
      animation: fadeUp 0.7s cubic-bezier(0.16,1,0.3,1) both;
    }
    @keyframes fadeUp {
      from { opacity:0; transform:translateY(30px); }
      to   { opacity:1; transform:translateY(0);    }
    }
    .logo {
      font-size: 1.8rem;
      font-weight: 700;
      background: var(--grad);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      text-align: center;
      margin-bottom: 6px;
    }
    .subtitle {
      text-align: center;
      color: var(--muted);
      font-size: 0.82rem;
      margin-bottom: 32px;
    }
    label {
      display: block;
      font-size: 0.78rem;
      font-weight: 600;
      color: var(--muted);
      letter-spacing: 0.06em;
      text-transform: uppercase;
      margin-bottom: 6px;
    }
    input[type="text"],
    input[type="password"] {
      width: 100%;
      background: rgba(255,255,255,0.05);
      border: 1px solid var(--brd);
      border-radius: 10px;
      color: var(--text);
      font-family: var(--font);
      font-size: 0.95rem;
      padding: 12px 14px;
      outline: none;
      transition: border-color 0.2s, box-shadow 0.2s;
      margin-bottom: 18px;
    }
    input[type="text"]:focus,
    input[type="password"]:focus {
      border-color: rgba(114,9,183,0.7);
      box-shadow: 0 0 0 3px rgba(114,9,183,0.2);
    }
    .btn-login {
      width: 100%;
      padding: 13px;
      background: linear-gradient(135deg,#f72585,#7209b7);
      color: #fff;
      font-family: var(--font);
      font-size: 0.95rem;
      font-weight: 700;
      border: none;
      border-radius: 999px;
      cursor: pointer;
      transition: transform 0.18s, box-shadow 0.18s;
      box-shadow: 0 0 20px rgba(247,37,133,0.5);
    }
    .btn-login:hover { transform: scale(1.03); box-shadow: 0 0 32px rgba(247,37,133,0.8); }
    .btn-login:active { transform: scale(0.97); }
    .error-msg {
      background: rgba(247,37,133,0.12);
      border: 1px solid rgba(247,37,133,0.35);
      color: #ff8fab;
      border-radius: 10px;
      padding: 10px 14px;
      font-size: 0.83rem;
      margin-bottom: 18px;
      text-align: center;
    }
    .back-link {
      display: block;
      text-align: center;
      margin-top: 20px;
      font-size: 0.8rem;
      color: var(--muted);
      text-decoration: none;
      transition: color 0.2s;
    }
    .back-link:hover { color: #4cc9f0; }
  </style>
</head>
<body>
<div class="card">
  <div class="logo">⚙️ Admin</div>
  <p class="subtitle">Sign in to the control panel</p>

  <?php if ($error): ?>
    <div class="error-msg"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
  <?php endif; ?>

  <form method="POST" action="login.php" autocomplete="on" novalidate>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

    <label for="username">Username</label>
    <input type="text" id="username" name="username"
           placeholder="admin"
           value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
           autocomplete="username"
           required />

    <label for="password">Password</label>
    <input type="password" id="password" name="password"
           placeholder="••••••••"
           autocomplete="current-password"
           required />

    <button type="submit" class="btn-login">Sign In</button>
  </form>

  <a href="../index.php" class="back-link">← Back to landing page</a>
</div>
</body>
</html>
