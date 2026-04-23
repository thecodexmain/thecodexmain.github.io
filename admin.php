<?php
// ============================================================
//  Admin Panel — Google Play Store Page Manager
//  Password-protected single-file interface
//  Uses database.json as data store (no MySQL)
// ============================================================

session_start();

// ---- CONFIG ----
// To generate a new hash, run from CLI: php -r "echo password_hash('YourNewPassword', PASSWORD_DEFAULT);"
// Then replace the string below with the output.
define('ADMIN_PASSWORD_HASH', '$2y$10$w6LuJbmz6YBNHh8KBjjb0.v5R7LXbg1JxSL/U.eqJRpJ4zmC5GaMu');
define('DATA_FILE', __DIR__ . '/database.json');

// ---- LOGOUT ----
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// ---- LOGIN HANDLER ----
$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if (password_verify($_POST['password'], ADMIN_PASSWORD_HASH)) {
        $_SESSION['admin_auth'] = true;
        header('Location: admin.php');
        exit;
    } else {
        $loginError = 'Incorrect password. Please try again.';
    }
}

// ---- SAVE HANDLER (authenticated) ----
$saveMessage = '';
$saveError   = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save']) && !empty($_SESSION['admin_auth'])) {
    // Read current data (with file existence check)
    if (!file_exists(DATA_FILE)) {
        $saveError = 'Data file not found.';
    } else {
        $current = json_decode(file_get_contents(DATA_FILE), true) ?? [];

        // Sanitise & update text fields
        $current['app_title']   = trim(strip_tags($_POST['app_title']   ?? ''));
        $current['dev_name']    = trim(strip_tags($_POST['dev_name']    ?? ''));
        $current['description'] = trim(strip_tags($_POST['description'] ?? ''));
        $current['category']    = trim(strip_tags($_POST['category']    ?? ''));
        $current['domain']      = trim(strip_tags($_POST['domain']      ?? ''));
        $current['rating']      = trim(strip_tags($_POST['rating']      ?? ''));
        $current['reviews']     = trim(strip_tags($_POST['reviews']     ?? ''));
        $current['downloads']   = trim(strip_tags($_POST['downloads']   ?? ''));

        // Validate URL fields — only http/https allowed
        $current['app_icon'] = safe_url($_POST['app_icon'] ?? '');
        $current['apk_link'] = safe_url($_POST['apk_link'] ?? '');

        // Screenshots: newline-separated URLs → validated http/https array
        $rawScreenshots = trim($_POST['screenshot_urls'] ?? '');
        $current['screenshot_urls'] = array_values(
            array_filter(
                array_map(function (string $u): string {
                    return safe_url($u);
                }, explode("\n", $rawScreenshots))
            )
        );

        // Write back with exclusive lock to prevent race conditions
        $result = file_put_contents(
            DATA_FILE,
            json_encode($current, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
        if ($result !== false) {
            $saveMessage = 'Changes saved successfully!';
        } else {
            $saveError = 'Failed to write file. Check server permissions.';
        }
    }
}

// ---- LOAD DATA ----
$data = [];
if (file_exists(DATA_FILE)) {
    $data = json_decode(file_get_contents(DATA_FILE), true) ?? [];
}

$isAuth = !empty($_SESSION['admin_auth']);

// ---- HELPERS ----
function esc(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

/**
 * Validate that a URL uses http or https only.
 * Returns the URL if valid, empty string otherwise.
 */
function safe_url(string $url): string {
    $url = trim($url);
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return '';
    }
    $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
    return in_array($scheme, ['http', 'https'], true) ? $url : '';
}
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Panel — Play Store Manager</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            'play-green':  '#01875f',
            'play-gray':   '#9aa0a6',
            'play-divider':'#3c4043',
          }
        }
      }
    };
  </script>
</head>
<body class="bg-gray-950 text-gray-100 min-h-screen font-sans antialiased">

<?php if (!$isAuth): ?>
<!-- ============================================================
     LOGIN GATE
