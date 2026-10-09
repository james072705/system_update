/* ============================================================
   theme.js - Dark/Light mode toggle
   ============================================================ */

(function () {
    const STORAGE_KEY = 'kiosk-theme';

    function getStoredTheme() {
        return localStorage.getItem(STORAGE_KEY);
    }

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem(STORAGE_KEY, theme);

        // Update toggle icons
        document.querySelectorAll('.theme-toggle').forEach(btn => {
            const icon = btn.querySelector('i');
            if (icon) {
                icon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
            }
        });
    }

// Init theme on load - uses the user's saved choice (persists across refresh).
    // Does NOT follow system preference (prefers-color-scheme).
    // Defaults to LIGHT only if the user has never chosen a theme.
    const initial = getStoredTheme() || 'light';
    applyTheme(initial);

    // Toggle click handlers
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.theme-toggle');
        if (btn) {
            const current = document.documentElement.getAttribute('data-theme');
            applyTheme(current === 'dark' ? 'light' : 'dark');
        }
    });

    // Expose API
    window.themeToggle = {
        set: applyTheme,
        get: () => document.documentElement.getAttribute('data-theme')
    };
})();
