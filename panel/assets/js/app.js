// Prime Webs Panel - Main JS

// ── THEME ──────────────────────────────────────────────────────────────────
const savedTheme = localStorage.getItem('pw_theme') || 'light';
document.documentElement.setAttribute('data-theme', savedTheme);

document.addEventListener('DOMContentLoaded', function () {
  // Theme toggle
  const themeToggle = document.getElementById('themeToggle');
  if (themeToggle) {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    themeToggle.textContent = isDark ? '☀️' : '🌙';
    themeToggle.addEventListener('click', () => {
      const current = document.documentElement.getAttribute('data-theme');
      const next = current === 'dark' ? 'light' : 'dark';
      document.documentElement.setAttribute('data-theme', next);
      localStorage.setItem('pw_theme', next);
      themeToggle.textContent = next === 'dark' ? '☀️' : '🌙';
    });
  }

  // ── SIDEBAR TOGGLE (MOBILE) ────────────────────────────────────────────
  const sidebarToggle = document.getElementById('sidebarToggle');
  const sidebar = document.getElementById('sidebar');
  const mainWrapper = document.getElementById('mainWrapper');
  if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener('click', () => {
      sidebar.classList.toggle('open');
    });
    // Close on outside click
    mainWrapper && mainWrapper.addEventListener('click', (e) => {
      if (sidebar.classList.contains('open') && !sidebar.contains(e.target)) {
        sidebar.classList.remove('open');
      }
    });
  }

  // ── MODAL HELPERS ─────────────────────────────────────────────────────
  document.querySelectorAll('[data-modal-close]').forEach(btn => {
    btn.addEventListener('click', () => {
      btn.closest('.modal-overlay').classList.remove('active');
    });
  });
  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) overlay.classList.remove('active');
    });
  });
  document.querySelectorAll('.modal-close').forEach(btn => {
    btn.addEventListener('click', () => {
      btn.closest('.modal-overlay').classList.remove('active');
    });
  });

  // ── DROPDOWNS ──────────────────────────────────────────────────────────
  function setupDropdown(btnId, menuId) {
    const btn = document.getElementById(btnId);
    const menu = document.getElementById(menuId);
    if (!btn || !menu) return;
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      // Close others
      document.querySelectorAll('.dropdown-menu.show').forEach(m => { if (m !== menu) m.classList.remove('show'); });
      menu.classList.toggle('show');
    });
  }
  setupDropdown('notifBtn', 'notifMenu');
  setupDropdown('userDropBtn', 'userDropMenu');
  document.addEventListener('click', () => {
    document.querySelectorAll('.dropdown-menu.show').forEach(m => m.classList.remove('show'));
  });

  // ── ALERT DISMISS ──────────────────────────────────────────────────────
  document.querySelectorAll('.alert-dismiss').forEach(btn => {
    btn.addEventListener('click', () => btn.closest('.alert').remove());
  });

  // ── TABLE SEARCH ──────────────────────────────────────────────────────
  const tableSearch = document.getElementById('tableSearch');
  if (tableSearch) {
    tableSearch.addEventListener('input', function () {
      const q = this.value.toLowerCase();
      document.querySelectorAll('tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
      });
    });
  }

  // ── TABS ──────────────────────────────────────────────────────────────
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      const group = this.closest('[data-tabs]') || this.closest('.card');
      if (!group) return;
      group.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      group.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
      this.classList.add('active');
      const target = this.getAttribute('data-tab');
      const content = group.querySelector('[data-tab-content="' + target + '"]');
      if (content) content.classList.add('active');
    });
  });

  // ── ANIMATE IN ────────────────────────────────────────────────────────
  document.querySelectorAll('.animate-in').forEach((el, i) => {
    el.style.animationDelay = (i * 0.04) + 's';
    el.style.opacity = '0';
  });

  // ── COPY BUTTONS ─────────────────────────────────────────────────────
  document.querySelectorAll('[data-copy]').forEach(btn => {
    btn.addEventListener('click', function () {
      const text = this.getAttribute('data-copy');
      copyText(text, this);
    });
  });

  // ── CONFIRM DELETE ────────────────────────────────────────────────────
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', function (e) {
      const msg = this.getAttribute('data-confirm') || 'Are you sure?';
      if (!confirm(msg)) e.preventDefault();
    });
  });

  // Apply custom theme color
  const savedColor = localStorage.getItem('pw_color');
  if (savedColor) applyThemeColor(savedColor);
});

