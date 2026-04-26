<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/json_db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');
$currentUser = getCurrentUser();
$settings = getSettings();

$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $postAction = $_POST['action'] ?? '';
    
    if ($postAction === 'create') {
        $username = sanitizeUsername(trim($_POST['username'] ?? ''));
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $plan = trim($_POST['plan'] ?? 'basic');
        $role = $_POST['role'] ?? 'user';
        $expires = $_POST['expires_at'] ?? '';
        $resellerId = trim($_POST['reseller_id'] ?? '');
        
        if (empty($username) || empty($email) || empty($password)) {
            $error = 'Please fill in all required fields.';
        } elseif (!isValidEmail($email)) {
            $error = 'Invalid email address.';
        } elseif (getUserByUsername($username)) {
            $error = 'Username already taken.';
        } elseif (getUserByEmail($email)) {
            $error = 'Email already registered.';
        } elseif (!isStrongPassword($password)) {
            $error = 'Password must be at least 8 characters.';
        } else {
            $userId = generateId('u');
            $plan = getPlanById($plan) ? $plan : 'basic';
            $expiresAt = null;
            if (!empty($expires)) {
                $expiresAt = date('c', strtotime($expires));
            } elseif ($role === 'user') {
                $planData = getPlanById($plan);
                if ($planData) {
                    $expiresAt = calculateExpiry($planData['duration_days']);
                }
            }
            
            createUser([
                'id' => $userId,
                'username' => $username,
                'email' => $email,
                'password' => hashPassword($password),
                'role' => in_array($role, ['admin', 'reseller', 'user']) ? $role : 'user',
                'plan' => $plan,
                'status' => 'active',
                'created_at' => date('c'),
                'expires_at' => $expiresAt,
                'last_login' => null,
                'login_ip' => null,
                'twofa_enabled' => false,
                'twofa_secret' => null,
                'avatar' => null,
                'reseller_id' => !empty($resellerId) ? $resellerId : null
            ]);
            
            addLog('admin', 'create_user', $currentUser['id'], ['new_user' => $username]);
            
            // Notify user
            addNotification([
                'id' => generateId('n'),
                'user_id' => $userId,
                'title' => 'Welcome to ' . ($settings['site_name'] ?? 'Amrit Web Panel') . '!',
                'message' => 'Your account has been created.',
                'icon' => 'check-circle',
                'read_by' => [],
                'created_at' => date('c')
            ]);
            
            setFlash('success', "User '{$username}' created successfully.");
            redirect('/panel/admin/users.php');
        }
    }
    
    elseif ($postAction === 'edit') {
        $userId = $_POST['user_id'] ?? '';
        $user = getUserById($userId);
        if (!$user) { $error = 'User not found.'; }
        else {
            $updates = [];
            if (!empty($_POST['email']) && isValidEmail($_POST['email'])) $updates['email'] = trim($_POST['email']);
            if (!empty($_POST['password'])) {
                if (!isStrongPassword($_POST['password'])) { $error = 'Password must be at least 8 characters.'; }
                else $updates['password'] = hashPassword($_POST['password']);
            }
            if (!empty($_POST['plan'])) $updates['plan'] = $_POST['plan'];
            if (!empty($_POST['status'])) $updates['status'] = $_POST['status'];
            if (isset($_POST['expires_at'])) $updates['expires_at'] = !empty($_POST['expires_at']) ? date('c', strtotime($_POST['expires_at'])) : null;
            
            if (empty($error)) {
                updateUser($userId, $updates);
                addLog('admin', 'edit_user', $currentUser['id'], ['user_id' => $userId]);
                setFlash('success', 'User updated successfully.');
                redirect('/panel/admin/users.php');
            }
        }
    }
    
    elseif ($postAction === 'delete') {
        $userId = $_POST['user_id'] ?? '';
        $user = getUserById($userId);
        if ($user && $user['id'] !== $currentUser['id']) {
            deleteUser($userId);
            // Remove user sites
            $sites = getSites();
            $sites = array_values(array_filter($sites, fn($s) => $s['user_id'] !== $userId));
            saveSites($sites);
            addLog('admin', 'delete_user', $currentUser['id'], ['user_id' => $userId]);
            setFlash('success', 'User deleted successfully.');
        }
        redirect('/panel/admin/users.php');
    }
    
    elseif ($postAction === 'toggle_status') {
        $userId = $_POST['user_id'] ?? '';
        $user = getUserById($userId);
        if ($user && $user['id'] !== $currentUser['id']) {
            $newStatus = $user['status'] === 'active' ? 'suspended' : 'active';
            updateUser($userId, ['status' => $newStatus]);
            setFlash('success', 'User status updated.');
        }
        redirect('/panel/admin/users.php');
    }
    
    elseif ($postAction === 'renew') {
        $userId = $_POST['user_id'] ?? '';
        $days = (int)($_POST['days'] ?? 30);
        $user = getUserById($userId);
        if ($user) {
            $base = (!empty($user['expires_at']) && strtotime($user['expires_at']) > time()) 
                ? strtotime($user['expires_at']) : time();
            $newExpiry = date('c', $base + ($days * 86400));
            updateUser($userId, ['expires_at' => $newExpiry, 'status' => 'active']);
            setFlash('success', 'User renewed for ' . $days . ' days.');
        }
        redirect('/panel/admin/users.php');
    }
}

