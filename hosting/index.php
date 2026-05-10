<?php
require_once __DIR__ . '/includes/auth.php';

$settings = hostingGetSettings();
$baseUrl = hostingGetBaseUrl();
$services = hostingEnsureDefaultServices();
?>
<?php require __DIR__ . '/includes/header.php'; ?>

<section class="hero p-4 p-md-5 mb-4">
    <div class="row align-items-center g-4">
        <div class="col-lg-7">
            <span class="badge badge-soft mb-3"><i class="bi bi-shield-lock me-1"></i>Secure Payments • Manual Approval • Instant Email</span>
            <h1 class="display-6 fw-bold mb-2">Sell hosting the right way — with approvals, tickets, and an admin panel.</h1>
            <p class="lead mb-4 text-white-50">Users can request services, manage their purchases, and open support tickets. Admins approve orders and share cPanel details automatically via email.</p>
            <div class="d-flex flex-wrap gap-2">
                <?php if (hostingIsLoggedIn()): ?>
                    <a class="btn btn-light" href="<?php echo $baseUrl; ?>/dashboard.php"><i class="bi bi-speedometer2 me-1"></i>Go to Dashboard</a>
                <?php else: ?>
                    <a class="btn btn-light" href="<?php echo $baseUrl; ?>/register.php"><i class="bi bi-person-plus me-1"></i>Create Account</a>
                    <a class="btn btn-outline-light" href="<?php echo $baseUrl; ?>/login.php"><i class="bi bi-box-arrow-in-right me-1"></i>Login</a>
                <?php endif; ?>
                <a class="btn btn-outline-light" href="#plans"><i class="bi bi-bag-check me-1"></i>View Plans</a>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="row g-3">
                <div class="col-6">
                    <div class="kpi bg-white text-dark">
                        <div class="label">Uptime Target</div>
                        <div class="value">99.9%</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="kpi bg-white text-dark">
                        <div class="label">Support</div>
                        <div class="value">Tickets</div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="kpi bg-white text-dark">
                        <div class="label">Workflow</div>
                        <div class="value">Request → Approve → cPanel Details</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="plans" class="mb-4">
    <div class="d-flex justify-content-between align-items-end mb-3">
        <div>
            <h2 class="h4 fw-bold mb-1">Hosting Plans</h2>
            <div class="text-muted">Request a plan and wait for admin approval.</div>
        </div>
        <a class="btn btn-sm btn-primary" href="<?php echo $baseUrl; ?>/user/buy.php"><i class="bi bi-cart-plus me-1"></i>Buy New Service</a>
    </div>
    <div class="row g-3">
        <?php foreach ($services as $s): if (empty($s['active'])) continue; ?>
            <div class="col-md-4">
                <div class="card plan-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h3 class="h5 fw-bold mb-1"><?php echo htmlspecialchars($s['name']); ?></h3>
                                <div class="text-muted small"><?php echo htmlspecialchars(ucfirst($s['billing_cycle'])); ?> billing</div>
                            </div>
                            <span class="badge text-bg-light border"><i class="bi bi-check2-circle me-1 text-success"></i>Popular</span>
                        </div>
                        <div class="price mt-3">$<?php echo number_format((float)$s['price'], 2); ?></div>
                        <ul class="mt-3 mb-0 small text-muted">
                            <?php foreach (($s['features'] ?? []) as $f): ?>
                                <li><?php echo htmlspecialchars($f); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="card-footer bg-transparent border-0 pt-0 pb-3 px-3">
                        <a class="btn btn-outline-primary w-100" href="<?php echo $baseUrl; ?>/user/buy.php?service=<?php echo urlencode($s['id']); ?>">
                            <i class="bi bi-bag-plus me-1"></i>Request This Plan
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section id="features" class="mb-4">
    <h2 class="h4 fw-bold mb-3">What’s included</h2>
    <div class="row g-3">
        <div class="col-md-4"><div class="card h-100"><div class="card-body"><h3 class="h6 fw-bold"><i class="bi bi-speedometer2 me-2 text-primary"></i>User Dashboard</h3><div class="text-muted small">View orders, services, and cPanel access details once approved.</div></div></div></div>
        <div class="col-md-4"><div class="card h-100"><div class="card-body"><h3 class="h6 fw-bold"><i class="bi bi-ticket-perforated me-2 text-primary"></i>Ticket System</h3><div class="text-muted small">Open tickets and track replies directly in your account.</div></div></div></div>
        <div class="col-md-4"><div class="card h-100"><div class="card-body"><h3 class="h6 fw-bold"><i class="bi bi-shield-check me-2 text-primary"></i>Admin Approval</h3><div class="text-muted small">Admins approve requests and send cPanel details automatically via email.</div></div></div></div>
    </div>
</section>

<section id="support" class="mb-2">
    <h2 class="h4 fw-bold mb-2">Support</h2>
    <div class="text-muted">Login and open a ticket from your dashboard.</div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

