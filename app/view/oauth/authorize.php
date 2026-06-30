<?php
/** @var array $client */
/** @var string $scope */
/** @var string $state */
/** @var string $client_id */
/** @var string $redirect_uri */
/** @var string $response_type */
?>
<div class="card border-0 shadow-lg rounded-4 overflow-hidden" style="background-color: #fff;">
                <!-- Top Brand Header with Gradient -->
                <div class="py-4 px-4 text-center text-white" style="background: linear-gradient(135deg, #1abc9c, #16a085);">
                    <div class="d-inline-flex align-items-center justify-content-center bg-white rounded-circle shadow-sm mb-3" style="width: 70px; height: 70px;">
                        <i class="fa-thin fa-key text-success" style="font-size: 2.2rem;"></i>
                    </div>
                    <h4 class="mb-1 fw-bold">Sign in with APKS</h4>
                    <p class="mb-0 text-white-50 small">Central Authorization Service</p>
                </div>
                
                <!-- Consent Details -->
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <p class="text-secondary">
                            An application called <strong class="text-dark"><?php echo htmlspecialchars($client['name']); ?></strong> is requesting permission to access your account.
                        </p>
                    </div>

                    <!-- Permissions List -->
                    <div class="bg-light p-3 rounded-3 mb-4 border border-light-subtle">
                        <h6 class="text-dark fw-bold mb-3 d-flex align-items-center">
                            <i class="fa-thin fa-list-check text-primary me-2"></i>
                            Review Requested Access:
                        </h6>
                        <ul class="list-unstyled m-0">
                            <li class="d-flex align-items-start mb-2">
                                <i class="fa-thin fa-circle-check text-success me-2 mt-1"></i>
                                <div>
                                    <span class="fw-semibold text-dark d-block">Read-Only Profile Information</span>
                                    <small class="text-muted">Allows the application to see your username and verify your identity.</small>
                                </div>
                            </li>
                            <?php if (strpos($scope, 'email') !== false): ?>
                            <li class="d-flex align-items-start">
                                <i class="fa-thin fa-circle-check text-success me-2 mt-1"></i>
                                <div>
                                    <span class="fw-semibold text-dark d-block">Email Address</span>
                                    <small class="text-muted">Allows the application to see your registered email address.</small>
                                </div>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <p class="text-muted small text-center mb-4">
                        By approving, you authorize this application to retrieve details according to its integration terms. You can log out of the client app at any time.
                    </p>

                    <!-- Consent Action Form -->
                    <form method="post" class="d-flex flex-column gap-2">
                        <input type="hidden" name="decision" id="consentDecision" value="deny">
                        
                        <div class="form-check mb-2 d-flex justify-content-center align-items-center">
                            <input class="form-check-input me-2" type="checkbox" value="1" id="remember_consent" name="remember_consent" checked>
                            <label class="form-check-label text-muted small" for="remember_consent">
                                Remember my consent for this application
                            </label>
                        </div>
                        
                        <button type="button" class="btn btn-success py-2.5 rounded-pill fw-semibold shadow-sm" onclick="submitConsent('approve');">
                            <i class="fa-thin fa-badge-check me-1"></i> Approve Access
                        </button>
                        
                        <button type="button" class="btn btn-outline-danger py-2.5 rounded-pill fw-semibold" onclick="submitConsent('deny');">
                            <i class="fa-thin fa-circle-xmark me-1"></i> Deny
                        </button>
                    </form>
                </div>
                
                <!-- Logged in as Footer -->
                <div class="card-footer bg-light text-center py-3 border-0">
                    <span class="text-secondary small">
                        Logged in as: <strong class="text-dark"><i class="fa-thin fa-user-circle me-1"></i><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
                    </span>
                </div>
            </div>

<script>
function submitConsent(decision) {
    document.getElementById('consentDecision').value = decision;
    document.querySelector('form').submit();
}
</script>
