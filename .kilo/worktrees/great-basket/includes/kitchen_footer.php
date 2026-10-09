<?php
/**
 * Kitchen footer - secure kitchen site (kitchen role)
 */
?>
</div><!-- /kitchen-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>const BASE_URL = <?= json_encode(BASE_URL) ?>;</script>
<script src="<?= BASE_URL ?>assets/js/theme.js?v=2.3"></script>
<script src="<?= BASE_URL ?>assets/js/app.js?v=2.1"></script>
<script>
// Auto-refresh orders every 15 seconds
document.addEventListener('DOMContentLoaded', () => {
    setInterval(() => {
        const tab = new URLSearchParams(window.location.search).get('tab') || 'pending';
        if (tab === 'completed') return;
        // Refresh counts for all tabs
        location.reload();
    }, 15000);
});
</script>
</body>
</html>
</content>
