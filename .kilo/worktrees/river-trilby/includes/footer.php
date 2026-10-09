<?php
/**
 * Shared footer for admin/POS pages
 */
?>
        </main>

        <footer class="text-center py-4 text-muted small">
            &copy; <?= date('Y') ?> <?= e(SITE_NAME) ?> &mdash; <?= e(APP_TAGLINE) ?>. BSIT Capstone Project.
        </footer>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>const BASE_URL = <?= json_encode(BASE_URL) ?>;</script>
<script src="<?= BASE_URL ?>assets/js/theme.js?v=2.3"></script>
<script src="<?= BASE_URL ?>assets/js/app.js?v=2.1"></script>
<script src="<?= BASE_URL ?>assets/js/dashboard.js?v=2.1"></script>
<script src="<?= BASE_URL ?>assets/js/charts.js?v=2.1"></script>
<script src="<?= BASE_URL ?>assets/js/pos.js?v=2.1"></script>
<script src="<?= BASE_URL ?>assets/js/validation.js?v=2.1"></script>
</body>
</html>
<?php db()->query('SELECT 1'); // keep connection alive until end ?>
 