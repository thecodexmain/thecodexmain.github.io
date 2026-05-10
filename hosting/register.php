<?php
require_once __DIR__ . '/includes/auth.php';
if (hostingIsLoggedIn()) {
    header('Location: ' . hostingGetBaseUrl() . '/dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = trim($_POST['password'] ?? '');

    if ($name === '' || $email === '' || $password === '') {
        hostingSetFlash('danger', 'All fields are required.');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        hostingSetFlash('danger', 'Please enter a valid email address.');
    } elseif (strlen($password) < 6) {
        hostingSetFlash('danger', 'Password must be at least 6 characters.');
    } else {
        $users = loadHostingData('users');
        $exists = false;
        foreach ($users as $u) {
            if (strtolower($u['email']) === $email) {
                $exists = true;
                break;
            }
        }

        if ($exists) {
            hostingSetFlash('warning', 'Email already registered. Please login.');
        } else {
            $users[] = [
                'id' => hostingGenerateId('USR-'),
                'name' => $name,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'role' => 'user',
                'created_at' => date('Y-m-d H:i:s')
            ];
            saveHostingData('users', $users);
            hostingSendAutoMail($email, 'Welcome to CodexHost', "Hello {$name},\n\nYour account was created successfully.\n\nRegards,\nCodexHost Team", ['event' => 'user_registered']);
            hostingSetFlash('success', 'Account created successfully. You can now login.');
            header('Location: ' . hostingGetBaseUrl() . '/login.php');
            exit;
        }
    }
}

require_once __DIR__ . '/includes/layout.php';
hostingLayoutStart('Register - CodexHost');
?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h3 class="fw-bold mb-3">Create Account</h3>
                <form method="POST">
                    <div class="mb-3"><label class="form-label">Full Name</label><input name="name" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
                    <button class="btn btn-primary w-100">Create Account</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php hostingLayoutEnd(); ?>
