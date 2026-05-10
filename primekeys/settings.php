<?php
require_once __DIR__ . '/includes/auth.php';
primeRequireRole('owner');
primeSyncDataState();

$currentUser = primeGetCurrentUser();
$baseUrl = primeGetBaseUrl();
$settings = primeGetSettings();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings['brand_name'] = primeRaw($_POST['brand_name'] ?? 'PRIME KEYS');
    $settings['brand_tagline'] = primeRaw($_POST['brand_tagline'] ?? 'Owner & Reseller Key Control Panel');
    $settings['theme_color'] = preg_match('/^#[0-9A-Fa-f]{6}$/', $_POST['theme_color'] ?? '') ? $_POST['theme_color'] : '#0d6efd';
    $settings['maintenance_mode'] = isset($_POST['maintenance_mode']);
    $settings['library_online'] = isset($_POST['library_online']);
    $settings['mod_name'] = primeRaw($_POST['mod_name'] ?? 'Prime Android Suite');
    $settings['mod_status'] = primeRaw($_POST['mod_status'] ?? 'Online');
    $settings['referral_mode'] = ($_POST['referral_mode'] ?? 'single_use') === 'unlimited' ? 'unlimited' : 'single_use';
    $settings['price_per_day'] = max(0, (float)($_POST['price_per_day'] ?? 0));
    $settings['features'] = [
        'esp' => isset($_POST['features']['esp']),
        'item' => isset($_POST['features']['item']),
        'aimbot' => isset($_POST['features']['aimbot']),
        'bullet_tracking' => isset($_POST['features']['bullet_tracking']),
        'memory' => isset($_POST['features']['memory']),
    ];
    primeSaveSettings($settings);
    primeAddAuditLog($currentUser, 'update_settings', 'Updated PRIME KEYS settings and online controls.');
    primeSetFlash('success', 'Settings saved successfully.');
    header('Location: ' . $baseUrl . '/settings.php');
    exit;
}

$pageTitle = 'Settings';
include __DIR__ . '/includes/header.php';
?>
<div class="wrapper">
<?php include __DIR__ . '/includes/sidebar.php'; ?>
<div class="main-content">
    <div class="page-header">
        <h2><i class="bi bi-sliders text-theme"></i> Global Settings</h2>
        <p class="text-muted mb-0">Manage branding, pricing, maintenance, referrals, mod details, and online features.</p>
    </div>
    <?php echo primeRenderFlash(); ?>

    <form method="POST">
        <div class="row g-3">
            <div class="col-lg-7">
                <div class="card mb-3">
                    <div class="card-header"><i class="bi bi-brush"></i> Branding</div>
                    <div class="card-body row g-3">
                        <div class="col-md-6"><label class="form-label">Brand Name</label><input type="text" class="form-control" name="brand_name" value="<?php echo htmlspecialchars($settings['brand_name']); ?>" required></div>
                        <div class="col-md-6"><label class="form-label">Tagline</label><input type="text" class="form-control" name="brand_tagline" value="<?php echo htmlspecialchars($settings['brand_tagline']); ?>"></div>
                        <div class="col-md-6"><label class="form-label">Theme Color</label><div class="d-flex gap-2"><input type="color" id="themeColorInput" class="form-control form-control-color" name="theme_color" value="<?php echo htmlspecialchars($settings['theme_color']); ?>" style="width:56px"><input type="text" id="themeColorHex" class="form-control" value="<?php echo htmlspecialchars($settings['theme_color']); ?>"></div></div>
                        <div class="col-md-6"><label class="form-label">Online Price / Day</label><input type="number" step="0.01" class="form-control" name="price_per_day" value="<?php echo htmlspecialchars((string)$settings['price_per_day']); ?>"></div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header"><i class="bi bi-cpu"></i> Mod & Platform Controls</div>
                    <div class="card-body row g-3">
                        <div class="col-md-6"><label class="form-label">Mod Name</label><input type="text" class="form-control" name="mod_name" value="<?php echo htmlspecialchars($settings['mod_name']); ?>"></div>
                        <div class="col-md-6"><label class="form-label">Mod Status</label><input type="text" class="form-control" name="mod_status" value="<?php echo htmlspecialchars($settings['mod_status']); ?>"></div>
                        <div class="col-md-6"><div class="form-check form-switch mt-4"><input class="form-check-input" type="checkbox" name="maintenance_mode" id="maintenanceMode" <?php echo !empty($settings['maintenance_mode']) ? 'checked' : ''; ?>><label class="form-check-label" for="maintenanceMode">Maintenance Mode Online</label></div></div>
                        <div class="col-md-6"><div class="form-check form-switch mt-4"><input class="form-check-input" type="checkbox" name="library_online" id="libraryOnline" <?php echo !empty($settings['library_online']) ? 'checked' : ''; ?>><label class="form-check-label" for="libraryOnline">Online Lib System</label></div></div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><i class="bi bi-toggles"></i> Feature Toggles</div>
                    <div class="card-body row g-3">
                        <?php foreach (['esp' => 'ESP', 'item' => 'Item', 'aimbot' => 'Aimbot', 'bullet_tracking' => 'Bullet Tracking', 'memory' => 'Memory'] as $key => $label): ?>
                            <div class="col-md-4 col-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="features[<?php echo $key; ?>]" id="feature_<?php echo $key; ?>" <?php echo !empty($settings['features'][$key]) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="feature_<?php echo $key; ?>"><?php echo $label; ?></label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card mb-3">
                    <div class="card-header"><i class="bi bi-diagram-3"></i> Referral Rules</div>
                    <div class="card-body">
                        <div class="form-check mb-2"><input class="form-check-input" type="radio" name="referral_mode" id="refSingle" value="single_use" <?php echo ($settings['referral_mode'] ?? 'single_use') === 'single_use' ? 'checked' : ''; ?>><label class="form-check-label" for="refSingle">One Referral One User Can Register</label></div>
                        <div class="form-check"><input class="form-check-input" type="radio" name="referral_mode" id="refUnlimited" value="unlimited" <?php echo ($settings['referral_mode'] ?? '') === 'unlimited' ? 'checked' : ''; ?>><label class="form-check-label" for="refUnlimited">One Referral Unlimited User Can Register</label></div>
                    </div>
                </div>
                <div class="card mb-3">
                    <div class="card-header"><i class="bi bi-activity"></i> Live Snapshot</div>
                    <div class="card-body small text-muted">
                        <div class="d-flex justify-content-between mb-2"><span>Maintenance</span><strong><?php echo !empty($settings['maintenance_mode']) ? 'On' : 'Off'; ?></strong></div>
                        <div class="d-flex justify-content-between mb-2"><span>Library</span><strong><?php echo !empty($settings['library_online']) ? 'Online' : 'Offline'; ?></strong></div>
                        <div class="d-flex justify-content-between mb-2"><span>Referral Mode</span><strong><?php echo ($settings['referral_mode'] ?? 'single_use') === 'unlimited' ? 'Unlimited' : 'Single'; ?></strong></div>
                        <div class="d-flex justify-content-between"><span>Price / Day</span><strong>₹<?php echo number_format((float)$settings['price_per_day'], 2); ?></strong></div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header"><i class="bi bi-save"></i> Save Changes</div>
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-save"></i> Save Settings</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
