<?php
/** @var string $error */
/** @var string $success */
/** @var array $translations */
/** @var array $users */
/** @var string $search */

$error = $error ?? '';
$success = $success ?? '';
$search = $search ?? '';
$users = $users ?? [];
$translations = $translations ?? [];
?>

<div class="container py-4">
    <!-- SweetAlert notifications logic based on backend status -->
    <?php if ($error): ?>
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                Swal.fire({
                    icon: 'error',
                    title: '<?php echo htmlspecialchars($translations['ERROR'] ?? 'Error'); ?>',
                    text: <?php echo json_encode($error); ?>,
                    confirmButtonColor: '#1abc9c'
                });
            });
        </script>
    <?php endif; ?>
    <?php if ($success): ?>
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                Swal.fire({
                    icon: 'success',
                    title: '<?php echo htmlspecialchars($translations['SUCCESS'] ?? 'Success'); ?>',
                    text: <?php echo json_encode($success); ?>,
                    confirmButtonColor: '#1abc9c',
                    timer: 3000,
                    timerProgressBar: true
                });
            });
        </script>
    <?php endif; ?>

    <div class="row">
        <!-- Left Column: User Table List -->
        <div class="col-lg-8 mb-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white py-3 border-light d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                    <h5 class="m-0 fw-bold text-dark d-flex align-items-center">
                        <i class="fa-thin fa-users text-info me-2 fs-4"></i>
                        <?php echo htmlspecialchars($translations['USER_LIST'] ?? 'System Users'); ?>
                    </h5>
                    
                    <!-- Search Form -->
                    <form method="get" action="" class="d-flex gap-2">
                        <input type="hidden" name="page" value="users">
                        <input type="hidden" name="action" value="users-view">
                        <div class="input-group">
                            <input type="text" name="q" class="form-control rounded-start-pill border-end-0" 
                                   placeholder="<?php echo htmlspecialchars($translations['SEARCH_USER_PLACEHOLDER'] ?? 'Search by username...'); ?>" 
                                   value="<?php echo htmlspecialchars($search); ?>">
                            <button class="btn btn-outline-secondary rounded-end-pill border-start-0 bg-white text-secondary" type="submit">
                                <i class="fa-thin fa-magnifying-glass"></i>
                            </button>
                        </div>
                    </form>
                </div>
                
                <div class="card-body p-0">
                    <?php if (empty($users)): ?>
                        <div class="text-center py-5">
                            <i class="fa-thin fa-users-slash text-muted mb-3" style="font-size: 4rem;"></i>
                            <p class="text-secondary mb-0">No users found.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">ID</th>
                                        <th><?php echo htmlspecialchars($translations['USERNAME'] ?? 'Username'); ?></th>
                                        <th><?php echo htmlspecialchars($translations['CREATED_AT'] ?? 'Created At'); ?></th>
                                        <th class="text-end pe-4"><?php echo htmlspecialchars($translations['ACTIONS'] ?? 'Actions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $u): ?>
                                        <tr>
                                            <td class="ps-4 text-secondary small">#<?php echo htmlspecialchars($u['id']); ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-info-subtle text-info rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                        <i class="fa-thin fa-user fs-5"></i>
                                                    </div>
                                                    <div>
                                                        <span class="fw-bold text-dark d-block">
                                                            <?php echo htmlspecialchars($u['username']); ?>
                                                            <?php if (strtolower($u['username']) === strtolower($_SESSION['username'])): ?>
                                                                <span class="badge bg-success-subtle text-success fw-semibold ms-1 small rounded-pill">You</span>
                                                            <?php endif; ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="text-secondary small">
                                                    <?php echo htmlspecialchars($u['created_at']); ?>
                                                </span>
                                            </td>
                                            <td class="text-end pe-4">
                                                <div class="d-flex justify-content-end gap-2">
                                                    <!-- Edit Button triggers change password modal -->
                                                    <button type="button" 
                                                            class="btn btn-outline-primary btn-sm rounded-pill px-3 change-pw-btn"
                                                            data-username="<?php echo htmlspecialchars($u['username']); ?>">
                                                        <i class="fa-thin fa-key me-1"></i> <?php echo htmlspecialchars($translations['CHANGE_PASSWORD'] ?? 'Change Password'); ?>
                                                    </button>
                                                    
                                                    <!-- Delete Form -->
                                                    <?php if (strtolower($u['username']) !== strtolower($_SESSION['username'])): ?>
                                                        <form method="post" class="d-inline delete-user-form">
                                                            <input type="hidden" name="form_action" value="delete_user">
                                                            <input type="hidden" name="username" value="<?php echo htmlspecialchars($u['username']); ?>">
                                                            <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3 delete-user-btn">
                                                                <i class="fa-thin fa-trash me-1"></i> <?php echo htmlspecialchars($translations['DELETE_USER'] ?? 'Delete'); ?>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Right Column: Add User Form -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white py-3 border-light">
                    <h5 class="m-0 fw-bold text-dark d-flex align-items-center">
                        <i class="fa-thin fa-user-plus text-success me-2 fs-4"></i>
                        <?php echo htmlspecialchars($translations['USER_ADD'] ?? 'Register New User'); ?>
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form method="post">
                        <input type="hidden" name="form_action" value="create_user">
                        
                        <div class="mb-3">
                            <label for="username" class="form-label fw-semibold text-secondary small"><?php echo htmlspecialchars($translations['USERNAME'] ?? 'Username'); ?></label>
                            <input type="text" class="form-control rounded-3" id="username" name="username" required placeholder="Choose a username">
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold text-secondary small"><?php echo htmlspecialchars($translations['PASSWORD'] ?? 'Password'); ?></label>
                            <input type="password" class="form-control rounded-3" id="password" name="password" required placeholder="Enter password">
                        </div>

                        <div class="mb-4">
                            <label for="confirm" class="form-label fw-semibold text-secondary small"><?php echo htmlspecialchars($translations['CONFIRM_PASSWORD'] ?? 'Confirm Password'); ?></label>
                            <input type="password" class="form-control rounded-3" id="confirm" name="confirm" required placeholder="Confirm password">
                        </div>
                        
                        <button type="submit" class="btn btn-success w-100 py-2.5 rounded-pill fw-semibold shadow-sm">
                            <i class="fa-thin fa-user-plus me-1"></i> <?php echo htmlspecialchars($translations['USER_ADD'] ?? 'Register User'); ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Dialog: Change Password -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-light py-3">
                <h5 class="modal-title fw-bold text-dark" id="changePasswordModalLabel">
                    <i class="fa-thin fa-key text-primary me-2"></i>
                    <?php echo htmlspecialchars($translations['CHANGE_PASSWORD'] ?? 'Change Password'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body p-4">
                    <input type="hidden" name="form_action" value="update_user">
                    <input type="hidden" name="username" id="modal-username">
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small"><?php echo htmlspecialchars($translations['USERNAME'] ?? 'Username'); ?></label>
                        <input type="text" class="form-control rounded-3 bg-light" id="modal-display-username" readonly>
                    </div>
                    
                    <div class="mb-2">
                        <label for="modal-password" class="form-label fw-semibold text-secondary small"><?php echo htmlspecialchars($translations['PASSWORD'] ?? 'New Password'); ?></label>
                        <input type="password" class="form-control rounded-3" id="modal-password" name="password" required placeholder="Enter new password">
                    </div>
                </div>
                <div class="modal-footer border-light py-3">
                    <button type="button" class="btn btn-link text-secondary text-decoration-none rounded-pill px-4" data-bs-dismiss="modal">
                        <?php echo htmlspecialchars($translations['CANCEL'] ?? 'Cancel'); ?>
                    </button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm">
                        <i class="fa-thin fa-check me-1"></i> <?php echo htmlspecialchars($translations['UPDATE'] ?? 'Update'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Modal handling
    var changePasswordModalEl = document.getElementById('changePasswordModal');
    var changePasswordModal = new bootstrap.Modal(changePasswordModalEl);
    
    document.querySelectorAll('.change-pw-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var username = this.getAttribute('data-username');
            
            document.getElementById('modal-username').value = username;
            document.getElementById('modal-display-username').value = username;
            document.getElementById('modal-password').value = '';
            
            changePasswordModal.show();
        });
    });

    // Delete user confirm using SweetAlert2
    document.querySelectorAll('.delete-user-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var form = this.closest('form');
            var username = form.querySelector('input[name="username"]').value;
            
            Swal.fire({
                title: '<?php echo htmlspecialchars($translations['DELETE_CONFIRM_TITLE'] ?? 'Are you sure?'); ?>',
                text: '<?php echo htmlspecialchars($translations['DELETE_CONFIRM_TEXT'] ?? 'This will permanently delete this user.'); ?>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<?php echo htmlspecialchars($translations['YES_DELETE'] ?? 'Yes, delete it!'); ?>',
                cancelButtonText: '<?php echo htmlspecialchars($translations['CANCEL'] ?? 'Cancel'); ?>'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
});
</script>
