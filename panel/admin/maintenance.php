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
        $settings = readJson(DATA_PATH . 'settings.json');
        $settings['maintenance_mode'] = !empty($_POST['maintenance_mode']);
        $settings['maintenance_message'] = sanitize($_POST['maintenance_message'] ?? '');
        writeJson(DATA_PATH . 'settings.json', $settings);
        appLog('maintenance_toggle', $_SESSION['username'], 'Maintenance: ' . ($settings['maintenance_mode'] ? 'ON' : 'OFF'));
        $success = 'Maintenance settings updated.';
    }
}

$settings = readJson(DATA_PATH . 'settings.json');

renderHead('Maintenance');
renderSidebar('admin', 'maintenance');
renderTopbar('Maintenance Mode');
?>

<?php if ($success) renderAlert('success', $success); ?>

<div class="card" style="max-width:600px;">
    <div class="card-header"><span class="card-title">🔧 Maintenance Mode</span></div>
    <div class="card-body">
        <form method="POST">
            <?php csrfField(); ?>
            <div style="padding:20px;background:var(--surface2);border-radius:var(--radius-sm);margin-bottom:20px;text-align:center;">
                <?php if (!empty($settings['maintenance_mode'])): ?>
                <div style="font-size:48px;margin-bottom:8px;">🔴</div>
                <div style="font-size:18px;font-weight:700;color:var(--danger);">Maintenance Mode is ON</div>
                <div class="text-muted text-small" style="margin-top:6px;">Users see the maintenance page. Admins have full access.</div>
                <?php else: ?>
                <div style="font-size:48px;margin-bottom:8px;">🟢</div>
                <div style="font-size:18px;font-weight:700;color:var(--success);">Panel is Online</div>
                <div class="text-muted text-small" style="margin-top:6px;">All users have normal access.</div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:15px;">
                    <input type="checkbox" name="maintenance_mode" value="1" style="width:18px;height:18px;"
                        <?= !empty($settings['maintenance_mode']) ? 'checked' : '' ?>>
                    Enable Maintenance Mode
                </label>
            </div>

            <div class="form-group">
                <label>Maintenance Message</label>
                <textarea class="form-control" name="maintenance_message" rows="3"
                    placeholder="We are performing scheduled maintenance. Please check back soon."><?= htmlspecialchars($settings['maintenance_message'] ?? '') ?></textarea>
                <div class="form-hint">This message is shown to users when maintenance mode is active.</div>
            </div>

            <button type="submit" class="btn btn-primary">💾 Save</button>
        </form>
    </div>
</div>

<div class="card" style="max-width:600px;">
    <div class="card-header"><span class="card-title">ℹ️ Information</span></div>
    <div class="card-body">
        <ul style="padding-left:20px;color:var(--text-muted);font-size:13px;line-height:2;">
            <li>Maintenance mode blocks access for all non-admin users.</li>
            <li>Admin accounts can still login and access the panel normally.</li>
            <li>The login page remains accessible so you can login as admin.</li>
            <li>A 503 Service Unavailable response is sent to search engines.</li>
        </ul>
    </div>
</div>

<?php renderFooter(); ?>
