<?php
require_once __DIR__ . '/includes/auth.php';
primeRequireLogin();

$currentUser = primeGetCurrentUser();
$baseUrl = primeGetBaseUrl();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $users = primeLoadData('users');
    foreach ($users as &$user) {
        if (($user['id'] ?? '') !== ($currentUser['id'] ?? '')) {
            continue;
        }

        if ($action === 'update_profile') {
            $user['name'] = primeRaw($_POST['name'] ?? $user['name']);
            $user['email'] = primeRaw($_POST['email'] ?? $user['email']);
            $_SESSION['primekeys_name'] = $user['name'];
            $_SESSION['primekeys_email'] = $user['email'];
            primeSetFlash('success', 'Profile updated successfully.');
            primeAddAuditLog($currentUser, 'update_profile', 'Updated profile details.');
        }

        if ($action === 'change_password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            if (!password_verify($currentPassword, $user['password'] ?? '')) {
                primeSetFlash('error', 'Current password is incorrect.');
                header('Location: ' . $baseUrl . '/profile.php');
                exit;
            }
            if (strlen($newPassword) < 6) {
                primeSetFlash('error', 'New password must be at least 6 characters.');
                header('Location: ' . $baseUrl . '/profile.php');
                exit;
            }
            if ($newPassword !== $confirmPassword) {
                primeSetFlash('error', 'Password confirmation does not match.');
                header('Location: ' . $baseUrl . '/profile.php');
                exit;
            }
            $user['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
            primeSetFlash('success', 'Password updated successfully.');
            primeAddAuditLog($currentUser, 'change_password', 'Updated account password.');
        }
        break;
    }
    unset($user);
    primeSaveData('users', $users);
    header('Location: ' . $baseUrl . '/profile.php');
    exit;
}

$currentUser = primeGetCurrentUser();
$pageTitle = 'Profile';
include __DIR__ . '/includes/header.php';
?>
<div class="wrapper">
<?php include __DIR__ . '/includes/sidebar.php'; ?>
<div class="main-content">
    <div class="page-header">
        <h2><i class="bi bi-shield-lock text-theme"></i> Profile & Password</h2>
        <p class="text-muted mb-0">Update your account details and change your password securely.</p>
    </div>
    <?php echo primeRenderFlash(); ?>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><i class="bi bi-person"></i> Update Profile</div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="row g-3">
                            <div class="col-12"><label class="form-label">Name</label><input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($currentUser['name']); ?>" required></div>
                            <div class="col-12"><label class="form-label">Email</label><input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($currentUser['email']); ?>"></div>
                            <div class="col-12"><label class="form-label">Username</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($currentUser['username']); ?>" disabled></div>
                            <div class="col-12"><button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Profile</button></div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><i class="bi bi-key"></i> Update Your Password</div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="change_password">
                        <div class="row g-3">
                            <div class="col-12"><label class="form-label">Current Password</label><input type="password" class="form-control" name="current_password" required></div>
                            <div class="col-md-6"><label class="form-label">New Password</label><input type="password" class="form-control" name="new_password" required></div>
                            <div class="col-md-6"><label class="form-label">Confirm Password</label><input type="password" class="form-control" name="confirm_password" required></div>
                            <div class="col-12"><button type="submit" class="btn btn-outline-primary"><i class="bi bi-shield-check"></i> Change Password</button></div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
