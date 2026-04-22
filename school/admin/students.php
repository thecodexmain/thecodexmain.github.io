<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['super_admin', 'admin']);

// Delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id       = sanitize($_GET['id']);
    $students = loadData('students');
    $students = array_values(array_filter($students, fn($s) => $s['id'] !== $id));
    saveData('students', $students);
    // Also remove user account
    $users = loadData('users');
    $users = array_values(array_filter($users, fn($u) => ($u['student_id'] ?? '') !== $id));
    saveData('users', $users);
    setFlash('success', 'Student deleted successfully.');
    header('Location: ' . getBaseUrl() . '/admin/students.php');
    exit;
}

$allStudents = loadData('students');

// Filter
$search  = sanitize($_GET['search']  ?? '');
$filterClass   = sanitize($_GET['class']   ?? '');
$filterSection = sanitize($_GET['section'] ?? '');

$filtered = $allStudents;
if ($search) {
    $filtered = array_filter($filtered, fn($s) => stripos($s['name']??'', $search) !== false || stripos($s['id']??'', $search) !== false || stripos($s['email']??'', $search) !== false);
}
if ($filterClass) {
    $filtered = array_filter($filtered, fn($s) => ($s['class']??'') === $filterClass);
}
if ($filterSection) {
    $filtered = array_filter($filtered, fn($s) => ($s['section']??'') === $filterSection);
}
$filtered = array_values($filtered);

$page   = max(1, (int)($_GET['page'] ?? 1));
$paged  = paginate($filtered, $page);

// Unique classes/sections for filter
$classes  = array_unique(array_column($allStudents, 'class'));
$sections = array_unique(array_column($allStudents, 'section'));
sort($classes); sort($sections);

$pageTitle = 'Students';
include __DIR__ . '/../includes/header.php';
?>
<div class="wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="bi bi-people text-theme"></i> Students</h2>
            <p class="text-muted mb-0">Total: <?php echo count($allStudents); ?> students</p>
        </div>
        <a href="<?php echo $baseUrl; ?>/admin/add_student.php" class="btn btn-primary"><i class="bi bi-person-plus"></i> Add Student</a>
    </div>

    <?php echo renderFlash(); ?>

    <!-- Filters -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="search" placeholder="Search by name, ID, email..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="class">
                        <option value="">All Classes</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?php echo htmlspecialchars($c); ?>" <?php echo $filterClass===$c?'selected':''; ?>>Class <?php echo htmlspecialchars($c); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="section">
                        <option value="">All Sections</option>
                        <?php foreach ($sections as $sec): ?>
                            <option value="<?php echo htmlspecialchars($sec); ?>" <?php echo $filterSection===$sec?'selected':''; ?>>Section <?php echo htmlspecialchars($sec); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-search"></i> Filter</button>
                </div>
                <div class="col-md-2">
                    <a href="<?php echo $baseUrl; ?>/admin/students.php" class="btn btn-outline-secondary w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Class</th>
                            <th>Roll No.</th>
                            <th>Parent</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($paged['data'])): ?>
                        <tr><td colspan="9" class="text-center text-muted py-4">No students found.</td></tr>
                    <?php else: foreach ($paged['data'] as $i => $s): ?>
                        <tr>
                            <td><?php echo ($page-1)*10 + $i + 1; ?></td>
                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($s['id']); ?></span></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar bg-primary text-white"><?php echo strtoupper(substr($s['name'],0,1)); ?></div>
                                    <div>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($s['name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($s['email']??''); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($s['class'].'-'.$s['section']); ?></td>
                            <td><?php echo htmlspecialchars($s['roll_number']??'-'); ?></td>
                            <td><?php echo htmlspecialchars($s['parent_name']??'-'); ?></td>
                            <td><?php echo htmlspecialchars($s['phone']??'-'); ?></td>
                            <td><span class="badge status-<?php echo $s['status']??'active'; ?>"><?php echo ucfirst($s['status']??'active'); ?></span></td>
                            <td>
                                <a href="<?php echo $baseUrl; ?>/admin/add_student.php?id=<?php echo urlencode($s['id']); ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                <a href="?action=delete&id=<?php echo urlencode($s['id']); ?>" class="btn btn-sm btn-outline-danger btn-delete ms-1" title="Delete"><i class="bi bi-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if ($paged['pages'] > 1): ?>
        <div class="card-footer bg-white">
            <nav><ul class="pagination mb-0 justify-content-end">
                <?php for ($p = 1; $p <= $paged['pages']; $p++): ?>
                    <li class="page-item <?php echo $p===$page?'active':''; ?>">
                        <a class="page-link" href="?page=<?php echo $p; ?>&search=<?php echo urlencode($search); ?>&class=<?php echo urlencode($filterClass); ?>&section=<?php echo urlencode($filterSection); ?>"><?php echo $p; ?></a>
                    </li>
                <?php endfor; ?>
            </ul></nav>
        </div>
        <?php endif; ?>
    </div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
