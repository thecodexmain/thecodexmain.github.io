<?php $settings = hostingGetSettings(); ?>
</main>
<footer class="border-top py-4 mt-5">
    <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
        <div class="text-muted small">© <?php echo date('Y'); ?> <?php echo htmlspecialchars($settings['brand_name']); ?> • <?php echo htmlspecialchars($settings['tagline']); ?></div>
        <div class="text-muted small"><i class="bi bi-envelope me-1"></i><?php echo htmlspecialchars($settings['support_email']); ?></div>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

