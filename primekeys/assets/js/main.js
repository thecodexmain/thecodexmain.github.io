document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.alert-dismissible').forEach(function (el) {
        setTimeout(function () {
            try { new bootstrap.Alert(el).close(); } catch (e) {}
        }, 4000);
    });

    document.querySelectorAll('[data-confirm]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            var message = btn.getAttribute('data-confirm') || 'Are you sure?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });

    var colorInput = document.getElementById('themeColorInput');
    var colorHex = document.getElementById('themeColorHex');
    if (colorInput && colorHex) {
        colorInput.addEventListener('input', function () { colorHex.value = this.value; });
        colorHex.addEventListener('change', function () { colorInput.value = this.value; });
    }
});
