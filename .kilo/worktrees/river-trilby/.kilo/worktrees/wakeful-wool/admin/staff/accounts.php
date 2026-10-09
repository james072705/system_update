<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../config/auth.php';
require_role('admin');

$pageTitle = 'Staff Accounts';
$currentPage = 'staff';

$delete_id = (int)($_GET['delete'] ?? 0);
if ($delete_id) {
    if ($delete_id !== $_SESSION['user_id']) {
        db()->prepare('DELETE FROM users WHERE user_id=?')->execute([$delete_id]);
        log_activity('Staff', 'Deleted staff account ID ' . $delete_id);
        $_SESSION['success'] = 'Staff account deleted.';
    } else {
        $_SESSION['error'] = 'You cannot delete your own account.';
    }
    header('Location: ' . BASE_URL . 'admin/staff/accounts.php');
    exit;
}

$users = db()->query('SELECT * FROM users ORDER BY role, fullname')->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-people me-2"></i>Staff Accounts</span>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-person-plus"></i> Add Staff</button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
<thead><tr><th>Name</th><th>Email</th><th>Username</th><th>Role</th><th class="text-center">Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
<td class="fw-bold"><?= e($u['fullname']) ?>
                            <?php if ($u['user_id'] === $_SESSION['user_id']): ?><span class="badge bg-soft-info ms-1">You</span><?php endif; ?>
                        </td>
                        <td><?= e($u['email'] ?? '') ?></td>
                        <td><?= e($u['username']) ?></td>
                        <td>
                            <span class="badge <?= $u['role'] === 'admin' ? 'bg-soft-primary' : ($u['role'] === 'kitchen' ? 'bg-soft-warning' : 'bg-soft-info') ?>">
                                <?= ucfirst($u['role']) ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="badge <?= $u['status'] === 'active' ? 'bg-soft-success' : 'bg-soft-secondary' ?>"><?= ucfirst($u['status']) ?></span>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
<button class="btn btn-sm btn-soft edit-user" data-id="<?= (int)$u['user_id'] ?>" data-name="<?= e($u['fullname']) ?>" data-email="<?= e($u['email'] ?? '') ?>" data-username="<?= e($u['username']) ?>" data-role="<?= e($u['role']) ?>" data-status="<?= e($u['status']) ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <?php if ($u['user_id'] !== $_SESSION['user_id']): ?>
                                    <a href="?delete=<?= (int)$u['user_id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this staff account?')"><i class="bi bi-trash"></i></a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mx-auto" style="max-width:420px;margin-left:auto;margin-right:auto;">
        <div class="modal-content">
            <form method="POST" action="add.php">
                <div class="modal-header"><h5 class="modal-title"><i class="bi bi-person-plus"></i> Add Staff</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" name="fullname" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="text" class="form-control" name="password" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select class="form-select" name="role">
                            <option value="kitchen">Kitchen</option>
                        </select>
                        <div class="form-text">Only one Administrator manages the system — no additional admin can be added here.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mx-auto" style="max-width:420px;margin-left:auto;margin-right:auto;">
        <div class="modal-content">
            <form method="POST" action="edit.php">
                <input type="hidden" name="user_id" id="edit-id">
                <div class="modal-header"><h5 class="modal-title"><i class="bi bi-pencil"></i> Edit Staff</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" name="fullname" id="edit-name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" id="edit-email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" name="username" id="edit-username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password <small class="text-muted">(leave blank to keep)</small></label>
                        <input type="text" class="form-control" name="password" placeholder="New password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select class="form-select" name="role" id="edit-role">
                            <option value="staff">Staff (POS)</option>
                            <option value="kitchen">Kitchen</option>
                        </select>
                        <div class="form-text" id="edit-role-note" style="display:none;"><i class="bi bi-shield-lock"></i> The Administrator role is fixed and cannot be changed here.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" id="edit-status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function setupRoleSelect(role) {
    const select = document.getElementById('edit-role');
    const note = document.getElementById('edit-role-note');
    // Clear current options
    ['staff', 'kitchen'].forEach(v => {
        let exists = select.querySelector(`option[value="${v}"]`);
        if (!exists) {
            let opt = document.createElement('option');
            opt.value = v;
            opt.textContent = v === 'staff' ? 'Staff (POS)' : 'Kitchen';
            select.appendChild(opt);
        }
    });
    // Handle admin (fixed, cannot be changed)
    if (role === 'admin') {
        // Ensure no admin option exists in select (keep out)
        let adminOpt = select.querySelector('option[value="admin"]');
        if (adminOpt) adminOpt.remove();
        select.disabled = true; // prevent changes to admin
        select.innerHTML = '<option value="admin">Administrator</option>';
        note.style.display = 'block';
    } else {
        select.disabled = false;
        select.value = role;
        note.style.display = 'none';
    }
}

document.querySelectorAll('.edit-user').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('edit-id').value = btn.dataset.id;
        document.getElementById('edit-name').value = btn.dataset.name;
        document.getElementById('edit-email').value = btn.dataset.email;
        document.getElementById('edit-username').value = btn.dataset.username;
        setupRoleSelect(btn.dataset.role);
        document.getElementById('edit-status').value = btn.dataset.status;
        new bootstrap.Modal(document.getElementById('editModal')).show();
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
