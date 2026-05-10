<?php
require_once __DIR__ . '/../includes/auth.php';
hostingRequireRole(['admin']);
$baseUrl = hostingGetBaseUrl();

$settings = hostingGetSettings();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    hostingVerifyCsrf();
    $brand = trim((string)($_POST['brand_name'] ?? ''));
    $tagline = trim((string)($_POST['tagline'] ?? ''));
    $support = trim((string)($_POST['support_email'] ?? ''));
    $theme = trim((string)($_POST['theme_color'] ?? ''));

    $useSmtp = isset($_POST['use_smtp']);
    $smtpHost = trim((string)($_POST['smtp_host'] ?? ''));
    $smtpPort = (int)($_POST['smtp_port'] ?? 587);
    $smtpUser = trim((string)($_POST['smtp_user'] ?? ''));
    $smtpPass = (string)($_POST['smtp_pass'] ?? '');
    $smtpSecure = (string)($_POST['smtp_secure'] ?? 'tls');
    $fromEmail = trim((string)($_POST['from_email'] ?? ''));
    $fromName = trim((string)($_POST['from_name'] ?? ''));

    if ($brand === '' || $support === '') {
        $error = 'Brand name and support email are required.';
    } elseif (!filter_var($support, FILTER_VALIDATE_EMAIL)) {
        $error = 'Support email is invalid.';
    } elseif ($fromEmail !== '' && !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'From email is invalid.';
    } elseif ($theme !== '' && !preg_match('/^#[0-9a-f]{6}$/i', $theme)) {
        $error = 'Theme color must be a hex value like #4f46e5.';
    } else {
        $settings['brand_name'] = $brand;
        $settings['tagline'] = $tagline;
        $settings['support_email'] = $support;
        if ($theme !== '') $settings['theme_color'] = $theme;

        $settings['mail']['use_smtp'] = $useSmtp;
        $settings['mail']['smtp_host'] = $smtpHost;
        $settings['mail']['smtp_port'] = $smtpPort ?: 587;
        $settings['mail']['smtp_user'] = $smtpUser;
        if ($smtpPass !== '') $settings['mail']['smtp_pass'] = $smtpPass;
        $settings['mail']['smtp_secure'] = in_array($smtpSecure, ['tls', 'ssl', ''], true) ? $smtpSecure : 'tls';
        if ($fromEmail !== '') $settings['mail']['from_email'] = $fromEmail;
        if ($fromName !== '') $settings['mail']['from_name'] = $fromName;

        hostingSaveData('settings', $settings);
        hostingSetFlash('success', 'Settings saved.');
        header('Location: ' . hostingGetBaseUrl() . '/admin/settings.php');
        exit;
    }
}
?>
<?php require __DIR__ . '/../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h4 fw-bold mb-1">Settings</h1>
        <div class="text-muted">Configure branding and email delivery.</div>
    </div>
    <a class="btn btn-sm btn-outline-secondary" href="<?php echo $baseUrl; ?>/admin/dashboard.php"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-transparent fw-semibold">Site</div>
            <div class="card-body">
                <form method="post" action="" class="row g-2">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(hostingCsrfToken()); ?>">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Brand name</label>
                        <input class="form-control" name="brand_name" value="<?php echo htmlspecialchars($settings['brand_name'] ?? ''); ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Tagline</label>
                        <input class="form-control" name="tagline" value="<?php echo htmlspecialchars($settings['tagline'] ?? ''); ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Support email</label>
                        <input class="form-control" name="support_email" type="email" value="<?php echo htmlspecialchars($settings['support_email'] ?? ''); ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Theme color</label>
                        <input class="form-control" name="theme_color" value="<?php echo htmlspecialchars($settings['theme_color'] ?? '#4f46e5'); ?>" placeholder="#4f46e5">
                    </div>
                    <div class="col-12 d-flex justify-content-end">
                        <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i>Save</button>
                    </div>

                    <hr class="my-3">

                    <div class="col-12">
                        <div class="fw-semibold mb-1">Email delivery</div>
                        <div class="text-muted small">Emails are sent automatically when orders/tickets update. If SMTP is enabled, the app will send directly via your SMTP server; otherwise it uses PHP <code>mail()</code>.</div>
                    </div>
                    <div class="col-12 form-check ms-2">
                        <input class="form-check-input" type="checkbox" name="use_smtp" id="use_smtp" <?php echo !empty($settings['mail']['use_smtp']) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="use_smtp">Use SMTP (recommended)</label>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">SMTP host</label>
                        <input class="form-control" name="smtp_host" value="<?php echo htmlspecialchars($settings['mail']['smtp_host'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Port</label>
                        <input class="form-control" name="smtp_port" type="number" min="1" value="<?php echo htmlspecialchars((string)($settings['mail']['smtp_port'] ?? 587)); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">SMTP user</label>
                        <input class="form-control" name="smtp_user" value="<?php echo htmlspecialchars($settings['mail']['smtp_user'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">SMTP password</label>
                        <input class="form-control" name="smtp_pass" type="password" placeholder="Leave blank to keep current">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Security</label>
                        <select class="form-select" name="smtp_secure">
                            <option value="tls" <?php echo ($settings['mail']['smtp_secure'] ?? 'tls') === 'tls' ? 'selected' : ''; ?>>tls (STARTTLS)</option>
                            <option value="ssl" <?php echo ($settings['mail']['smtp_secure'] ?? 'tls') === 'ssl' ? 'selected' : ''; ?>>ssl</option>
                            <option value="" <?php echo ($settings['mail']['smtp_secure'] ?? 'tls') === '' ? 'selected' : ''; ?>>none</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">From name</label>
                        <input class="form-control" name="from_name" value="<?php echo htmlspecialchars($settings['mail']['from_name'] ?? ($settings['brand_name'] ?? '')); ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">From email</label>
                        <input class="form-control" name="from_email" type="email" value="<?php echo htmlspecialchars($settings['mail']['from_email'] ?? ($settings['support_email'] ?? '')); ?>">
                    </div>
                    <div class="col-12 d-flex justify-content-end">
                        <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i>Save</button>
                    </div>
                </form>
                <div class="alert alert-light border mt-3 mb-0 small">
                    Cron option: run <code>php hosting/cron/send_mail.php</code> every minute to retry queued emails.
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-transparent fw-semibold">Tips</div>
            <div class="card-body text-muted small">
                <ul class="mb-0">
                    <li>Use a real SMTP mailbox for best deliverability.</li>
                    <li>Check <code>hosting/data/mail.log</code> if emails are failing.</li>
                    <li>For production, protect the <code>hosting/data</code> directory via server config.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