// ── MODAL FUNCTIONS ───────────────────────────────────────────────────────
function openModal(id) {
  const m = document.getElementById(id);
  if (m) m.classList.add('active');
}
function closeModal(id) {
  const m = document.getElementById(id);
  if (m) m.classList.remove('active');
}

// ── TOAST ─────────────────────────────────────────────────────────────────
function showToast(message, type = 'success') {
  let container = document.getElementById('toastContainer');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toastContainer';
    container.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:8px;pointer-events:none;';
    document.body.appendChild(container);
  }
  const colors = { success: '#10b981', danger: '#ef4444', info: '#3b82f6', warning: '#f59e0b' };
  const icons = { success: '✅', danger: '❌', info: 'ℹ️', warning: '⚠️' };
  const toast = document.createElement('div');
  toast.style.cssText = `background:${colors[type] || colors.info};color:white;padding:11px 18px;border-radius:10px;font-size:13px;font-weight:500;box-shadow:0 4px 16px rgba(0,0,0,0.25);max-width:320px;pointer-events:all;cursor:pointer;display:flex;align-items:center;gap:8px;animation:fadeIn 0.3s ease;`;
  toast.innerHTML = `<span>${icons[type] || icons.info}</span><span>${message}</span>`;
  toast.addEventListener('click', () => toast.remove());
  container.appendChild(toast);
  setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = 'opacity 0.3s'; setTimeout(() => toast.remove(), 300); }, 4000);
}

// ── COPY TO CLIPBOARD ─────────────────────────────────────────────────────
function copyText(text, btn) {
  navigator.clipboard.writeText(text).then(() => {
    showToast('Copied to clipboard!', 'success');
    if (btn) {
      const orig = btn.textContent;
      btn.textContent = '✓ Copied';
      setTimeout(() => { btn.textContent = orig; }, 2000);
    }
  }).catch(() => {
    // Fallback
    const ta = document.createElement('textarea');
    ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
    document.body.appendChild(ta); ta.focus(); ta.select();
    try { document.execCommand('copy'); showToast('Copied!', 'success'); } catch (e) {}
    document.body.removeChild(ta);
  });
}

// ── AJAX HELPERS ──────────────────────────────────────────────────────────
function ajaxPost(url, data, callback) {
  const form = new FormData();
  for (const k in data) form.append(k, data[k]);
  fetch(url, { method: 'POST', body: form })
    .then(r => r.json())
    .then(callback)
    .catch(err => { showToast('Network error', 'danger'); console.error(err); });
}

// ── THEME COLOR ───────────────────────────────────────────────────────────
function applyThemeColor(color) {
  document.documentElement.style.setProperty('--primary', color);
  const meta = document.querySelector('meta[name="theme-color"]');
  if (meta) meta.setAttribute('content', color);
  localStorage.setItem('pw_color', color);
}

// ── CONFIRM DELETE ────────────────────────────────────────────────────────
function confirmDelete(message, callback) {
  if (confirm(message || 'Are you sure you want to delete this? This cannot be undone.')) callback();
}

// ── FILE SIZE FORMAT ─────────────────────────────────────────────────────
function formatSize(bytes) {
  const units = ['B', 'KB', 'MB', 'GB'];
  let i = 0;
  while (bytes >= 1024 && i < 3) { bytes /= 1024; i++; }
  return Math.round(bytes * 10) / 10 + ' ' + units[i];
}

// ── DEBOUNCE ──────────────────────────────────────────────────────────────
function debounce(fn, delay) {
  let timer;
  return function (...args) {
    clearTimeout(timer);
    timer = setTimeout(() => fn.apply(this, args), delay);
  };
}
