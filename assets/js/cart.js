/* ============================================================
   cart.js - Cart helper functions
   ============================================================ */

// Compute grand total from cart (client-side display)
function computeCartTotal() {
    let total = 0;
    document.querySelectorAll('.cart-line-total').forEach(el => {
        total += parseFloat(el.dataset.total || 0);
    });
    return total;
}

// Update totals display
function updateTotals() {
    const totalEl = document.getElementById('cart-total');
    if (!totalEl) return;

    // Only recalculate when there are actual line items on this page (e.g. cart page).
    // On the checkout page there are no .cart-line-total elements, so keep the
    // server-rendered total (from data-amount) instead of overwriting it with 0.
    const lineTotals = document.querySelectorAll('.cart-line-total');
    if (lineTotals.length > 0) {
        totalEl.textContent = formatPeso(computeCartTotal());
    } else if (totalEl.dataset.amount !== undefined) {
        totalEl.textContent = formatPeso(totalEl.dataset.amount);
    }
}

// Cash payment change computation
function computeChange() {
    const cash = parseFloat(document.getElementById('cash-input')?.value || 0);
    const total = parseFloat(document.getElementById('cart-total')?.dataset.amount || computeCartTotal());
    const change = cash - total;
    const changeEl = document.getElementById('change-amount');
    if (changeEl) {
        changeEl.textContent = change >= 0 ? formatPeso(change) : 'Insufficient';
        changeEl.classList.toggle('text-danger', change < 0);
    }
    const payBtn = document.getElementById('pay-btn');
    if (payBtn) payBtn.disabled = change < 0;
}

document.addEventListener('DOMContentLoaded', () => {
    const cashInput = document.getElementById('cash-input');
    if (cashInput) {
        cashInput.addEventListener('input', computeChange);
        computeChange();
    }
    updateTotals();
});
