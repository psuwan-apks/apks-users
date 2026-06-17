<?php
/** @var string $error */
?>
<div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="card-header bg-danger text-white py-3 px-4">
                    <h5 class="m-0 d-flex align-items-center">
                        <i class="fa-thin fa-triangle-exclamation me-2 fs-4"></i>
                        OAuth 2.0 Error
                    </h5>
                </div>
                <div class="card-body p-4 text-center">
                    <div class="mb-4">
                        <i class="fa-thin fa-shield-slash text-danger" style="font-size: 5rem;"></i>
                    </div>
                    <h4 class="card-title text-dark mb-3">Authorization Request Failed</h4>
                    <p class="card-text text-muted mb-4 fs-5">
                        <?php echo htmlspecialchars($error ?? 'An unknown protocol error occurred.'); ?>
                    </p>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="index.php" class="btn btn-outline-secondary px-4 py-2 rounded-pill">
                            <i class="fa-thin fa-house me-1"></i> Dashboard Home
                        </a>
                    </div>
                </div>
            </div>
