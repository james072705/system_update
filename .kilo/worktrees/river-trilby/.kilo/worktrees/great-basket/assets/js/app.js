/* ============================================================
   app.js - Global utilities & shared behavior
   ============================================================ */

// Confirm dialog helper
function confirmAction(message, form) {
    if (confirm(message || 'Are you sure?')) {
        if (form) form.submit();
        return true;
    }
    return false;
}

// Show a toast notification
function showToast(message, type = 'success') {
    const icons = { success: 'bi-check-circle-fill', error: 'bi-x-circle-fill', warning: 'bi-exclamation-triangle-fill', info: 'bi-info-circle-fill' };
    const colors = { success: '#2a9d8f', error: '#e76f51', warning: '#f4a261', info: '#457b9d' };

    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:10px;';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.style.cssText = `background:${colors[type]};color:#fff;padding:14px 20px;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,.2);display:flex;align-items:center;gap:10px;font-weight:600;min-width:260px;animation:slideIn .3s ease;`;
    toast.innerHTML = `<i class="bi ${icons[type]}"></i><span>${message}</span>`;

    container.appendChild(toast);
    setTimeout(() => { toast.style.opacity = '0'; toast.style.transform = 'translateX(20px)'; toast.style.transition = 'all .4s'; }, 3000);
    setTimeout(() => toast.remove(), 3500);
}

// Auto-dismiss alerts
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.alert-dismissible').forEach(alert => {
        setTimeout(() => {
            if (alert) alert.style.transition = 'opacity .5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 600);
        }, 4000);
    });
});

// Format number with peso
function formatPeso(value) {
    return '₱' + Number(value).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// Smooth scroll to top
function scrollTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
