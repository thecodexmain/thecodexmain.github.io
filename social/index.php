<?php
require_once __DIR__ . '/includes/auth.php';

if (socialCurrentUserId()) {
    socialRedirect('feed.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!socialVerifyCsrf()) {
        socialSetFlash('danger', 'Invalid security token.');
        socialRedirect('index.php');
    }
    $action = $_POST['action'] ?? '';
    if ($action === 'register') {
        [$ok, $msg] = socialCreateUser((string)($_POST['username'] ?? ''), (string)($_POST['display_name'] ?? ''), (string)($_POST['password'] ?? ''));
        socialSetFlash($ok ? 'success' : 'danger', $msg);
    } elseif ($action === 'login') {
        $ok = socialLogin((string)($_POST['username'] ?? ''), (string)($_POST['password'] ?? ''));
        socialSetFlash($ok ? 'success' : 'danger', $ok ? 'Welcome back.' : 'Invalid username or password.');
        if ($ok) {
            socialRedirect('feed.php');
        }
    }
    socialRedirect('index.php');
}

socialRenderHeader('PhotoSocial - Login');
$token = socialCsrfToken();
?>
<div class="row justify-content-center mt-4">
    <div class="col-lg-5">
        <div class="card mb-3"><div class="card-body p-4">
            <h4 class="mb-3">Sign in</h4>
            <form method="post">
                <input type="hidden" name="_csrf" value="<?php echo socialEsc($token); ?>">
                <input type="hidden" name="action" value="login">
                <div class="mb-2"><label class="form-label">Username</label><input class="form-control" name="username" required></div>
                <div class="mb-3"><label class="form-label">Password</label><input type="password" class="form-control" name="password" required></div>
                <button class="btn btn-dark w-100">Login</button>
            </form>
        </div></div>
    </div>
    <div class="col-lg-5">
        <div class="card"><div class="card-body p-4">
            <h4 class="mb-3">Create account</h4>
            <form method="post">
                <input type="hidden" name="_csrf" value="<?php echo socialEsc($token); ?>">
                <input type="hidden" name="action" value="register">
                <div class="mb-2"><label class="form-label">Username</label><input class="form-control" name="username" required></div>
                <div class="mb-2"><label class="form-label">Display name</label><input class="form-control" name="display_name" required></div>
                <div class="mb-3"><label class="form-label">Password</label><input type="password" class="form-control" name="password" required></div>
                <button class="btn btn-outline-dark w-100">Register</button>
            </form>
            <p class="small text-muted mt-3 mb-0">The first registered account becomes admin for moderation actions.</p>
        </div></div>
    </div>
</div>
<?php socialRenderFooter(); ?>