============================================================ -->
<div class="min-h-screen flex items-center justify-center px-4">
  <div class="w-full max-w-sm bg-gray-900 rounded-2xl shadow-2xl p-8">

    <!-- Logo -->
    <div class="flex flex-col items-center mb-8">
      <div class="w-14 h-14 rounded-2xl bg-play-green flex items-center justify-center mb-4 shadow-lg">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
        </svg>
      </div>
      <h1 class="text-xl font-bold text-white">Admin Panel</h1>
      <p class="text-sm text-gray-400 mt-1">Play Store Page Manager</p>
    </div>

    <!-- Error -->
    <?php if ($loginError): ?>
    <div class="bg-red-900/50 border border-red-700 text-red-300 text-sm rounded-lg px-4 py-3 mb-5">
      <?= esc($loginError) ?>
    </div>
    <?php endif; ?>

    <!-- Form -->
    <form method="POST" action="admin.php">
      <div class="mb-4">
        <label for="password" class="block text-sm text-gray-400 mb-1.5 font-medium">Password</label>
        <input
          type="password"
          id="password"
          name="password"
          autofocus
          required
          placeholder="Enter admin password"
          class="w-full bg-gray-800 border border-gray-700 text-white placeholder-gray-500 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-play-green focus:ring-1 focus:ring-play-green transition"
        />
      </div>
      <button
        type="submit"
        class="w-full bg-play-green hover:bg-green-700 active:bg-green-800 text-white font-semibold py-2.5 rounded-lg transition text-sm"
      >
        Sign In
      </button>
    </form>

  </div>
</div>

<?php else: ?>
<!-- ============================================================
     DASHBOARD (authenticated)
============================================================ -->

<!-- Top bar -->
<header class="sticky top-0 z-50 bg-gray-900 border-b border-gray-800 flex items-center justify-between px-6 h-14 shadow-sm">
  <div class="flex items-center gap-3">
    <div class="w-7 h-7 rounded-lg bg-play-green flex items-center justify-center">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
      </svg>
    </div>
    <span class="font-semibold text-white">Play Store Manager</span>
  </div>
  <div class="flex items-center gap-3">
    <a href="index.php" target="_blank" class="text-xs text-play-green hover:underline">View Page ↗</a>
    <a href="admin.php?logout=1" class="text-xs text-gray-400 hover:text-white transition">Logout</a>
  </div>
</header>

