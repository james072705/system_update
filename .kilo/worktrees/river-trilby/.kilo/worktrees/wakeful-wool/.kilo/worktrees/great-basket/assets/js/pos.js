/* ============================================================
   pos.js - Point of Sale cart interactions
   Handles: add product, update qty, remove item, cart count
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {
    // Add product to cart
    document.querySelectorAll('.p-add').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            if (btn.disabled) return;
            const productId = btn.dataset.id;
            const qty = btn.dataset.qty || 1;

            fetch(`${BASE_URL}api/cart.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=add&cart=pos&product_id=${productId}&qty=${qty}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    updatePosCartCount(data.count);
                    showToast(data.message, 'success');
                    // Reload so the server-rendered cart panel + total updates
                    setTimeout(() => location.reload(), 400);
                } else {
                    showToast(data.message || 'Error', 'error');
                }
            })
            .catch(() => showToast('Network error adding to cart.', 'error'));
        });
    });

    // Refresh cart count on load
    refreshPosCartCount();

// Cart count refresh
    function refreshPosCartCount() {
        fetch(`${BASE_URL}api/cart.php?action=count&cart=pos`)
            .then(r => r.json())
            .then(data => {
                if (data.count !== undefined) updatePosCartCount(data.count);
            })
            .catch(() => {});
    }

    function updatePosCartCount(count) {
        document.querySelectorAll('.cart-count').forEach(el => {
            el.textContent = count;
            // hide/show badge if zero
            el.style.display = count > 0 ? 'inline-block' : 'none';
        });
    }
});

// Reusable handlers for cart qty & remove (used on index.php)
document.addEventListener('click', (e) => {
const updateBtn = e.target.closest('.cart-update');
    if (updateBtn) {
        const productId = updateBtn.dataset.id;
        const qty = updateBtn.dataset.qty;
        fetch(`${BASE_URL}api/cart.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=update&cart=pos&product_id=${productId}&qty=${qty}`
        })
        .then(r => r.json())
        .then(() => location.reload())
        .catch(() => showToast('Error updating quantity.', 'error'));
        return;
    }

    const removeBtn = e.target.closest('.cart-remove');
    if (removeBtn) {
        const productId = removeBtn.dataset.id;
        fetch(`${BASE_URL}api/cart.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=remove&cart=pos&product_id=${productId}`
        })
        .then(r => r.json())
        .then(() => location.reload())
        .catch(() => showToast('Error removing item.', 'error'));
    }
});

