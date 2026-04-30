# Keymaster – PHP Key Management System

## Requirements
- PHP 7.4+ (PDO SQLite extension enabled)
- Apache/Nginx with `.htaccess` support  
- The `keymaster/data/` directory **must be writable** by the web-server user

## Quick Start

1. **Upload** the `keymaster/` folder to your PHP web host.
2. **Open** `keymaster/config.php` and change:
   - `ADMIN_USER` – your admin username
   - `ADMIN_PASS` – your admin password *(will be hashed on first run)*
   - `ADMIN_TOKEN` – secret token used to call protected API endpoints
3. **Make the data folder writable**:
   ```bash
   chmod 750 keymaster/data
   ```
4. **Browse** to `keymaster/index.php` – the public key-status checker.
5. **Browse** to `keymaster/admin_login.php` – login with your credentials.

---

## Pages

| URL | Description |
|-----|-------------|
| `index.php` | Public page – check key status by Device ID |
| `admin_login.php` | Admin login |
| `admin.php` | Full admin panel |
| `api.php` | REST API |

---

## API Reference

All API calls are plain HTTP GET (or POST). JSON is returned.

### Check status (public)
```
GET api.php?action=status&device_id=ANDROID-ABC123
```

### Generate a key (admin)
```
GET api.php?action=generate&device_id=ANDROID-ABC123&plan=premium&days=30&admin_token=YOUR_TOKEN
```

### Revoke a key (admin)
```
GET api.php?action=revoke&device_id=ANDROID-ABC123&admin_token=YOUR_TOKEN
```

### Toggle registrations (admin)
```
GET api.php?action=toggle_reg&value=0&admin_token=YOUR_TOKEN   # close
GET api.php?action=toggle_reg&value=1&admin_token=YOUR_TOKEN   # open
```

### List all keys (admin)
```
GET api.php?action=list&admin_token=YOUR_TOKEN
```

---

## Admin Panel Features

- 📊 Stats bar (total / active / expired / revoked / registration status)
- ➕ Generate new keys (device ID, plan, days)
- ⛔ Toggle registrations on/off (affects both UI and API)
- ✅ / ❌ Per-row Revoke & Delete actions
- 🔍 Live search across the key table
- 🔒 Change admin password

---

## Security Notes

- Change `ADMIN_TOKEN` in `config.php` before deploying.
- The `data/` directory is protected by `.htaccess` from direct browser access.
- Admin password is stored as a bcrypt hash in the SQLite settings table.
- All device IDs are sanitised (alphanumeric + `-_` only).
