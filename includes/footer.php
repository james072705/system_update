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

<!-- Logout Confirmation Modal -->
<div class="modal fade" id="logoutConfirmModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm" style="max-width:380px;margin-left:auto;margin-right:auto;">
    <div class="modal-content">
      <div class="modal-body text-center py-4">
        <div class="logout-modal-icon">
          <i class="bi bi-box-arrow-right"></i>
        </div>
        <h5 class="modal-title fw-bold mb-2" id="logoutModalLabel">Are you sure you want to log out?</h5>
        <p class="text-muted small mb-4">You will need to log in again to access your account.</p>
        <div class="d-flex gap-2 justify-content-center">
          <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-danger px-4" id="confirmLogoutBtn">Yes, Log Out</button>
        </div>
      </div>
    </div>
  </div>
</div>

</body>
</html>
<?php db()->query('SELECT 1'); // keep connection alive until end ?>
 