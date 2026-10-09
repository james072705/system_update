/* ============================================================
   kiosk.js - Kiosk self-ordering interactions
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {
    // ============================================================
    // LANDING ADS CAROUSEL + START ORDER
    // ============================================================
    const landing   = document.getElementById('kiosk-landing');
    const menuWrap  = document.getElementById('kiosk-menu-wrap');
    const slides    = document.querySelectorAll('.landing-slide');
    const dots      = document.querySelectorAll('.landing-dots .dot');
    const startBtn  = document.getElementById('start-order-btn');

    let currentSlide = 1;
    let adTimer = null;
    const AD_INTERVAL = 5000; // change ad every 5 seconds

    function showSlide(n) {
        // wrap around 1..slides.length
        if (n < 1) n = slides.length;
        if (n > slides.length) n = 1;
        currentSlide = n;

        slides.forEach(s => s.classList.remove('active'));
        dots.forEach(d => d.classList.remove('active'));

        const activeSlide = document.querySelector(`.landing-slide[data-slide="${n}"]`);
        const activeDot   = document.querySelector(`.landing-dots .dot[data-slide="${n}"]`);
        if (activeSlide) activeSlide.classList.add('active');
        if (activeDot) activeDot.classList.add('active');
    }

    function startAdTimer() {
        stopAdTimer();
        adTimer = setInterval(() => showSlide(currentSlide + 1), AD_INTERVAL);
    }

    function stopAdTimer() {
        if (adTimer) { clearInterval(adTimer); adTimer = null; }
    }

    // Only run carousel logic if the landing screen exists
    if (landing && slides.length) {
        // Dot click navigation
        dots.forEach(dot => {
            dot.addEventListener('click', () => {
                showSlide(parseInt(dot.dataset.slide, 10));
                startAdTimer(); // reset timer
            });
        });

        // Start the auto-rotating ads
        showSlide(1);
        startAdTimer();
    }

    // Start Order button -> reveal the menu, hide landing
    if (startBtn && landing && menuWrap) {
        startBtn.addEventListener('click', function () {
            stopAdTimer();
            landing.style.display = 'none';          // hide ads screen
            document.body.classList.add('kiosk-started'); // show menu wrapper
            window.scrollTo(0, 0);
        });
    }

    // ============================================================
    // Category filter buttons
    // ============================================================
    const catButtons = document.querySelectorAll('.cat-btn');
    const productCards = document.querySelectorAll('.kiosk-product');

    catButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            catButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const catId = btn.dataset.cat;
            productCards.forEach(card => {
                if (catId === 'all' || card.dataset.cat === catId) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });

    // Live search
    const searchInput = document.getElementById('kiosk-search');
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            const q = searchInput.value.toLowerCase();
            productCards.forEach(card => {
                const name = (card.dataset.name || '').toLowerCase();
                const matches = name.includes(q);
                // Only show if not hidden by category
                const catBtn = document.querySelector('.cat-btn.active');
                const catMatch = !catBtn || catBtn.dataset.cat === 'all' || card.dataset.cat === catBtn.dataset.cat;
                card.style.display = (matches && catMatch) ? '' : 'none';
            });
        });
    }

// Add to cart via AJAX
    document.querySelectorAll('.p-add').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const productId = btn.dataset.id;
            const qty = btn.dataset.qty || 1;

            fetch(`${BASE_URL}api/cart.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=add&product_id=${productId}&qty=${qty}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    updateCartCount(data.count);
                    showToast(data.message, 'success');
                    // little animation
                    btn.innerHTML = '<i class="bi bi-check-lg"></i> Added';
                    setTimeout(() => {
                        btn.innerHTML = '<i class="bi bi-plus-lg"></i> Add';
                    }, 1000);
                } else {
                    showToast(data.message || 'Error', 'error');
                }
            });
        });
    });

    // Update cart count on load
    refreshCartCount();
});

function updateCartCount(count) {
    const els = document.querySelectorAll('.cart-count');
    els.forEach(el => el.textContent = count);
}

function refreshCartCount() {
    fetch(`${BASE_URL}api/cart.php?action=count`)
        .then(r => r.json())
        .then(data => {
            if (data.count !== undefined) updateCartCount(data.count);
        });
}

// Cart quantity controls (on cart page)
document.addEventListener('click', (e) => {
    const updateBtn = e.target.closest('.cart-update');
    if (updateBtn) {
        const productId = updateBtn.dataset.id;
        const qty = updateBtn.dataset.qty;
        fetch(`${BASE_URL}api/cart.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=update&product_id=${productId}&qty=${qty}`
        })
        .then(r => r.json())
        .then(() => location.reload());
    }

    const removeBtn = e.target.closest('.cart-remove');
    if (removeBtn) {
        const productId = removeBtn.dataset.id;
        fetch(`${BASE_URL}api/cart.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=remove&product_id=${productId}`
        })
        .then(r => r.json())
        .then(() => location.reload());
    }
});