<main class="max-w-2xl mx-auto px-4 py-8">

  <!-- Status messages -->
  <?php if ($saveMessage): ?>
  <div class="bg-green-900/50 border border-green-700 text-green-300 text-sm rounded-lg px-4 py-3 mb-6 flex items-center gap-2">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
    </svg>
    <?= esc($saveMessage) ?>
  </div>
  <?php endif; ?>

  <?php if ($saveError): ?>
  <div class="bg-red-900/50 border border-red-700 text-red-300 text-sm rounded-lg px-4 py-3 mb-6">
    <?= esc($saveError) ?>
  </div>
  <?php endif; ?>

  <!-- Edit Form -->
  <div class="bg-gray-900 rounded-2xl shadow-xl overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-800">
      <h2 class="font-semibold text-white">App Settings</h2>
      <p class="text-xs text-gray-400 mt-0.5">Edit the app listing details below</p>
    </div>

    <form method="POST" action="admin.php" class="px-6 py-6 space-y-5">

      <!-- App Title -->
      <div>
        <label class="block text-sm font-medium text-gray-300 mb-1.5">App Title</label>
        <input
          type="text"
          name="app_title"
          value="<?= esc($data['app_title'] ?? '') ?>"
          required
          class="w-full bg-gray-800 border border-gray-700 text-white placeholder-gray-500 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-play-green focus:ring-1 focus:ring-play-green transition"
        />
      </div>

      <!-- Developer Name -->
      <div>
        <label class="block text-sm font-medium text-gray-300 mb-1.5">Developer Name</label>
        <input
          type="text"
          name="dev_name"
          value="<?= esc($data['dev_name'] ?? '') ?>"
          class="w-full bg-gray-800 border border-gray-700 text-white placeholder-gray-500 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-play-green focus:ring-1 focus:ring-play-green transition"
        />
      </div>

      <!-- Domain & Category (row) -->
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-300 mb-1.5">Domain</label>
          <input
            type="text"
            name="domain"
            value="<?= esc($data['domain'] ?? '') ?>"
            placeholder="example.com"
            class="w-full bg-gray-800 border border-gray-700 text-white placeholder-gray-500 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-play-green focus:ring-1 focus:ring-play-green transition"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-300 mb-1.5">Category</label>
          <input
            type="text"
            name="category"
            value="<?= esc($data['category'] ?? '') ?>"
            placeholder="Dating"
            class="w-full bg-gray-800 border border-gray-700 text-white placeholder-gray-500 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-play-green focus:ring-1 focus:ring-play-green transition"
          />
        </div>
      </div>

      <!-- Description -->
      <div>
        <label class="block text-sm font-medium text-gray-300 mb-1.5">App Description</label>
        <textarea
          name="description"
          rows="3"
          placeholder="Short description shown on the app page."
          class="w-full bg-gray-800 border border-gray-700 text-white placeholder-gray-500 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-play-green focus:ring-1 focus:ring-play-green transition resize-y"
        ><?= esc($data['description'] ?? '') ?></textarea>
      </div>

      <!-- Rating & Reviews (row) -->
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-300 mb-1.5">Rating <span class="text-gray-500 font-normal">(e.g. 4.5)</span></label>
          <input
            type="text"
            name="rating"
            value="<?= esc($data['rating'] ?? '4.5') ?>"
            placeholder="4.5"
            class="w-full bg-gray-800 border border-gray-700 text-white placeholder-gray-500 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-play-green focus:ring-1 focus:ring-play-green transition"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-300 mb-1.5">Reviews <span class="text-gray-500 font-normal">(e.g. 1.2M)</span></label>
          <input
            type="text"
            name="reviews"
            value="<?= esc($data['reviews'] ?? '') ?>"
            placeholder="1.2M"
            class="w-full bg-gray-800 border border-gray-700 text-white placeholder-gray-500 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-play-green focus:ring-1 focus:ring-play-green transition"
          />
        </div>
      </div>

      <!-- Downloads -->
      <div>
        <label class="block text-sm font-medium text-gray-300 mb-1.5">Downloads <span class="text-gray-500 font-normal">(e.g. 5Cr+)</span></label>
        <input
          type="text"
          name="downloads"
          value="<?= esc($data['downloads'] ?? '') ?>"
          placeholder="5Cr+"
          class="w-full bg-gray-800 border border-gray-700 text-white placeholder-gray-500 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-play-green focus:ring-1 focus:ring-play-green transition"
        />
      </div>

      <!-- App Icon URL -->
      <div>
        <label class="block text-sm font-medium text-gray-300 mb-1.5">App Icon URL</label>
        <input
          type="url"
          name="app_icon"
          value="<?= esc($data['app_icon'] ?? '') ?>"
          placeholder="https://example.com/icon.png"
          class="w-full bg-gray-800 border border-gray-700 text-white placeholder-gray-500 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-play-green focus:ring-1 focus:ring-play-green transition"
        />
      </div>

      <!-- APK Link -->
      <div>
        <label class="block text-sm font-medium text-gray-300 mb-1.5">
          APK / Install Link
          <span class="text-gray-500 font-normal text-xs ml-1">— used by the Install button</span>
        </label>
        <input
          type="url"
          name="apk_link"
          value="<?= esc($data['apk_link'] ?? '') ?>"
          placeholder="https://lovebloom.live/app/lovebloom.apk"
          required
          class="w-full bg-gray-800 border border-gray-700 text-white placeholder-gray-500 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-play-green focus:ring-1 focus:ring-play-green transition"
        />
      </div>

      <!-- Screenshot URLs -->
      <div>
        <label class="block text-sm font-medium text-gray-300 mb-1.5">
          Screenshot URLs
          <span class="text-gray-500 font-normal text-xs ml-1">— one URL per line</span>
        </label>
        <textarea
          name="screenshot_urls"
          rows="6"
          placeholder="https://example.com/ss1.jpg&#10;https://example.com/ss2.jpg"
          class="w-full bg-gray-800 border border-gray-700 text-white placeholder-gray-500 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-play-green focus:ring-1 focus:ring-play-green transition resize-y font-mono text-xs"
        ><?= esc(implode("\n", $data['screenshot_urls'] ?? [])) ?></textarea>
      </div>

      <!-- Submit -->
      <div class="pt-2 flex gap-3">
        <button
          type="submit"
          name="save"
          value="1"
          class="flex-1 bg-play-green hover:bg-green-700 active:bg-green-800 text-white font-semibold py-2.5 rounded-lg transition text-sm"
        >
          Save Changes
        </button>
        <a
          href="admin.php"
          class="px-5 py-2.5 bg-gray-800 hover:bg-gray-700 text-gray-300 font-medium rounded-lg transition text-sm text-center"
        >
          Reset
        </a>
      </div>

    </form>
  </div>

  <!-- Quick info -->
  <p class="text-xs text-gray-600 text-center mt-6">
    Data stored in <code class="bg-gray-800 px-1 rounded text-gray-400">database.json</code> &mdash; no database required.
  </p>

</main>

<?php endif; ?>

</body>
</html>
