<?php
/**
 * index.php – Visually-attractive, mobile-first landing page.
 * All content is driven by /data/settings.json.
 * Supports: click tracking via ?action=click (AJAX-safe).
 */

require_once __DIR__ . '/config.php';

// ── AJAX click tracking ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'click') {
    log_click();
    header('Content-Type: application/json');
    echo '{"ok":true}';
    exit;
}

$s = read_settings();

// Resolve asset URLs (relative to the document root)
$videoSrc    = esc($s['video']);
$fallbackSrc = esc($s['fallback_image']);
$headline    = esc($s['headline']);
$subheadline = esc($s['subheadline']);   // emoji passthrough handled by htmlspecialchars
$dlLink      = esc($s['download_link']);
$btnPrimary  = esc($s['btn_primary']);
$btnSecondary = esc($s['btn_secondary']);
$badgeText   = esc($s['badge_text']);
$countdown   = (int) $s['countdown'];
$slots       = (int) $s['slots'];
$animationsEnabled = (bool) $s['animations'];
$animClass   = $animationsEnabled ? 'anim' : 'no-anim';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
  <title><?= $headline ?></title>
  <meta name="description" content="<?= $subheadline ?>" />

  <!-- Google Fonts: Poppins -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800;900&display=swap" rel="stylesheet" />

  <style>
    /* ═══════════════════════════════════════════
       RESET & BASE
    ═══════════════════════════════════════════ */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --c-bg:        #0a0a12;
      --c-glass:     rgba(255,255,255,0.07);
      --c-glass-brd: rgba(255,255,255,0.15);
      --c-text:      #f0f0ff;
      --c-muted:     rgba(240,240,255,0.65);
      --grad-btn:    linear-gradient(135deg,#f72585,#7209b7,#f77f00);
      --grad-text:   linear-gradient(90deg,#f72585,#b5179e,#7209b7,#4cc9f0);
      --glow-pink:   0 0 22px rgba(247,37,133,0.7),0 0 50px rgba(114,9,183,0.4);
      --glow-blue:   0 0 22px rgba(76,201,240,0.6);
      --radius:      20px;
      --font:        'Poppins', sans-serif;
    }
    html { scroll-behavior: smooth; -webkit-font-smoothing: antialiased; }
    body {
      font-family: var(--font);
      background: var(--c-bg);
      color: var(--c-text);
      min-height: 100dvh;
      overflow-x: hidden;
    }

    /* ═══════════════════════════════════════════
       VIDEO BACKGROUND
    ═══════════════════════════════════════════ */
    .video-bg {
      position: fixed; inset: 0;
      z-index: 0;
      overflow: hidden;
    }
    .video-bg video {
      width: 100%; height: 100%;
      object-fit: cover;
      transform-origin: center;
    }
    .anim .video-bg video { animation: videoZoom 20s ease-in-out infinite alternate; }

    @keyframes videoZoom {
      from { transform: scale(1); }
      to   { transform: scale(1.08); }
    }

    /* ── Gradient overlay + blur ── */
    .video-bg::after {
      content: '';
      position: absolute; inset: 0;
      background: linear-gradient(
        to bottom,
        rgba(10,10,18,0.55) 0%,
        rgba(10,10,18,0.72) 50%,
        rgba(10,10,18,0.85) 100%
      );
      backdrop-filter: blur(2px);
      -webkit-backdrop-filter: blur(2px);
    }

    /* ═══════════════════════════════════════════
       PAGE LAYOUT
    ═══════════════════════════════════════════ */
    .page-wrapper {
      position: relative; z-index: 1;
      min-height: 100dvh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px 16px;
    }

    /* ═══════════════════════════════════════════
       GLASS CARD
    ═══════════════════════════════════════════ */
    .glass-card {
      background: var(--c-glass);
      border: 1px solid var(--c-glass-brd);
      border-radius: var(--radius);
      backdrop-filter: blur(18px) saturate(160%);
      -webkit-backdrop-filter: blur(18px) saturate(160%);
      padding: clamp(28px, 6vw, 56px) clamp(20px, 5vw, 48px);
      max-width: 520px;
      width: 100%;
      text-align: center;
      box-shadow: 0 8px 48px rgba(0,0,0,0.55), inset 0 1px 0 rgba(255,255,255,0.1);
    }
    .anim .glass-card { animation: fadeUpCard 0.8s cubic-bezier(0.16,1,0.3,1) both; }

    @keyframes fadeUpCard {
      from { opacity: 0; transform: translateY(40px) scale(0.97); }
      to   { opacity: 1; transform: translateY(0)    scale(1);    }
    }

    /* ═══════════════════════════════════════════
       BADGE
    ═══════════════════════════════════════════ */
    .badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: rgba(247,37,133,0.18);
      border: 1px solid rgba(247,37,133,0.4);
      color: #ff8fab;
      font-size: 0.72rem;
      font-weight: 600;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      padding: 5px 14px;
      border-radius: 999px;
      margin-bottom: 20px;
    }
    .badge-dot {
      width: 7px; height: 7px;
      border-radius: 50%;
      background: #f72585;
    }
    .anim .badge-dot { animation: pulseDot 1.4s ease-in-out infinite; }
    .anim .badge     { animation: floatBadge 3s ease-in-out infinite; }

    @keyframes pulseDot {
      0%,100% { transform: scale(1);   box-shadow: 0 0 0   4px rgba(247,37,133,0.3); }
      50%      { transform: scale(1.3); box-shadow: 0 0 0   8px rgba(247,37,133,0.0); }
    }
    @keyframes floatBadge {
      0%,100% { transform: translateY(0); }
      50%     { transform: translateY(-5px); }
    }

    /* ═══════════════════════════════════════════
       HEADLINE
    ═══════════════════════════════════════════ */
    .headline {
      font-size: clamp(1.9rem, 6vw, 3rem);
      font-weight: 900;
      line-height: 1.15;
      letter-spacing: -0.02em;
      background: var(--grad-text);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 14px;
    }
    .anim .headline { animation: fadeUp 0.9s 0.15s cubic-bezier(0.16,1,0.3,1) both; }

    /* ═══════════════════════════════════════════
       SUBHEADLINE
    ═══════════════════════════════════════════ */
    .subheadline {
      font-size: clamp(0.95rem, 3vw, 1.15rem);
      font-weight: 400;
      color: var(--c-muted);
      line-height: 1.65;
      margin-bottom: 28px;
    }
    .anim .subheadline { animation: fadeUp 0.9s 0.3s cubic-bezier(0.16,1,0.3,1) both; }

    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(22px); }
      to   { opacity: 1; transform: translateY(0);    }
    }

    /* ═══════════════════════════════════════════
       SLOTS
    ═══════════════════════════════════════════ */
    .slots-text {
      font-size: 0.78rem;
      color: var(--c-muted);
      margin-bottom: 26px;
    }
    .slots-count {
      font-weight: 700;
      color: #4cc9f0;
    }

    /* ═══════════════════════════════════════════
       COUNTDOWN TIMER
    ═══════════════════════════════════════════ */
    .countdown-wrap {
      margin-bottom: 30px;
    }
    .countdown-label {
      font-size: 0.7rem;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: var(--c-muted);
      margin-bottom: 10px;
    }
    .countdown-grid {
      display: flex;
      justify-content: center;
      gap: 10px;
    }
    .countdown-unit {
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    .countdown-num {
      background: rgba(255,255,255,0.06);
      border: 1px solid rgba(255,255,255,0.12);
      border-radius: 10px;
      padding: 10px 14px;
      min-width: 52px;
      font-size: 1.6rem;
      font-weight: 700;
      line-height: 1;
      color: #fff;
      transition: transform 0.18s ease, color 0.18s ease;
    }
    .countdown-unit-label {
      font-size: 0.6rem;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: var(--c-muted);
      margin-top: 4px;
    }
    .flip { animation: numFlip 0.22s ease both; }
    @keyframes numFlip {
      0%   { transform: scaleY(0.5); opacity: 0; }
      100% { transform: scaleY(1);   opacity: 1; }
    }

    /* ═══════════════════════════════════════════
       BUTTONS
    ═══════════════════════════════════════════ */
    .btn-group {
      display: flex;
      flex-direction: column;
      gap: 14px;
    }

    /* Base button */
    .btn {
      position: relative;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      font-family: var(--font);
      font-size: 1rem;
      font-weight: 700;
      border: none;
      border-radius: 999px;
      padding: 15px 32px;
      cursor: pointer;
      text-decoration: none;
      overflow: hidden;
      transition: transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
      -webkit-tap-highlight-color: transparent;
      user-select: none;
    }
    .btn:active { transform: scale(0.96) !important; }

    /* Primary */
    .btn-primary {
      background: var(--grad-btn);
      background-size: 200% 200%;
      color: #fff;
      box-shadow: var(--glow-pink);
    }
    .anim .btn-primary { animation: fadeUp 0.9s 0.5s cubic-bezier(0.16,1,0.3,1) both, pulseCta 2.5s 1.5s ease-in-out infinite; }
    .btn-primary:hover:not(:disabled) {
      transform: scale(1.04);
      box-shadow: 0 0 32px rgba(247,37,133,0.9), 0 0 70px rgba(114,9,183,0.5);
      background-position: 100% 100%;
    }

    /* Primary locked state */
    .btn-primary.locked {
      background: linear-gradient(135deg,#555,#333);
      box-shadow: none;
      cursor: not-allowed;
      opacity: 0.55;
      animation: none !important;
    }
    .btn-primary.locked::before {
      content: '🔒 ';
    }

    /* Secondary */
    .btn-secondary {
      background: transparent;
      color: var(--c-text);
      border: 1.5px solid var(--c-glass-brd);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
    }
    .anim .btn-secondary { animation: fadeUp 0.9s 0.65s cubic-bezier(0.16,1,0.3,1) both; }
    .btn-secondary:hover {
      transform: scale(1.04);
      border-color: rgba(76,201,240,0.7);
      box-shadow: var(--glow-blue);
      color: #4cc9f0;
    }

    @keyframes pulseCta {
      0%,100% { box-shadow: var(--glow-pink); }
      50%      { box-shadow: 0 0 40px rgba(247,37,133,0.9), 0 0 80px rgba(114,9,183,0.6); }
    }

    /* Ripple */
    .ripple {
      position: absolute;
      border-radius: 50%;
      background: rgba(255,255,255,0.35);
      transform: scale(0);
      animation: rippleAnim 0.55s linear;
      pointer-events: none;
    }
    @keyframes rippleAnim {
      to { transform: scale(4); opacity: 0; }
    }

    /* ═══════════════════════════════════════════
       PARTICLES (lightweight canvas)
    ═══════════════════════════════════════════ */
    #particles {
      position: fixed; inset: 0;
      z-index: 0;
      pointer-events: none;
    }

    /* ═══════════════════════════════════════════
       RESPONSIVE
    ═══════════════════════════════════════════ */
    @media (min-width: 480px) {
      .btn-group { flex-direction: row; justify-content: center; }
    }
  </style>
</head>
<body class="<?= $animClass ?>">

<!-- Particle canvas -->
<?php if ($animationsEnabled): ?>
<canvas id="particles"></canvas>
<?php endif; ?>

<!-- ── Video background ── -->
<div class="video-bg">
  <video autoplay muted loop playsinline
         poster="<?= $fallbackSrc ?>">
    <source src="<?= $videoSrc ?>" type="video/mp4" />
  </video>
</div>

<!-- ── Main content ── -->
<main class="page-wrapper">
  <div class="glass-card">

    <!-- Badge -->
    <div class="badge">
      <span class="badge-dot"></span>
      <?= $badgeText ?>
    </div>

    <!-- Headline -->
    <h1 class="headline"><?= $headline ?></h1>

    <!-- Subheadline -->
    <p class="subheadline"><?= $subheadline ?></p>

    <!-- Slots -->
    <p class="slots-text">
      Only <span class="slots-count" id="slotsCount"><?= $slots ?></span> slots remaining
    </p>

    <!-- Countdown -->
    <div class="countdown-wrap" id="countdownWrap">
      <p class="countdown-label">Unlocks in</p>
      <div class="countdown-grid">
        <div class="countdown-unit">
          <span class="countdown-num" id="cdHours">00</span>
          <span class="countdown-unit-label">Hrs</span>
        </div>
        <div class="countdown-unit">
          <span class="countdown-num" id="cdMins">00</span>
          <span class="countdown-unit-label">Min</span>
        </div>
        <div class="countdown-unit">
          <span class="countdown-num" id="cdSecs">00</span>
          <span class="countdown-unit-label">Sec</span>
        </div>
      </div>
    </div>

    <!-- Buttons -->
    <div class="btn-group">
      <a href="<?= $dlLink ?>" id="btnPrimary"
         class="btn btn-primary locked"
         aria-label="<?= $btnPrimary ?>"
         onclick="trackClick(event)">
        <?= $btnPrimary ?>
      </a>
      <a href="#" id="btnSecondary"
         class="btn btn-secondary"
         aria-label="<?= $btnSecondary ?>">
        <?= $btnSecondary ?>
      </a>
    </div>

  </div>
</main>

<script>
/* ══════════════════════════════════════════════
   CONFIG (injected from PHP)
══════════════════════════════════════════════ */
const COUNTDOWN_SECS  = <?= $countdown ?>;
const ANIM_ENABLED    = <?= $animationsEnabled ? 'true' : 'false' ?>;

/* ══════════════════════════════════════════════
   COUNTDOWN
══════════════════════════════════════════════ */
(function () {
  const key    = 'codex_cd_end';
  const stored = localStorage.getItem(key);
  const endTime = stored ? parseInt(stored, 10) : Date.now() + COUNTDOWN_SECS * 1000;
  if (!stored) localStorage.setItem(key, endTime);

  const elH = document.getElementById('cdHours');
  const elM = document.getElementById('cdMins');
  const elS = document.getElementById('cdSecs');
  const btn = document.getElementById('btnPrimary');
  const wrap = document.getElementById('countdownWrap');

  function pad(n) { return String(n).padStart(2, '0'); }

  function setNum(el, val) {
    const s = pad(val);
    if (el.textContent !== s) {
      el.classList.remove('flip');
      void el.offsetWidth; // reflow
      el.textContent = s;
      if (ANIM_ENABLED) el.classList.add('flip');
    }
  }

  function tick() {
    const remaining = Math.max(0, Math.floor((endTime - Date.now()) / 1000));
    const h = Math.floor(remaining / 3600);
    const m = Math.floor((remaining % 3600) / 60);
    const s = remaining % 60;
    setNum(elH, h);
    setNum(elM, m);
    setNum(elS, s);

    if (remaining <= 0) {
      // Unlock primary button
      btn.classList.remove('locked');
      btn.removeAttribute('disabled');
      wrap.style.display = 'none';
      localStorage.removeItem(key);
      clearInterval(timer);
    }
  }

  tick();
  const timer = setInterval(tick, 1000);
})();

/* ══════════════════════════════════════════════
   RIPPLE EFFECT
══════════════════════════════════════════════ */
document.querySelectorAll('.btn').forEach(btn => {
  btn.addEventListener('click', function (e) {
    const rect   = this.getBoundingClientRect();
    const size   = Math.max(rect.width, rect.height);
    const x      = e.clientX - rect.left - size / 2;
    const y      = e.clientY - rect.top  - size / 2;
    const ripple = document.createElement('span');
    ripple.classList.add('ripple');
    ripple.style.cssText = `width:${size}px;height:${size}px;left:${x}px;top:${y}px`;
    this.appendChild(ripple);
    ripple.addEventListener('animationend', () => ripple.remove());
  });
});

/* ══════════════════════════════════════════════
   HAPTIC FEEDBACK (mobile)
══════════════════════════════════════════════ */
document.querySelectorAll('.btn').forEach(btn => {
  btn.addEventListener('click', () => {
    if (navigator.vibrate) navigator.vibrate(30);
  });
});

/* ══════════════════════════════════════════════
   CLICK TRACKING
══════════════════════════════════════════════ */
function trackClick(e) {
  const btn = document.getElementById('btnPrimary');
  if (btn.classList.contains('locked')) { e.preventDefault(); return; }
  fetch('index.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'action=click',
    keepalive: true,
  }).catch(() => {});
}

