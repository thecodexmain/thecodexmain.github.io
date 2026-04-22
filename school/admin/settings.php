<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['super_admin', 'admin']);

$settings = getSettings();
$baseUrl  = getBaseUrl();
$error    = '';
$success  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'general';

    if ($action === 'general') {
        $settings['school_name']  = sanitize($_POST['school_name'] ?? '');
        $settings['tagline']      = sanitize($_POST['tagline']     ?? '');
        $settings['email']        = sanitize($_POST['email']       ?? '');
        $settings['phone']        = sanitize($_POST['phone']       ?? '');
        $settings['address']      = sanitize($_POST['address']     ?? '');
        $settings['theme_color']  = sanitize($_POST['theme_color'] ?? '#0d6efd');

        // Logo upload
        if (!empty($_FILES['logo']['name'])) {
            $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
            $maxSize = 2 * 1024 * 1024;
            if (!in_array($_FILES['logo']['type'], $allowed)) {
                $error = 'Logo must be an image file (JPG, PNG, GIF, WEBP).';
            } elseif ($_FILES['logo']['size'] > $maxSize) {
                $error = 'Logo must be under 2MB.';
            } else {
                $ext      = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
                $filename = 'logo_' . time() . '.' . $ext;
                $uploadDir = __DIR__ . '/../uploads/logo/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadDir . $filename)) {
                    // Remove old logo
                    if (!empty($settings['logo'])) {
                        $old = $uploadDir . basename($settings['logo']);
                        if (file_exists($old)) @unlink($old);
                    }
                    $settings['logo'] = $filename;
                } else {
                    $error = 'Failed to upload logo.';
                }
            }
        }

        if (!$error) {
            // Save modules
            $moduleKeys = ['students','teachers','classes','attendance','exams','fees','timetable','assignments','library','notices','messages','events','documents','reports'];
            foreach ($moduleKeys as $m) {
                $settings['modules'][$m] = isset($_POST['modules'][$m]);
            }
            $path = __DIR__ . '/../data/settings.json';
            file_put_contents($path, json_encode($settings, JSON_PRETTY_PRINT));
            setFlash('success', 'Settings saved successfully!');
            header('Location: ' . $baseUrl . '/admin/settings.php');
            exit;
        }
    }
}

$pageTitle = 'Settings';
include __DIR__ . '/../includes/header.php';
$settings  = getSettings(); // reload
?>
<div class="wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="page-header">
        <h2><i class="bi bi-gear text-theme"></i> System Settings</h2>
        <p class="text-muted mb-0">Manage school information, theme, and modules</p>
    </div>

    <?php echo renderFlash(); ?>
    <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="general">
        <div class="row g-3">
            <!-- General Info -->
            <div class="col-lg-8">
                <div class="card mb-3">
                    <div class="card-header"><i class="bi bi-info-circle"></i> School Information</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">School Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="school_name" value="<?php echo htmlspecialchars($settings['school_name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tagline</label>
                                <input type="text" class="form-control" name="tagline" value="<?php echo htmlspecialchars($settings['tagline']); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($settings['email']); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($settings['phone']); ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" rows="2"><?php echo htmlspecialchars($settings['address']); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modules -->
                <div class="card">
                    <div class="card-header"><i class="bi bi-toggles"></i> Module Settings</div>
                    <div class="card-body">
                        <p class="text-muted small">Enable or disable system modules. Disabled modules will be hidden from the navigation.</p>
                        <div class="row g-2">
                            <?php
                            $moduleLabels = ['students'=>'Students','teachers'=>'Teachers','classes'=>'Classes','attendance'=>'Attendance','exams'=>'Exams','fees'=>'Fees','timetable'=>'Timetable','assignments'=>'Assignments','library'=>'Library','notices'=>'Notices','messages'=>'Messages','events'=>'Events','documents'=>'Documents','reports'=>'Reports'];
                            foreach ($moduleLabels as $key => $label):
                                $checked = ($settings['modules'][$key] ?? true) ? 'checked' : '';
                            ?>
                            <div class="col-md-4 col-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="modules[<?php echo $key; ?>]" id="mod_<?php echo $key; ?>" <?php echo $checked; ?>>
                                    <label class="form-check-label" for="mod_<?php echo $key; ?>"><?php echo $label; ?></label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Logo + Theme -->
            <div class="col-lg-4">
                <div class="card mb-3">
                    <div class="card-header"><i class="bi bi-palette"></i> Appearance</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Theme Color</label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="color" class="form-control form-control-color" name="theme_color" value="<?php echo htmlspecialchars($settings['theme_color']); ?>" style="width:50px;height:40px;">
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($settings['theme_color']); ?>" id="colorHex" onchange="document.querySelector('[name=theme_color]').value=this.value">
                            </div>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <label class="form-label">School Logo</label>
                            <?php if (!empty($settings['logo'])): ?>
                                <div class="mb-2">
                                    <img src="<?php echo $baseUrl; ?>/uploads/logo/<?php echo htmlspecialchars(basename($settings['logo'])); ?>" id="logoPreview" height="60" class="rounded border" style="max-width:100%">
                                </div>
                            <?php else: ?>
                                <img id="logoPreview" src="" style="display:none;max-width:100%;height:60px" class="rounded border mb-2">
                            <?php endif; ?>
                            <input type="file" class="form-control" name="logo" id="logoInput" accept="image/*">
                            <small class="text-muted">JPG, PNG, GIF, WEBP. Max 2MB.</small>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><i class="bi bi-info-circle"></i> System Info</div>
                    <div class="card-body small text-muted">
                        <div class="d-flex justify-content-between mb-2"><span>PHP Version</span><strong><?php echo PHP_VERSION; ?></strong></div>
                        <div class="d-flex justify-content-between mb-2"><span>Server</span><strong><?php echo php_uname('s'); ?></strong></div>
                        <div class="d-flex justify-content-between"><span>Data Storage</span><strong>JSON Files</strong></div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save"></i> Save Settings</button>
                <a href="<?php echo $baseUrl; ?>/admin/dashboard.php" class="btn btn-secondary ms-2">Cancel</a>
            </div>
        </div>
    </form>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
<script>
document.querySelector('[name=theme_color]').addEventListener('input', function() {
    document.getElementById('colorHex').value = this.value;
});
</script>
