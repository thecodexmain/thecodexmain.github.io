<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['super_admin', 'admin']);

$baseUrl  = getBaseUrl();
$editMode = false;
$teacher  = [];
$editId   = sanitize($_GET['id'] ?? '');

if ($editId) {
    $teacher = getTeacherById($editId);
    if ($teacher) $editMode = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name          = sanitize($_POST['name']          ?? '');
    $email         = sanitize($_POST['email']         ?? '');
    $phone         = sanitize($_POST['phone']         ?? '');
    $subject       = sanitize($_POST['subject']       ?? '');
    $qualification = sanitize($_POST['qualification'] ?? '');
    $joiningDate   = sanitize($_POST['joining_date']  ?? '');
    $salary        = sanitize($_POST['salary']        ?? '0');
    $status        = sanitize($_POST['status']        ?? 'active');

    if (empty($name)) {
        setFlash('error', 'Teacher name is required.');
        header('Location: ' . $baseUrl . '/admin/add_teacher.php' . ($editId ? '?id=' . urlencode($editId) : ''));
        exit;
    }

    $teachers = loadData('teachers');

    if ($editId) {
        foreach ($teachers as &$t) {
            if ($t['id'] === $editId) {
                $t['name']          = $name;
                $t['email']         = $email;
                $t['phone']         = $phone;
                $t['subject']       = $subject;
                $t['qualification'] = $qualification;
                $t['joining_date']  = $joiningDate;
                $t['salary']        = $salary;
                $t['status']        = $status;
                break;
            }
        }
        saveData('teachers', $teachers);
        setFlash('success', 'Teacher updated successfully!');
    } else {
        $newId = 'TCH' . str_pad(count($teachers) + 1, 3, '0', STR_PAD_LEFT);
        $existingIds = array_column($teachers, 'id');
        while (in_array($newId, $existingIds)) {
            $newId = 'TCH' . strtoupper(substr(md5(uniqid()), 0, 4));
        }

        $teachers[] = [
            'id'            => $newId,
            'name'          => $name,
            'email'         => $email,
            'phone'         => $phone,
            'subject'       => $subject,
            'qualification' => $qualification,
            'joining_date'  => $joiningDate,
            'salary'        => $salary,
            'status'        => $status
        ];
        saveData('teachers', $teachers);

        // Create login account
        $username = sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($username && $password) {
            $users     = loadData('users');
            $usernames = array_column($users, 'username');
            if (!in_array($username, $usernames)) {
                $users[] = [
                    'id'         => generateId(),
                    'name'       => $name,
                    'username'   => $username,
                    'password'   => password_hash($password, PASSWORD_DEFAULT),
                    'role'       => 'teacher',
                    'email'      => $email,
                    'teacher_id' => $newId,
                    'created_at' => date('Y-m-d')
                ];
                saveData('users', $users);
            }
        }
        setFlash('success', "Teacher added! ID: $newId");
    }
    header('Location: ' . $baseUrl . '/admin/teachers.php');
    exit;
}

$pageTitle = $editMode ? 'Edit Teacher' : 'Add Teacher';
include __DIR__ . '/../includes/header.php';
?>
<div class="wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center">
        <h2><i class="bi bi-person-badge text-theme"></i> <?php echo $editMode ? 'Edit Teacher' : 'Add Teacher'; ?></h2>
        <a href="<?php echo $baseUrl; ?>/admin/teachers.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
    <?php echo renderFlash(); ?>
    <div class="card">
        <div class="card-body">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($teacher['name'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($teacher['email'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($teacher['phone'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Subject</label>
                        <input type="text" class="form-control" name="subject" value="<?php echo htmlspecialchars($teacher['subject'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Qualification</label>
                        <input type="text" class="form-control" name="qualification" value="<?php echo htmlspecialchars($teacher['qualification'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Salary (₹)</label>
                        <input type="number" class="form-control" name="salary" value="<?php echo htmlspecialchars($teacher['salary'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Joining Date</label>
                        <input type="date" class="form-control" name="joining_date" value="<?php echo htmlspecialchars($teacher['joining_date'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="active"   <?php echo ($teacher['status']??'active')==='active'  ?'selected':''; ?>>Active</option>
                            <option value="inactive" <?php echo ($teacher['status']??'')==='inactive'?'selected':''; ?>>Inactive</option>
                        </select>
                    </div>

                    <?php if (!$editMode): ?>
                    <div class="col-12"><hr><h6 class="text-muted">Login Account (Optional)</h6></div>
                    <div class="col-md-6">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" name="username" placeholder="Leave blank to skip">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" placeholder="Min. 6 characters">
                    </div>
                    <?php endif; ?>

                    <div class="col-12">
                        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save"></i> <?php echo $editMode ? 'Update' : 'Add Teacher'; ?></button>
                        <a href="<?php echo $baseUrl; ?>/admin/teachers.php" class="btn btn-secondary ms-2">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
