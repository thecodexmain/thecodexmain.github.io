<?php
// Read data from database.json
$dataFile = __DIR__ . '/database.json';
if (!file_exists($dataFile)) {
    die('Configuration error: database.json not found.');
}
$rawJson = file_get_contents($dataFile);
$data = json_decode($rawJson, true);
if (!is_array($data)) {
    die('Configuration error: database.json is invalid.');
}

$app_title      = htmlspecialchars($data['app_title']      ?? 'App');
$dev_name       = htmlspecialchars($data['dev_name']       ?? 'Developer');
$description    = htmlspecialchars($data['description']    ?? '');
$category       = htmlspecialchars($data['category']       ?? 'App');
$domain         = htmlspecialchars($data['domain']         ?? $_SERVER['HTTP_HOST'] ?? '');
$rating         = htmlspecialchars($data['rating']         ?? '4.5');
$reviews        = htmlspecialchars($data['reviews']        ?? '0');
$downloads      = htmlspecialchars($data['downloads']      ?? '0');
$app_icon       = htmlspecialchars($data['app_icon']       ?? '');
$apk_link       = htmlspecialchars($data['apk_link']       ?? '#');
$screenshots    = $data['screenshot_urls'] ?? [];
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
  <title><?= $app_title ?> - Apps on Google Play</title>
  <meta name="description" content="Download <?= $app_title ?> from Google Play." />

  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            'play-green':  '#01875f',
            'play-gray':   '#9aa0a6',
            'play-divider':'#3c4043',
            'play-dark':   '#111111',
            'play-card':   '#1c1c1e',
          },
          borderRadius: {
            'squircle': '22%',
          }
        }
      }
    };
  </script>

  <style>
    /* Smooth horizontal scroll for screenshot gallery */
    .gallery-scroll {
      scroll-snap-type: x mandatory;
      -webkit-overflow-scrolling: touch;
    }
    .gallery-scroll::-webkit-scrollbar { display: none; }
    .gallery-item {
      scroll-snap-align: start;
      flex-shrink: 0;
    }

    /* Squircle icon shape */
    .squircle {
      border-radius: 22%;
    }

    /* Rating star fill */
    .star-icon {
      color: #01875f;
    }
  </style>