// Fetch data
$users = getUsers();
$plans = getPlans();
$resellers = getResellers();

// Filter
$filterRole = $_GET['role'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$search = $_GET['q'] ?? '';

$filteredUsers = $users;
if ($filterRole) $filteredUsers = array_filter($filteredUsers, fn($u) => $u['role'] === $filterRole);
if ($filterStatus) $filteredUsers = array_filter($filteredUsers, fn($u) => $u['status'] === $filterStatus);
if ($search) $filteredUsers = array_filter($filteredUsers, fn($u) => 
    stripos($u['username'], $search) !== false || stripos($u['email'], $search) !== false
);
$filteredUsers = array_values($filteredUsers);

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$paging = paginate($filteredUsers, $page, 20);
$displayUsers = $paging['items'];

// Edit user data
$editUser = null;
if ($action === 'edit' && !empty($_GET['id'])) {
    $editUser = getUserById($_GET['id']);
}

$pageTitle = 'User Management';
$activePage = 'users';

$flash = getFlash();
include __DIR__ . '/../includes/header.php';
?>
<div class="panel-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="main-content">
        <?php include __DIR__ . '/../includes/topbar.php'; ?>
        <div class="page-content">
            <?php if (!empty($flash['success'])): ?>
            <div class="alert alert-success" data-auto-dismiss="5000"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($flash['success']) ?></div>
            <?php endif; if ($error): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <div class="section-header">
                <div class="section-title">User Management</div>
                <button class="btn btn-primary" onclick="showModal('createUserModal')">
                    <i class="fas fa-user-plus"></i> Add User
                </button>
            </div>
            
            <!-- Filters -->
            <div class="toolbar">
                <form method="GET" action="" style="display:contents;">
                    <input type="text" name="q" class="search-input" placeholder="Search users..." value="<?= htmlspecialchars($search) ?>">
                    <select name="role" class="form-control" style="width:auto;" onchange="this.form.submit()">
                        <option value="">All Roles</option>
                        <option value="user" <?= $filterRole === 'user' ? 'selected' : '' ?>>Users</option>
                        <option value="reseller" <?= $filterRole === 'reseller' ? 'selected' : '' ?>>Resellers</option>
                        <option value="admin" <?= $filterRole === 'admin' ? 'selected' : '' ?>>Admins</option>
                    </select>
                    <select name="status" class="form-control" style="width:auto;" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="active" <?= $filterStatus === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="suspended" <?= $filterStatus === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                    </select>
                    <button type="submit" class="btn btn-secondary"><i class="fas fa-filter"></i> Filter</button>
                    <?php if ($search || $filterRole || $filterStatus): ?>
                    <a href="/panel/admin/users.php" class="btn btn-outline">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Users (<?= $paging['total'] ?>)</div>
                </div>
                <div class="table-wrapper">
                    <table id="usersTable">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll"></th>
                                <th>User</th>
                                <th>Role</th>
                                <th>Plan</th>
                                <th>Status</th>
                                <th>Expires</th>
                                <th>Last Login</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($displayUsers)): ?>
                        <tr><td colspan="8">
                            <div class="empty-state">
                                <i class="fas fa-users"></i>
                                <h3>No users found</h3>
                                <p>No users match your search criteria.</p>
                            </div>
                        </td></tr>
                        <?php else: foreach ($displayUsers as $user): ?>
                        <tr>
                            <td><input type="checkbox" class="row-checkbox" value="<?= $user['id'] ?>"></td>
                            <td>
                                <div style="font-weight:500;"><?= htmlspecialchars($user['username']) ?></div>
                                <div style="font-size:.75rem;color:var(--text-muted);"><?= htmlspecialchars($user['email']) ?></div>
                            </td>
                            <td><span class="badge badge-<?= $user['role'] ?>"><?= ucfirst($user['role']) ?></span></td>
                            <td><?= htmlspecialchars($user['plan'] ?? 'basic') ?></td>
                            <td>
                                <span class="badge badge-<?= $user['status'] === 'active' ? 'success' : 'danger' ?>">
                                    <?= ucfirst($user['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($user['role'] === 'admin'): ?>
                                <span class="text-muted">Never</span>
                                <?php elseif (empty($user['expires_at'])): ?>
                                <span class="text-muted">-</span>
                                <?php elseif (checkExpiry($user['expires_at'])): ?>
                                <span class="text-danger"><i class="fas fa-exclamation-circle"></i> Expired</span>
                                <?php else: ?>
                                <?= formatDate($user['expires_at']) ?>
                                <?php endif; ?>
                            </td>
                            <td><?= $user['last_login'] ? timeAgo($user['last_login']) : '<span class="text-muted">Never</span>' ?></td>
                            <td>
                                <div style="display:flex;gap:.375rem;">
                                    <button class="btn btn-sm btn-outline" onclick="openEditUser('<?= htmlspecialchars(json_encode($user), ENT_QUOTES) ?>')" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" style="display:inline;">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-<?= $user['status'] === 'active' ? 'warning' : 'success' ?>" title="<?= $user['status'] === 'active' ? 'Suspend' : 'Activate' ?>">
                                            <i class="fas fa-<?= $user['status'] === 'active' ? 'ban' : 'check' ?>"></i>
                                        </button>
                                    </form>
                                    <button class="btn btn-sm btn-info" onclick="openRenewModal('<?= $user['id'] ?>', '<?= htmlspecialchars($user['username']) ?>')" title="Renew">
                                        <i class="fas fa-redo"></i>
                                    </button>
                                    <?php if ($user['id'] !== $currentUser['id']): ?>
                                    <form method="POST" style="display:inline;" data-confirm="Delete user '<?= htmlspecialchars($user['username']) ?>'?">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($paging['total_pages'] > 1): ?>
                <div class="card-footer">
                    <?= paginationHtml($paging, '/panel/admin/users.php?q=' . urlencode($search) . '&role=' . urlencode($filterRole) . '&status=' . urlencode($filterStatus)) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Create User Modal -->
<div class="modal-overlay" id="createUserModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title"><i class="fas fa-user-plus"></i> Add New User</div>
            <button class="modal-close" data-dismiss="modal"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" action="" data-validate>
            <?= csrfField() ?>
            <input type="hidden" name="action" value="create">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Username *</label>
                        <input type="text" name="username" class="form-control" placeholder="e.g. john_doe" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-control" placeholder="email@example.com" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Password *</label>
                        <input type="password" name="password" class="form-control" placeholder="Min 8 characters" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-control">
                            <option value="user">User</option>
                            <option value="reseller">Reseller</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Plan</label>
                        <select name="plan" class="form-control">
                            <?php foreach ($plans as $plan): if (!$plan['active']) continue; ?>
                            <option value="<?= htmlspecialchars($plan['id']) ?>"><?= htmlspecialchars($plan['name']) ?> (<?= $settings['currency_symbol'] ?? '$' ?><?= $plan['price'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Custom Expiry</label>
                        <input type="date" name="expires_at" class="form-control">
                        <div class="form-text">Leave blank to use plan duration.</div>
                    </div>
                </div>
                <?php if (!empty($resellers)): ?>
                <div class="form-group">
                    <label class="form-label">Assign to Reseller</label>
                    <select name="reseller_id" class="form-control">
                        <option value="">None (Direct)</option>
                        <?php foreach ($resellers as $r): ?>
                        <option value="<?= htmlspecialchars($r['id']) ?>"><?= htmlspecialchars($r['username']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus"></i> Create User</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal-overlay" id="editUserModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title"><i class="fas fa-edit"></i> Edit User</div>
            <button class="modal-close" data-dismiss="modal"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" action="" data-validate>
            <?= csrfField() ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="user_id" id="editUserId">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" id="editUsername" class="form-control" disabled>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="editEmail" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Leave blank to keep">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Plan</label>
                        <select name="plan" id="editPlan" class="form-control">
                            <?php foreach ($plans as $plan): if (!$plan['active']) continue; ?>
                            <option value="<?= htmlspecialchars($plan['id']) ?>"><?= htmlspecialchars($plan['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" id="editStatus" class="form-control">
                            <option value="active">Active</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Expiry Date</label>
                    <input type="date" name="expires_at" id="editExpiry" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Renew Modal -->
<div class="modal-overlay" id="renewModal">
    <div class="modal" style="max-width:380px;">
        <div class="modal-header">
            <div class="modal-title"><i class="fas fa-redo"></i> Renew Subscription</div>
            <button class="modal-close" data-dismiss="modal"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" action="">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="renew">
            <input type="hidden" name="user_id" id="renewUserId">
            <div class="modal-body">
                <p>Renew subscription for <strong id="renewUsername"></strong></p>
                <div class="form-group" style="margin-top:1rem;">
                    <label class="form-label">Extend by (days)</label>
                    <select name="days" class="form-control">
                        <option value="7">7 days</option>
                        <option value="30" selected>30 days</option>
                        <option value="90">90 days</option>
                        <option value="180">180 days</option>
                        <option value="365">1 year</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success"><i class="fas fa-redo"></i> Renew</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditUser(userJson) {
    const user = JSON.parse(userJson);
    document.getElementById('editUserId').value = user.id;
    document.getElementById('editUsername').value = user.username;
    document.getElementById('editEmail').value = user.email;
    document.getElementById('editPlan').value = user.plan || 'basic';
    document.getElementById('editStatus').value = user.status || 'active';
    if (user.expires_at) {
        document.getElementById('editExpiry').value = user.expires_at.substring(0, 10);
    }
    showModal('editUserModal');
}
function openRenewModal(userId, username) {
    document.getElementById('renewUserId').value = userId;
    document.getElementById('renewUsername').textContent = username;
    showModal('renewModal');
}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
