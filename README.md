# Landing Page + Admin Panel

A visually-attractive, mobile-first landing page with a PHP/JSON admin panel.
Designed for cPanel shared hosting — **no MySQL, no Node.js required**.

---

## Folder Structure

```
public_html/
├── index.php           ← Landing page
├── config.php          ← Credentials + helpers
├── admin/
│   ├── login.php       ← Admin login
│   └── dashboard.php   ← Admin dashboard
├── uploads/            ← Video / image assets  (must be writable)
└── data/
    ├── settings.json   ← All page settings      (must be writable)
    ├── clicks.json     ← CTA click tracking     (auto-created)
    └── .htaccess       ← Blocks direct HTTP access to /data
```

---

## Quick Start (cPanel)

1. **Upload** all files to `public_html/` via cPanel File Manager or FTP.

2. **Set permissions** (via File Manager → right-click → Permissions):

   | Path         | Permission |
   |--------------|------------|
   | `uploads/`   | `755`      |
   | `data/`      | `755`      |
   | `data/settings.json` | `644` |

3. **Change the admin password** before going live:

   ```bash
   # Run in cPanel Terminal or SSH
   php -r "echo password_hash('YourStrongPassword', PASSWORD_DEFAULT);"
   ```

   Paste the output hash into `config.php` as `ADMIN_PASSWORD_HASH`.
   Also change `SESSION_SECRET` to a random 32-character string.

4. **Upload your assets** via the admin panel:
   - Browse to `https://yourdomain.com/admin/login.php`
   - Default credentials: `admin` / `admin123`
   - Upload background video (MP4) and fallback image (JPG/PNG)
   - Fill in headline, subheadline, buttons, countdown, slots
   - Hit **Save Settings**

---

## Admin Panel

| URL | Purpose |
|-----|---------|
| `/admin/login.php` | Admin login |
| `/admin/dashboard.php` | Settings + file upload + live preview |
| `?logout=1` | Log out |

---

## Security Notes

- Change `ADMIN_PASSWORD_HASH` and `SESSION_SECRET` in `config.php` immediately.
- The `/data/` directory is protected from direct HTTP access via `.htaccess`.
- Uploaded files are validated by MIME type (not just extension).
- CSRF tokens protect all state-changing POST requests.
- PHP sessions use `HttpOnly`, `SameSite=Strict`, and `Secure` (when HTTPS).

---

## Customisation

All settings live in `data/settings.json`:

```json
{
  "headline":       "Your App Title",
  "subheadline":    "Catchy subtitle here 🔥",
  "video":          "uploads/bg.mp4",
  "fallback_image": "uploads/bg.jpg",
  "download_link":  "#",
  "countdown":      10,
  "slots":          24,
  "animations":     true,
  "btn_primary":    "Download Now",
  "btn_secondary":  "Learn More",
  "badge_text":     "Limited slots available!"
}
```

You can also edit this file directly via cPanel File Manager.

---

## Features

- ✅ Fullscreen autoplay video background with subtle zoom animation
- ✅ Glassmorphism content card (backdrop-filter + transparency)
- ✅ Gradient headline text + fade-up animations
- ✅ Animated floating badge + pulsing CTA button
- ✅ Countdown timer (flip animation) — primary button locked until 0
- ✅ Ripple click effect + mobile haptic feedback
- ✅ Lightweight canvas particle effect (no libraries)
- ✅ Fully responsive (mobile-first)
- ✅ CTA click tracking in `data/clicks.json`
- ✅ Secure admin panel (CSRF, session, bcrypt)
- ✅ File upload with MIME validation (MP4 / JPG / PNG / WebP)
- ✅ Live preview iframe in dashboard