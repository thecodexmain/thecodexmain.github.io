<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';

requireRole('admin');

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $currentSettings = readJson(DATA_PATH . 'settings.json');

        $fields = [
            'site_name', 'site_logo', 'site_favicon', 'theme_color', 'footer_text',
            'maintenance_message', 'meta_title', 'meta_description',
            'smtp_host', 'smtp_user', 'smtp_pass', 'smtp_from',
            'default_timezone', 'currency'
        ];

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $currentSettings[$field] = sanitize($_POST[$field]);
            }
        }

        $currentSettings['smtp_port'] = (int)($_POST['smtp_port'] ?? 587);
        $currentSettings['max_login_attempts'] = (int)($_POST['max_login_attempts'] ?? 5);
        $currentSettings['lock_duration'] = (int)($_POST['lock_duration'] ?? 30);
        $currentSettings['maintenance_mode'] = !empty($_POST['maintenance_mode']);
        $currentSettings['2fa_enabled'] = !empty($_POST['2fa_enabled']);
        $currentSettings['installed'] = true;

        if (writeJson(DATA_PATH . 'settings.json', $currentSettings)) {
            appLog('settings_update', $_SESSION['username'], 'Updated settings');
            $success = 'Settings saved successfully.';
            // Reload settings
            global $settings;
            $settings = $currentSettings;
        } else {
            $error = 'Failed to save settings.';
        }
    }
}

$s = readJson(DATA_PATH . 'settings.json');
$timezones = DateTimeZone::listIdentifiers();

renderHead('Settings');
renderSidebar('admin', 'settings');
renderTopbar('Settings');
?>

<?php if ($success) renderAlert('success', $success); ?>
<?php if ($error) renderAlert('danger', $error); ?>

<form method="POST">
    <?php csrfField(); ?>

    <div class="row">
        <div class="col">
            <div class="card">
                <div class="card-header"><span class="card-title">🎨 Branding</span></div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Site Name</label>
                        <input type="text" class="form-control" name="site_name" value="<?= htmlspecialchars($s['site_name'] ?? 'Prime Webs') ?>">
                    </div>
                    <div class="form-group">
                        <label>Logo URL <span class="text-muted">(optional)</span></label>
                        <input type="url" class="form-control" name="site_logo" value="<?= htmlspecialchars($s['site_logo'] ?? '') ?>" placeholder="https://...">
                    </div>
                    <div class="form-group">
                        <label>Favicon URL <span class="text-muted">(optional)</span></label>
                        <input type="url" class="form-control" name="site_favicon" value="<?= htmlspecialchars($s['site_favicon'] ?? '') ?>" placeholder="https://...">
                    </div>
                    <div class="form-group">
                        <label>Theme Color</label>
                        <div style="display:flex;gap:8px;align-items:center;">
                            <input type="color" name="theme_color" value="<?= htmlspecialchars($s['theme_color'] ?? '#6366f1') ?>" style="width:50px;height:36px;border-radius:6px;border:1px solid var(--border);padding:2px;cursor:pointer;" onchange="document.documentElement.style.setProperty('--primary',this.value)">
                            <input type="text" class="form-control" style="flex:1;" name="theme_color_text" value="<?= htmlspecialchars($s['theme_color'] ?? '#6366f1') ?>" readonly>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Footer Text</label>
                        <input type="text" class="form-control" name="footer_text" value="<?= htmlspecialchars($s['footer_text'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><span class="card-title">📧 SMTP Email</span></div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>SMTP Host</label>
                            <input type="text" class="form-control" name="smtp_host" value="<?= htmlspecialchars($s['smtp_host'] ?? '') ?>" placeholder="smtp.gmail.com">
                        </div>
                        <div class="form-group">
                            <label>SMTP Port</label>
                            <input type="number" class="form-control" name="smtp_port" value="<?= $s['smtp_port'] ?? 587 ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>SMTP Username</label>
                            <input type="text" class="form-control" name="smtp_user" value="<?= htmlspecialchars($s['smtp_user'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>SMTP Password</label>
                            <input type="password" class="form-control" name="smtp_pass" value="<?= htmlspecialchars($s['smtp_pass'] ?? '') ?>" placeholder="••••••••">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>From Email</label>
                        <input type="email" class="form-control" name="smtp_from" value="<?= htmlspecialchars($s['smtp_from'] ?? '') ?>" placeholder="noreply@yourdomain.com">
                    </div>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card">
                <div class="card-header"><span class="card-title">🔐 Security</span></div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Max Login Attempts</label>
                            <input type="number" class="form-control" name="max_login_attempts" value="<?= $s['max_login_attempts'] ?? 5 ?>" min="1" max="20">
                        </div>
                        <div class="form-group">
                            <label>Lock Duration (minutes)</label>
                            <input type="number" class="form-control" name="lock_duration" value="<?= $s['lock_duration'] ?? 30 ?>" min="1">
                        </div>
                    </div>
                    <div class="form-group">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                            <input type="checkbox" name="2fa_enabled" value="1" <?= !empty($s['2fa_enabled']) ? 'checked' : '' ?>>
                            Enable Two-Factor Authentication (2FA)
                        </label>
                        <div class="form-hint">When enabled, users with 2FA set up must enter a code at login.</div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><span class="card-title">🌍 General</span></div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Default Timezone</label>
                        <select class="form-control" name="default_timezone">
                            <?php foreach ($timezones as $tz): ?>
                            <option value="<?= $tz ?>" <?= ($s['default_timezone'] ?? 'UTC') === $tz ? 'selected' : '' ?>><?= $tz ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Currency</label>
                        <select class="form-control" name="currency">
                            <?php foreach (['USD', 'EUR', 'GBP', 'INR', 'AUD', 'CAD', 'BRL'] as $c): ?>
                            <option value="<?= $c ?>" <?= ($s['currency'] ?? 'USD') === $c ? 'selected' : '' ?>><?= $c ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><span class="card-title">🔧 Maintenance</span></div>
                <div class="card-body">
                    <div class="form-group">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                            <input type="checkbox" name="maintenance_mode" value="1" <?= !empty($s['maintenance_mode']) ? 'checked' : '' ?>>
                            Enable Maintenance Mode
                        </label>
                        <div class="form-hint">Admins can still access the panel.</div>
                    </div>
                    <div class="form-group">
                        <label>Maintenance Message</label>
                        <textarea class="form-control" name="maintenance_message" rows="2"><?= htmlspecialchars($s['maintenance_message'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><span class="card-title">🔍 SEO / Meta</span></div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Meta Title</label>
                        <input type="text" class="form-control" name="meta_title" value="<?= htmlspecialchars($s['meta_title'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Meta Description</label>
                        <input type="text" class="form-control" name="meta_description" value="<?= htmlspecialchars($s['meta_description'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div style="text-align:right;margin-top:4px;">
        <button type="submit" class="btn btn-primary btn-lg">💾 Save Settings</button>
    </div>
</form>

<?php renderFooter(); ?>
<script>
// Sync color picker with text input
document.querySelector('input[name="theme_color"]').addEventListener('input', function() {
    document.querySelector('input[name="theme_color_text"]').value = this.value;
    document.documentElement.style.setProperty('--primary', this.value);
    localStorage.setItem('pw_color', this.value);
});
</script>
