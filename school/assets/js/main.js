// School Management System - Main JS
document.addEventListener('DOMContentLoaded', function () {

    // Auto-hide flash alerts
    document.querySelectorAll('.alert-dismissible').forEach(function (el) {
        setTimeout(function () {
            try { new bootstrap.Alert(el).close(); } catch (e) {}
        }, 4500);
    });

    // Confirm delete
    document.querySelectorAll('.btn-delete').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            if (!confirm('Are you sure you want to delete this record? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });

    // Live table search
    var searchInput = document.getElementById('tableSearch');
    if (searchInput) {
        searchInput.addEventListener('keyup', function () {
            var filter = this.value.toLowerCase();
            document.querySelectorAll('table tbody tr').forEach(function (row) {
                row.style.display = row.textContent.toLowerCase().includes(filter) ? '' : 'none';
            });
        });
    }

    // Logo preview
    var logoInput = document.getElementById('logoInput');
    if (logoInput) {
        logoInput.addEventListener('change', function () {
            var file = this.files[0];
            if (file) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    var prev = document.getElementById('logoPreview');
                    if (prev) { prev.src = e.target.result; prev.style.display = 'block'; }
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Tooltip init
    var tooltipEls = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipEls.forEach(function (el) { new bootstrap.Tooltip(el); });

    // Active sidebar link highlight by URL
    var currentPath = window.location.pathname;
    document.querySelectorAll('.sidebar .nav-link').forEach(function (link) {
        if (link.getAttribute('href') && currentPath.endsWith(link.getAttribute('href').split('/').pop())) {
            link.classList.add('active');
        }
    });
});
