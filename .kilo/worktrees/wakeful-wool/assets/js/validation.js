/* ============================================================
   validation.js - Client-side form validation
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {
    // Validate numeric fields
    document.querySelectorAll('input[data-type="number"]').forEach(input => {
        input.addEventListener('input', () => {
            input.value = input.value.replace(/[^0-9.]/g, '');
        });
    });

    // Validate price fields
    document.querySelectorAll('input[data-type="price"]').forEach(input => {
        input.addEventListener('input', () => {
            let v = input.value.replace(/[^0-9.]/g, '');
            const parts = v.split('.');
            if (parts.length > 2) v = parts[0] + '.' + parts.slice(1).join('');
            input.value = v;
        });
    });

    // Generic form validation (bootstrap)
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => {
        form.addEventListener('submit', (e) => {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                form.classList.add('was-validated');
            }
        });
    });
});

// Validate required fields message
function validateRequired(form) {
    const fields = form.querySelectorAll('[required]');
    for (const f of fields) {
        if (!f.value.trim()) {
            showToast('Please fill out all required fields.', 'warning');
            f.focus();
            return false;
        }
    }
    return true;
}
