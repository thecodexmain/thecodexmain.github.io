// Amrit Web Panel - Main JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // ========== THEME TOGGLE ==========
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');
    const html = document.documentElement;
    
    function setTheme(theme) {
        html.setAttribute('data-theme', theme);
        localStorage.setItem('awp-theme', theme);
        if (themeIcon) {
            themeIcon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        }
    }
    
    // Load saved theme
    const savedTheme = localStorage.getItem('awp-theme') || 'light';
    setTheme(savedTheme);
    
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            const current = html.getAttribute('data-theme');
            setTheme(current === 'dark' ? 'light' : 'dark');
        });
    }
    
    // ========== SIDEBAR TOGGLE ==========
    const sidebar = document.getElementById('sidebar');
    const sidebarCollapseBtn = document.getElementById('sidebarCollapseBtn');
    const mainContent = document.querySelector('.main-content');
    
    // Create overlay for mobile
    let overlay = document.getElementById('sidebarOverlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'sidebarOverlay';
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);
    }
    
    function isMobile() { return window.innerWidth <= 992; }
    
    if (sidebarCollapseBtn && sidebar) {
        sidebarCollapseBtn.addEventListener('click', function() {
            if (isMobile()) {
                sidebar.classList.toggle('mobile-open');
                overlay.classList.toggle('show');
            } else {
                sidebar.classList.toggle('collapsed');
                mainContent && mainContent.classList.toggle('expanded');
                localStorage.setItem('awp-sidebar', sidebar.classList.contains('collapsed') ? 'collapsed' : 'open');
            }
        });
    }
    
    overlay.addEventListener('click', function() {
        sidebar && sidebar.classList.remove('mobile-open');
        overlay.classList.remove('show');
    });
    
    // Restore sidebar state on desktop
    if (!isMobile() && localStorage.getItem('awp-sidebar') === 'collapsed' && sidebar) {
        sidebar.classList.add('collapsed');
        mainContent && mainContent.classList.add('expanded');
    }
    
    // ========== DROPDOWNS ==========
    document.querySelectorAll('[id$="Btn"]').forEach(btn => {
        const menuId = btn.id.replace('Btn', 'Menu');
        const menu = document.getElementById(menuId);
        if (menu && menu.classList.contains('dropdown-menu')) {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                // Close all others
                document.querySelectorAll('.dropdown-menu.show').forEach(m => {
                    if (m !== menu) m.classList.remove('show');
                });
                menu.classList.toggle('show');
            });
        }
    });
    
    document.addEventListener('click', function() {
        document.querySelectorAll('.dropdown-menu.show').forEach(m => m.classList.remove('show'));
    });
    
    // ========== AUTO-DISMISS ALERTS ==========
    document.querySelectorAll('.alert[data-auto-dismiss]').forEach(alert => {
        const delay = parseInt(alert.dataset.autoDismiss) || 5000;
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s ease';
            setTimeout(() => alert.remove(), 500);
        }, delay);
    });
    
    // ========== MODALS ==========
    document.querySelectorAll('[data-modal]').forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = document.getElementById(this.dataset.modal);
            if (modal) showModal(modal);
        });
    });
    
    document.querySelectorAll('.modal-close, [data-dismiss="modal"]').forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.modal-overlay');
            if (modal) hideModal(modal);
        });
    });
    
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) hideModal(this);
        });
    });
    
    // ========== CONFIRM DIALOGS ==========
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', function(e) {
            const msg = this.dataset.confirm || 'Are you sure?';
            if (!confirm(msg)) e.preventDefault();
        });
    });
    
    // ========== SEARCH FILTER ==========
    const searchInputs = document.querySelectorAll('.table-search');
    searchInputs.forEach(input => {
        input.addEventListener('input', function() {
            const query = this.value.toLowerCase();
            const tableId = this.dataset.table;
            const table = document.getElementById(tableId);
            if (!table) return;
            table.querySelectorAll('tbody tr').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    });
    
    // ========== COPY TO CLIPBOARD ==========
    document.querySelectorAll('[data-copy]').forEach(btn => {
        btn.addEventListener('click', function() {
            const text = this.dataset.copy || this.previousElementSibling?.value || '';
            navigator.clipboard.writeText(text).then(() => {
                const orig = this.innerHTML;
                this.innerHTML = '<i class="fas fa-check"></i>';
                setTimeout(() => this.innerHTML = orig, 2000);
            });
        });
    });
    
    // ========== FILE UPLOAD PREVIEW ==========
    document.querySelectorAll('input[type="file"]').forEach(input => {
        input.addEventListener('change', function() {
            const previewId = this.dataset.preview;
            if (!previewId) return;
            const preview = document.getElementById(previewId);
            if (!preview) return;
            const file = this.files[0];
            if (file) {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = e => preview.src = e.target.result;
                    reader.readAsDataURL(file);
                } else {
                    preview.textContent = file.name;
                }
            }
        });
    });
    
    // ========== TOOLTIPS ==========
    // Using CSS tooltips, no JS needed
    
    // ========== SELECT ALL CHECKBOX ==========
    const selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = this.checked);
        });
    }
    
    // ========== LIVE COLOR PICKER ==========
    const colorPicker = document.getElementById('themeColorPicker');
    if (colorPicker) {
        colorPicker.addEventListener('input', function() {
            document.documentElement.style.setProperty('--primary', this.value);
        });
    }
    
    // ========== AUTO RESIZE TEXTAREA ==========
    document.querySelectorAll('textarea[data-auto-resize]').forEach(ta => {
        ta.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
    });
    
    // ========== FORM VALIDATION ==========
    document.querySelectorAll('form[data-validate]').forEach(form => {
        form.addEventListener('submit', function(e) {
            let valid = true;
            this.querySelectorAll('[required]').forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    valid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            if (!valid) e.preventDefault();
        });
    });
    
    // ========== LOADING STATE ON FORMS ==========
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = this.querySelector('[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                submitBtn.disabled = true;
                const orig = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner spin"></i> Processing...';
                // Re-enable after 10s as fallback
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = orig;
                }, 10000);
            }
        });
    });
    
    // ========== KEYBOARD SHORTCUTS ==========
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.show').forEach(m => hideModal(m));
            document.querySelectorAll('.dropdown-menu.show').forEach(m => m.classList.remove('show'));
        }
    });
    
    console.log('Amrit Web Panel JS loaded ✓');
});

