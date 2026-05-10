<?php
require_once __DIR__ . '/includes/layout.php';
$plans = loadHostingData('service_plans');
hostingLayoutStart('CodexHost - Managed Web Hosting');
?>
<div class="p-5 hero mb-4">
    <div class="row align-items-center">
        <div class="col-lg-7">
            <h1 class="display-5 fw-bold">High-performance hosting with instant support</h1>
            <p class="lead mb-4">Sell-ready hosting portal with client dashboard, ticket system, service requests, and admin approval workflow with cPanel onboarding.</p>
            <a href="<?php echo hostingGetBaseUrl(); ?>/register.php" class="btn btn-light btn-lg me-2">Get Started</a>
            <a href="<?php echo hostingGetBaseUrl(); ?>/login.php" class="btn btn-outline-light btn-lg">Client Login</a>
        </div>
        <div class="col-lg-5 mt-4 mt-lg-0">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="fw-bold">Included Features</h5>
                    <ul class="mb-0">
                        <li>User Dashboard & My Services</li>
                        <li>Buy New Service Requests</li>
                        <li>Support Ticketing with replies</li>
                        <li>Admin Approval + cPanel credentials</li>
                        <li>Automatic event mail notifications</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <?php foreach ($plans as $plan): ?>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="fw-bold"><?php echo hostingSanitize($plan['name']); ?></h5>
                    <div class="display-6 fw-bold text-primary">$<?php echo number_format((float)$plan['price_monthly'], 2); ?><small class="fs-6 text-muted">/mo</small></div>
                    <ul class="mt-3 mb-0">
                        <li><?php echo hostingSanitize($plan['storage']); ?></li>
                        <li><?php echo hostingSanitize($plan['bandwidth']); ?></li>
                        <li><?php echo hostingSanitize($plan['emails']); ?></li>
                        <li><?php echo hostingSanitize($plan['ssl']); ?></li>
                    </ul>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php hostingLayoutEnd(); ?>