/* ══════════════════════════════════════════════
   LIGHTWEIGHT PARTICLE CANVAS
══════════════════════════════════════════════ */
if (ANIM_ENABLED) {
  (function () {
    const canvas = document.getElementById('particles');
    if (!canvas) return;
    const ctx    = canvas.getContext('2d');
    let W, H, particles;

    function resize() {
      W = canvas.width  = window.innerWidth;
      H = canvas.height = window.innerHeight;
    }

    function make() {
      return {
        x: Math.random() * W,
        y: Math.random() * H,
        r: Math.random() * 1.6 + 0.4,
        dx: (Math.random() - 0.5) * 0.35,
        dy: -(Math.random() * 0.5 + 0.1),
        a: Math.random() * 0.6 + 0.2,
      };
    }

    function init() {
      resize();
      particles = Array.from({ length: 70 }, make);
    }

    function draw() {
      ctx.clearRect(0, 0, W, H);
      particles.forEach(p => {
        ctx.beginPath();
        ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
        ctx.fillStyle = `rgba(200,180,255,${p.a})`;
        ctx.fill();
        p.x += p.dx;
        p.y += p.dy;
        if (p.y < -4) { Object.assign(p, make(), { y: H + 4 }); }
        if (p.x < -4 || p.x > W + 4) p.dx *= -1;
      });
      requestAnimationFrame(draw);
    }

    window.addEventListener('resize', resize);
    init();
    draw();
  })();
}
</script>
</body>
</html>