</head>
<body class="bg-black text-white min-h-screen font-sans antialiased">

  <!-- ========== STICKY HEADER ========== -->
  <header class="sticky top-0 z-50 bg-white flex items-center justify-between px-4 h-14 shadow-sm">
    <!-- Left: X / Back icon -->
    <button aria-label="Close" class="p-1 -ml-1 text-gray-700 hover:bg-gray-100 rounded-full transition">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
      </svg>
    </button>

    <!-- Center: domain name -->
    <span class="text-[14px] font-medium text-gray-500 tracking-wide select-none"><?= $domain ?></span>

    <!-- Right: three-dot menu -->
    <button aria-label="More options" class="p-1 -mr-1 text-gray-700 hover:bg-gray-100 rounded-full transition">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
        <circle cx="12" cy="5"  r="1.5"/>
        <circle cx="12" cy="12" r="1.5"/>
        <circle cx="12" cy="19" r="1.5"/>
      </svg>
    </button>
  </header>

  <!-- ========== GOOGLE PLAY BRANDING ========== -->
  <div class="bg-black flex items-center gap-3 px-4 py-3 border-b border-[#2a2a2a]">
    <!-- Google Play Color Triangle Logo (SVG recreation) -->
    <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" viewBox="0 0 512 512">
      <defs>
        <linearGradient id="g1" x1="0%" y1="0%" x2="100%" y2="0%">
          <stop offset="0%" style="stop-color:#00c4ff"/>
          <stop offset="100%" style="stop-color:#00d5ff"/>
        </linearGradient>
        <linearGradient id="g2" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" style="stop-color:#ff3a44"/>
          <stop offset="100%" style="stop-color:#c31162"/>
        </linearGradient>
        <linearGradient id="g3" x1="0%" y1="0%" x2="100%" y2="0%">
          <stop offset="0%" style="stop-color:#ffd600"/>
          <stop offset="100%" style="stop-color:#ff9d00"/>
        </linearGradient>
        <linearGradient id="g4" x1="0%" y1="0%" x2="0%" y2="100%">
          <stop offset="0%" style="stop-color:#32df76"/>
          <stop offset="100%" style="stop-color:#00b057"/>
        </linearGradient>
      </defs>
      <!-- Top-left (cyan) -->
      <path d="M60 30 L256 256 L60 256 Z" fill="url(#g1)"/>
      <!-- Top-right (red) -->
      <path d="M60 30 L452 256 L256 256 Z" fill="url(#g2)"/>
      <!-- Bottom-right (yellow) -->
      <path d="M256 256 L452 256 L60 482 Z" fill="url(#g3)"/>
      <!-- Bottom-left (green) -->
      <path d="M60 256 L256 256 L60 482 Z" fill="url(#g4)"/>
    </svg>
    <span class="text-white font-medium" style="font-size:18px; letter-spacing:0.01em;">Google Play</span>
  </div>

  <!-- ========== MAIN CONTENT ========== -->
  <main class="max-w-lg mx-auto pb-10">

    <!-- ---- HERO IDENTITY SECTION ---- -->
    <section class="flex items-start gap-4 px-4 pt-5 pb-4">
      <!-- App Icon (squircle) -->
      <div class="relative flex-shrink-0">
        <?php if ($app_icon): ?>
          <img
            src="<?= $app_icon ?>"
            alt="<?= $app_title ?> icon"
            id="app-icon-img"
            class="w-24 h-24 squircle object-cover ring-1 ring-white/10"
            onerror="document.getElementById('app-icon-img').style.display='none'; document.getElementById('app-icon-fallback').style.display='flex';"
          />
        <?php endif; ?>
        <!-- Fallback gradient icon -->
        <div
          id="app-icon-fallback"
          class="w-24 h-24 squircle bg-gradient-to-br from-pink-500 to-red-600 items-center justify-center"
          style="display:<?= $app_icon ? 'none' : 'flex' ?>;"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 text-white" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
          </svg>
        </div>
      </div>

      <!-- App Identity -->
      <div class="flex flex-col justify-center gap-1 pt-1">
        <h1 class="text-2xl font-bold text-white leading-tight"><?= $app_title ?></h1>
        <p class="text-sm font-medium text-play-green"><?= $dev_name ?></p>
        <!-- Badges -->
        <div class="flex items-center gap-2 mt-1">
          <span class="text-xs text-play-gray border border-play-divider rounded px-2 py-0.5"><?= $category ?></span>
        </div>
      </div>
    </section>

    <!-- ---- METRICS BAR ---- -->
    <section class="flex items-center justify-around px-4 py-3 border-t border-b border-[#2a2a2a] mx-4 rounded-lg">
      <!-- Rating -->
      <div class="flex flex-col items-center gap-0.5">
        <div class="flex items-center gap-0.5">
          <span class="text-sm font-semibold text-white"><?= $rating ?></span>
          <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 star-icon" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
          </svg>
        </div>
        <span class="text-[11px] text-play-gray">Reviews</span>
      </div>

      <!-- Divider -->
      <div class="w-px h-8 bg-play-divider"></div>

      <!-- Downloads -->
      <div class="flex flex-col items-center gap-0.5">
        <span class="text-sm font-semibold text-white"><?= $downloads ?></span>
        <span class="text-[11px] text-play-gray">Downloads</span>
      </div>

      <!-- Divider -->
      <div class="w-px h-8 bg-play-divider"></div>

      <!-- Age Rating -->
      <div class="flex flex-col items-center gap-0.5">
        <div class="border border-play-divider rounded px-1.5 py-0.5 text-xs font-semibold text-white">3+</div>
        <span class="text-[11px] text-play-gray">Rated for 3+</span>
      </div>
    </section>

    <!-- ---- INSTALL BUTTON ---- -->
    <section class="px-4 pt-4">
      <a
        href="<?= $apk_link ?>"
        id="install-btn"
        class="block w-full bg-play-green text-white text-base font-bold text-center py-3 rounded-lg active:bg-green-800 transition-colors select-none"
        style="border-radius: 8px;"
      >
        Install
      </a>
    </section>

    <!-- ---- ADDITIONAL ACTIONS ---- -->
    <section class="flex items-center justify-around px-4 pt-4 pb-2">
      <button class="flex flex-col items-center gap-1 text-play-green hover:opacity-80 transition">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
        </svg>
        <span class="text-[11px]">Wishlist</span>
      </button>
      <button class="flex flex-col items-center gap-1 text-play-green hover:opacity-80 transition">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
        </svg>
        <span class="text-[11px]">Share</span>
      </button>
      <button class="flex flex-col items-center gap-1 text-play-green hover:opacity-80 transition">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/>
        </svg>
        <span class="text-[11px]">Flag</span>
      </button>
    </section>

    <!-- ---- SCREENSHOT GALLERY ---- -->
    <?php if (!empty($screenshots)): ?>
    <section class="pt-5">
      <div class="flex gap-3 px-4 overflow-x-auto gallery-scroll pb-2">
        <?php foreach ($screenshots as $i => $url): ?>
          <div class="gallery-item" style="width: 70vw; max-width: 280px;">
            <div class="relative" style="aspect-ratio: 9/16;">
              <img
                src="<?= htmlspecialchars($url) ?>"
                alt="Screenshot <?= (int)($i + 1) ?>"
                class="w-full h-full object-cover rounded-xl"
                loading="lazy"
                onerror="this.style.display='none';"
              />
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <!-- ---- APP DESCRIPTION ---- -->
    <section class="px-4 pt-6">
      <h2 class="text-base font-semibold text-white mb-2">About this app</h2>
      <p class="text-sm text-play-gray leading-relaxed line-clamp-3" id="desc-short">
        <?= $description ?: 'No description available.' ?>
      </p>
      <button
        onclick="var el=document.getElementById('desc-short'); var expanded=el.classList.toggle('line-clamp-3'); this.textContent = expanded ? 'more' : 'less';"
        class="text-sm text-play-green mt-1 focus:outline-none"
      >more</button>
    </section>

    <!-- ---- RATINGS OVERVIEW ---- -->
    <section class="px-4 pt-6">
      <h2 class="text-base font-semibold text-white mb-3">Ratings and reviews</h2>
      <div class="flex items-center gap-6">
        <div class="flex flex-col items-center">
          <span class="text-5xl font-light text-white"><?= $rating ?></span>
          <div class="flex gap-0.5 mt-1">
            <?php
            $ratingNum = (float)($data['rating'] ?? 0);
            for ($s = 1; $s <= 5; $s++) {
                $fill = ($s <= round($ratingNum)) ? '#01875f' : '#3c4043';
                echo '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="' . $fill . '"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>';
            }
            ?>
          </div>
          <span class="text-xs text-play-gray mt-1"><?= $reviews ?> reviews</span>
        </div>
        <!-- Bar chart -->
        <div class="flex flex-col gap-1 flex-1">
          <?php
          $bars = [5 => 78, 4 => 12, 3 => 5, 2 => 2, 1 => 3];
          foreach ($bars as $star => $pct):
          ?>
          <div class="flex items-center gap-2">
            <span class="text-[11px] text-play-gray w-2"><?= $star ?></span>
            <div class="flex-1 h-1.5 bg-play-divider rounded-full overflow-hidden">
              <div class="h-full bg-play-green rounded-full" style="width:<?= $bars[$star] ?>%"></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- ---- DEVELOPER INFO ---- -->
    <section class="px-4 pt-6 pb-4 border-t border-[#2a2a2a] mt-6">
      <h2 class="text-base font-semibold text-white mb-2">Developer contact</h2>
      <div class="flex items-center gap-3 text-sm text-play-gray">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-play-gray flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
        </svg>
        <span><?= $domain ?></span>
      </div>
    </section>

  </main>

  <!-- ========== BOTTOM STICKY INSTALL BAR ========== -->
  <div class="fixed bottom-0 left-0 right-0 z-40 bg-black border-t border-[#2a2a2a] px-4 py-3 flex items-center gap-3 max-w-lg mx-auto" style="left:50%;transform:translateX(-50%);width:100%;max-width:512px;">
    <!-- Small icon -->
    <div class="w-10 h-10 squircle bg-gradient-to-br from-pink-500 to-red-600 flex-shrink-0 flex items-center justify-center">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
      </svg>
    </div>
    <div class="flex flex-col flex-1 min-w-0">
      <span class="text-sm font-medium text-white truncate"><?= $app_title ?></span>
      <span class="text-xs text-play-gray">Free</span>
    </div>
    <a
      href="<?= $apk_link ?>"
      class="bg-play-green text-white text-sm font-bold px-6 py-2 rounded-lg active:bg-green-800 transition-colors flex-shrink-0"
      style="border-radius:8px;"
    >Install</a>
  </div>

  <!-- Spacer for bottom bar -->
  <div class="h-20"></div>

</body>
</html>
