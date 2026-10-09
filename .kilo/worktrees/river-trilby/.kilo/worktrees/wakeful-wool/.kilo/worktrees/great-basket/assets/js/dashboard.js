/* ============================================================
   dashboard.js - Admin dashboard interactions
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {
    // Sidebar toggle (mobile)
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            if (backdrop) backdrop.classList.toggle('show');
        });
    }
    if (backdrop) {
        backdrop.addEventListener('click', () => {
            sidebar.classList.remove('open');
            backdrop.classList.remove('show');
        });
    }

    // Auto-refresh notifications count
    setInterval(() => {
        fetch(`${BASE_URL}api/notifications.php?action=count`)
            .then(r => r.json())
            .then(data => {
                const dot = document.getElementById('notif-dot');
                if (dot) dot.style.display = data.count > 0 ? 'block' : 'none';
            });
    }, 30000);
});
