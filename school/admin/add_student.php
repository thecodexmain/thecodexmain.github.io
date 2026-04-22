<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['super_admin', 'admin']);

$baseUrl  = getBaseUrl();
$editMode = false;
$student  = [];
$editId   = sanitize($_GET['id'] ?? '');

if ($editId) {
    $student = getStudentById($editId);
    if ($student) $editMode = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id         = sanitize($_POST['student_id'] ?? '');
    $name       = sanitize($_POST['name']        ?? '');
    $email      = sanitize($_POST['email']       ?? '');
    $phone      = sanitize($_POST['phone']       ?? '');
    $class      = sanitize($_POST['class']       ?? '');
    $section    = sanitize($_POST['section']     ?? '');
    $roll       = sanitize($_POST['roll_number'] ?? '');
    $dob        = sanitize($_POST['dob']         ?? '');
    $gender     = sanitize($_POST['gender']      ?? '');
    $address    = sanitize($_POST['address']     ?? '');
    $parentName = sanitize($_POST['parent_name'] ?? '');
    $parentPh   = sanitize($_POST['parent_phone']?? '');
    $admDate    = sanitize($_POST['admission_date']??'');
    $status     = sanitize($_POST['status']      ?? 'active');

    if (empty($name) || empty($class) || empty($section)) {
        setFlash('error', 'Name, Class and Section are required.');
        header('Location: ' . $baseUrl . '/admin/add_student.php' . ($editId ? '?id=' . urlencode($editId) : ''));
        exit;
    }

    $students = loadData('students');

    if ($editId) {
        // Update
        foreach ($students as &$s) {
            if ($s['id'] === $editId) {
                $s['name']          = $name;
                $s['email']         = $email;
                $s['phone']         = $phone;
                $s['class']         = $class;
                $s['section']       = $section;
                $s['roll_number']   = $roll;
                $s['dob']           = $dob;
                $s['gender']        = $gender;
                $s['address']       = $address;
                $s['parent_name']   = $parentName;
                $s['parent_phone']  = $parentPh;
                $s['admission_date']= $admDate;
                $s['status']        = $status;
                break;
            }
        }
        saveData('students', $students);
        setFlash('success', 'Student updated successfully!');
    } else {
        // Generate ID
        $newId = 'STU' . str_pad(count($students) + 1, 3, '0', STR_PAD_LEFT);
        // Ensure unique
        $existingIds = array_column($students, 'id');
        while (in_array($newId, $existingIds)) {
            $newId = 'STU' . strtoupper(substr(md5(uniqid()), 0, 5));
        }

        $students[] = [
            'id'            => $newId,
            'name'          => $name,
            'email'         => $email,
            'phone'         => $phone,
            'class'         => $class,
            'section'       => $section,
            'roll_number'   => $roll,
            'dob'           => $dob,
            'gender'        => $gender,
            'address'       => $address,
            'parent_name'   => $parentName,
            'parent_phone'  => $parentPh,
            'admission_date'=> $admDate,
            'status'        => $status
        ];
        saveData('students', $students);

        // Create login account if username provided
        $username = sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($username && $password) {
            $users    = loadData('users');
            $usernames = array_column($users, 'username');
            if (!in_array($username, $usernames)) {
                $users[] = [
                    'id'         => generateId(),
                    'name'       => $name,
                    'username'   => $username,
                    'password'   => password_hash($password, PASSWORD_DEFAULT),
                    'role'       => 'student',
                    'email'      => $email,
                    'student_id' => $newId,
                    'created_at' => date('Y-m-d')
                ];
                saveData('users', $users);
            }
        }

        setFlash('success', "Student added successfully! ID: $newId");
    }
    header('Location: ' . $baseUrl . '/admin/students.php');
    exit;
}

$classes  = loadData('classes');
$classOptions = array_unique(array_map(fn($c) => $c['class'], $classes));
sort($classOptions);
$sectionOptions = ['A','B','C','D','E'];

$pageTitle = $editMode ? 'Edit Student' : 'Add Student';
include __DIR__ . '/../includes/header.php';
?>
<div class="wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center">
        <h2><i class="bi bi-person-plus text-theme"></i> <?php echo $editMode ? 'Edit Student' : 'Add New Student'; ?></h2>
        <a href="<?php echo $baseUrl; ?>/admin/students.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    <?php echo renderFlash(); ?>

    <div class="card">
        <div class="card-body">
            <form method="POST">
                <?php if ($editMode): ?><input type="hidden" name="student_id" value="<?php echo htmlspecialchars($editId); ?>"><?php endif; ?>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($student['name'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($student['email'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Class <span class="text-danger">*</span></label>
                        <select class="form-select" name="class" required>
                            <option value="">Select Class</option>
                            <?php for ($i=1; $i<=12; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo ($student['class'] ?? '') == $i ? 'selected' : ''; ?>><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Section <span class="text-danger">*</span></label>
                        <select class="form-select" name="section" required>
                            <option value="">Select Section</option>
                            <?php foreach ($sectionOptions as $sec): ?>
                                <option value="<?php echo $sec; ?>" <?php echo ($student['section'] ?? '') === $sec ? 'selected' : ''; ?>><?php echo $sec; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Roll Number</label>
                        <input type="text" class="form-control" name="roll_number" value="<?php echo htmlspecialchars($student['roll_number'] ?? ''); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" class="form-control" name="dob" value="<?php echo htmlspecialchars($student['dob'] ?? ''); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Gender</label>
                        <select class="form-select" name="gender">
                            <option value="">Select</option>
                            <?php foreach (['Male','Female','Other'] as $g): ?>
                                <option value="<?php echo $g; ?>" <?php echo ($student['gender'] ?? '') === $g ? 'selected' : ''; ?>><?php echo $g; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Admission Date</label>
                        <input type="date" class="form-control" name="admission_date" value="<?php echo htmlspecialchars($student['admission_date'] ?? date('Y-m-d')); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <?php foreach (['active'=>'Active','inactive'=>'Inactive'] as $v=>$l): ?>
                                <option value="<?php echo $v; ?>" <?php echo ($student['status']??'active')===$v?'selected':''; ?>><?php echo $l; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" name="address" rows="2"><?php echo htmlspecialchars($student['address'] ?? ''); ?></textarea>
                    </div>

                    <div class="col-12"><hr><h6 class="text-muted">Parent/Guardian Information</h6></div>
                    <div class="col-md-6">
                        <label class="form-label">Parent/Guardian Name</label>
                        <input type="text" class="form-control" name="parent_name" value="<?php echo htmlspecialchars($student['parent_name'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Parent Phone</label>
                        <input type="text" class="form-control" name="parent_phone" value="<?php echo htmlspecialchars($student['parent_phone'] ?? ''); ?>">
                    </div>

                    <?php if (!$editMode): ?>
                    <div class="col-12"><hr><h6 class="text-muted">Login Account (Optional)</h6></div>
                    <div class="col-md-6">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" name="username" placeholder="Leave blank to skip">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" placeholder="Minimum 6 characters">
                    </div>
                    <?php endif; ?>

                    <div class="col-12">
                        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save"></i> <?php echo $editMode ? 'Update Student' : 'Add Student'; ?></button>
                        <a href="<?php echo $baseUrl; ?>/admin/students.php" class="btn btn-secondary ms-2">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