// ========== MODAL HELPERS ==========
function showModal(modal) {
    if (typeof modal === 'string') modal = document.getElementById(modal);
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

function hideModal(modal) {
    if (typeof modal === 'string') modal = document.getElementById(modal);
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }
}

// ========== TOAST NOTIFICATIONS ==========
function showToast(message, type = 'info', duration = 4000) {
    const container = document.getElementById('toastContainer') || createToastContainer();
    const toast = document.createElement('div');
    const icons = { success: 'check-circle', danger: 'times-circle', warning: 'exclamation-triangle', info: 'info-circle' };
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `<i class="fas fa-${icons[type] || 'info-circle'}"></i><span>${message}</span><button onclick="this.parentElement.remove()" class="toast-close"><i class="fas fa-times"></i></button>`;
    container.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 10);
    setTimeout(() => { toast.classList.remove('show'); setTimeout(() => toast.remove(), 300); }, duration);
}

function createToastContainer() {
    const c = document.createElement('div');
    c.id = 'toastContainer';
    c.style.cssText = 'position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;display:flex;flex-direction:column;gap:0.5rem;';
    document.body.appendChild(c);
    return c;
}

// Toast styles (injected)
const toastStyles = document.createElement('style');
toastStyles.textContent = `
.toast { display:flex; align-items:center; gap:0.625rem; padding:0.875rem 1rem; border-radius:0.625rem; box-shadow:0 10px 25px rgba(0,0,0,.15); font-size:.875rem; font-weight:500; min-width:280px; max-width:400px; opacity:0; transform:translateX(100%); transition:all .3s ease; }
.toast.show { opacity:1; transform:translateX(0); }
.toast-success { background:#d1fae5; color:#065f46; border:1px solid #a7f3d0; }
.toast-danger { background:#fee2e2; color:#991b1b; border:1px solid #fecaca; }
.toast-warning { background:#fef3c7; color:#92400e; border:1px solid #fde68a; }
.toast-info { background:#dbeafe; color:#1e40af; border:1px solid #bfdbfe; }
.toast-close { margin-left:auto; background:none; border:none; cursor:pointer; opacity:0.6; font-size:.875rem; }
`;
document.head.appendChild(toastStyles);

// ========== AJAX HELPER ==========
async function apiRequest(url, method = 'GET', data = null) {
    const opts = {
        method,
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '' }
    };
    if (data) opts.body = JSON.stringify(data);
    const res = await fetch(url, opts);
    return res.json();
}
