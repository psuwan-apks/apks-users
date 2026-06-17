<?php
/** @var string $error */
/** @var string $success */
$clients = OAuthProvider::getClients();
?>
<div class="container py-4">
    <div class="row">
        <!-- Main Panel: Client List -->
        <div class="col-lg-8 mb-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white py-3 border-light d-flex justify-content-between align-items-center">
                    <h5 class="m-0 fw-bold text-dark d-flex align-items-center">
                        <i class="fa-thin fa-cubes text-info me-2 fs-4"></i>
                        Registered Applications
                    </h5>
                    <span class="badge bg-info-subtle text-info fw-semibold px-2.5 py-1.5 rounded-pill">
                        <?php echo count($clients); ?> App(s)
                    </span>
                </div>
                
                <div class="card-body p-0">
                    <?php if (empty($clients)): ?>
                        <div class="text-center py-5">
                            <i class="fa-thin fa-folder-open text-muted mb-3" style="font-size: 4rem;"></i>
                            <p class="text-secondary mb-0">No client applications registered yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Application Name</th>
                                        <th>Client Credentials</th>
                                        <th>Redirect URI</th>
                                        <th class="text-end pe-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($clients as $c): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 42px; height: 42px;">
                                                        <i class="fa-thin fa-server fs-5"></i>
                                                    </div>
                                                    <div>
                                                        <span class="fw-bold text-dark d-block"><?php echo htmlspecialchars($c['name']); ?></span>
                                                        <span class="text-muted small">Scope: <?php echo htmlspecialchars($c['scope'] ?? 'profile'); ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column gap-1">
                                                    <div class="d-flex align-items-center gap-1">
                                                        <code class="small bg-light px-2 py-0.5 rounded border text-secondary">ID: <?php echo htmlspecialchars($c['client_id']); ?></code>
                                                        <button class="btn btn-link p-0 text-muted copy-btn" data-clipboard="<?php echo htmlspecialchars($c['client_id']); ?>" title="Copy ID">
                                                            <i class="fa-thin fa-copy small"></i>
                                                        </button>
                                                    </div>
                                                    
                                                    <div class="d-flex align-items-center gap-1">
                                                        <code class="small bg-light px-2 py-0.5 rounded border text-secondary secret-field" data-secret="<?php echo htmlspecialchars($c['client_secret']); ?>">Secret: ••••••••••</code>
                                                        <button class="btn btn-link p-0 text-muted toggle-secret-btn" title="Show Secret">
                                                            <i class="fa-thin fa-eye small"></i>
                                                        </button>
                                                        <button class="btn btn-link p-0 text-muted copy-btn" data-clipboard="<?php echo htmlspecialchars($c['client_secret']); ?>" title="Copy Secret">
                                                            <i class="fa-thin fa-copy small"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </td>
                                            <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                <span class="text-secondary small" title="<?php echo htmlspecialchars($c['redirect_uri']); ?>">
                                                    <?php echo htmlspecialchars($c['redirect_uri']); ?>
                                                </span>
                                            </td>
                                            <td class="text-end pe-4">
                                                <form method="post" class="d-inline delete-client-form">
                                                    <input type="hidden" name="form_action" value="delete_client">
                                                    <input type="hidden" name="client_id" value="<?php echo htmlspecialchars($c['client_id']); ?>">
                                                    <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3 delete-client-btn">
                                                        <i class="fa-thin fa-trash me-1"></i> Delete
                                                    </button>
                                                </form>
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
        
        <!-- Sidebar: Add Client -->
        <div class="col-lg-4">
            <?php if ($error): ?>
                <script>
                    window.addEventListener('DOMContentLoaded', () => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
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
                            title: 'Success',
                            text: <?php echo json_encode($success); ?>,
                            confirmButtonColor: '#1abc9c',
                            timer: 3000,
                            timerProgressBar: true
                        });
                    });
                </script>
            <?php endif; ?>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white py-3 border-light">
                    <h5 class="m-0 fw-bold text-dark d-flex align-items-center">
                        <i class="fa-thin fa-cube-projection text-success me-2 fs-4"></i>
                        Register New Client
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form method="post">
                        <input type="hidden" name="form_action" value="create_client">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold text-secondary small">Application Name</label>
                            <input type="text" class="form-control rounded-3" id="name" name="name" required placeholder="e.g. My External Portal">
                        </div>
                        
                        <div class="mb-3">
                            <label for="redirect_uri" class="form-label fw-semibold text-secondary small">Redirect Callback URI</label>
                            <input type="url" class="form-control rounded-3" id="redirect_uri" name="redirect_uri" required placeholder="http://localhost:8000/oauth-callback-demo.php">
                            <div class="form-text text-muted small">Must be a valid absolute HTTP or HTTPS endpoint.</div>
                        </div>

                        <div class="mb-4">
                            <label for="scope" class="form-label fw-semibold text-secondary small">Requested Scopes</label>
                            <select class="form-select rounded-3 select2" id="scope" name="scope">
                                <option value="profile">profile (username read-only)</option>
                                <option value="profile email">profile email (username & email)</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-success w-100 py-2.5 rounded-pill fw-semibold shadow-sm">
                            <i class="fa-thin fa-circle-plus me-1"></i> Register Application
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Secret Toggler
    document.querySelectorAll('.toggle-secret-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var codeField = this.parentNode.querySelector('.secret-field');
            var rawSecret = codeField.getAttribute('data-secret');
            var icon = this.querySelector('i');
            
            if (codeField.textContent.indexOf('••••••••••') !== -1) {
                codeField.textContent = 'Secret: ' + rawSecret;
                icon.className = 'fa-thin fa-eye-slash small';
                this.title = 'Hide Secret';
            } else {
                codeField.textContent = 'Secret: ••••••••••';
                icon.className = 'fa-thin fa-eye small';
                this.title = 'Show Secret';
            }
        });
    });

    // Copying to Clipboard
    document.querySelectorAll('.copy-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var textToCopy = this.getAttribute('data-clipboard');
            navigator.clipboard.writeText(textToCopy).then(function() {
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true
                });
                Toast.fire({
                    icon: 'success',
                    title: 'Copied to clipboard!'
                });
            }, function(err) {
                console.error('Could not copy text: ', err);
            });
        });
    });

    // Delete Client Confirmation using SweetAlert2
    document.querySelectorAll('.delete-client-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var form = this.closest('form');
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this client application deletion!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
});
</script>
