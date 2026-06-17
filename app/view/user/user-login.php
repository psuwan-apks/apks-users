<?php
// SSO Initiator - Redirects to the OAuth2 Authorization endpoint
// This replaces the old direct login form. Actual credentials are entered at provider-login.php.

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// If the user is already logged in, redirect to the dashboard
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']) {
    header('Location: ./index.php');
    exit;
}

// Build the OAuth2 authorization URL for the first-party client
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

$authorize_url = './index.php?' . http_build_query([
    'page' => 'oauth',
    'action' => 'authorize',
    'client_id' => 'apks-users-client',
    'redirect_uri' => 'http://localhost:8000/index.php?page=user&action=oauth-callback',
    'response_type' => 'code',
    'scope' => 'profile',
    'state' => $state
]);

// Show a brief interstitial before redirecting (to avoid flash-of-content issues)
$error = $_REQUEST['error'] ?? '';
?>

<style>
    .login-section {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: calc(100vh - 200px);
        padding: 2rem 1rem;
    }

    .login-card {
        width: 100%;
        max-width: 440px;
        background: #ffffff;
        border-radius: 20px;
        box-shadow:
            0 4px 6px -1px rgba(0, 0, 0, 0.05),
            0 20px 50px -12px rgba(0, 0, 0, 0.12);
        overflow: hidden;
        animation: cardSlideUp 0.5s cubic-bezier(0.16, 1, 0.3, 1);
    }

    @keyframes cardSlideUp {
        from { opacity: 0; transform: translateY(24px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .login-card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 2.25rem 2rem 2rem;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .login-card-header::before {
        content: '';
        position: absolute;
        top: -40%;
        left: -20%;
        width: 140%;
        height: 140%;
        background: radial-gradient(circle at 30% 70%, rgba(255,255,255,0.08) 0%, transparent 60%);
        pointer-events: none;
    }

    .login-brand-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 68px;
        height: 68px;
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border-radius: 18px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        margin-bottom: 1rem;
        position: relative;
        z-index: 1;
    }

    .login-card-header h2 {
        color: #fff;
        font-size: 1.4rem;
        font-weight: 700;
        margin: 0 0 0.35rem;
        position: relative;
        z-index: 1;
    }

    .login-card-header p {
        color: rgba(255, 255, 255, 0.7);
        font-size: 0.85rem;
        margin: 0;
        position: relative;
        z-index: 1;
    }

    .login-card-body {
        padding: 2rem 2rem 2.25rem;
        text-align: center;
    }

    /* Error styles */
    .login-error {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        background: #fef2f2;
        border: 1px solid #fecaca;
        border-radius: 12px;
        padding: 0.75rem 1rem;
        margin-bottom: 1.25rem;
        color: #dc2626;
        font-size: 0.875rem;
        font-weight: 500;
        text-align: left;
        animation: shakeError 0.4s ease-in-out;
    }

    @keyframes shakeError {
        0%, 100% { transform: translateX(0); }
        20%, 60% { transform: translateX(-6px); }
        40%, 80% { transform: translateX(6px); }
    }

    .login-error i { font-size: 1rem; flex-shrink: 0; }

    /* Spinner */
    .sso-spinner {
        width: 48px;
        height: 48px;
        border: 3px solid #e2e8f0;
        border-top: 3px solid #667eea;
        border-radius: 50%;
        animation: spinRotate 0.8s linear infinite;
        margin: 0 auto 1.25rem;
    }

    @keyframes spinRotate {
        to { transform: rotate(360deg); }
    }

    .sso-status-text {
        color: #64748b;
        font-size: 0.95rem;
        margin-bottom: 0.5rem;
    }

    .sso-sub-text {
        color: #94a3b8;
        font-size: 0.8rem;
    }

    .sso-retry-link {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        margin-top: 1rem;
        padding: 0.65rem 1.5rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #fff;
        text-decoration: none;
        border-radius: 10px;
        font-size: 0.9rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .sso-retry-link:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px -5px rgba(102, 126, 234, 0.4);
        color: #fff;
    }

    @media (max-width: 480px) {
        .login-card { border-radius: 16px; margin: 0 0.25rem; }
        .login-card-header { padding: 1.75rem 1.5rem 1.5rem; }
        .login-card-body { padding: 1.5rem; }
    }
</style>

<section class="login-section">
    <div class="login-card">
        <!-- Header -->
        <div class="login-card-header">
            <div class="login-brand-icon">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                    <path d="M2 17l10 5 10-5"></path>
                    <path d="M2 12l10 5 10-5"></path>
                </svg>
            </div>
            <h2>APKS Authentication</h2>
            <p>Single Sign-On Portal</p>
        </div>

        <!-- Body -->
        <div class="login-card-body">
            <?php if (!empty($error)): ?>
                <div class="login-error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
                <p class="sso-status-text">Authentication could not be completed.</p>
                <a href="<?php echo htmlspecialchars($authorize_url); ?>" class="sso-retry-link" id="ssoRetryLink">
                    <i class="fa-thin fa-rotate-right"></i>
                    Try Again
                </a>
            <?php else: ?>
                <div class="sso-spinner"></div>
                <p class="sso-status-text">Redirecting to authentication provider...</p>
                <p class="sso-sub-text">You will be redirected automatically</p>

                <script>
                    window.location.href = <?php echo json_encode($authorize_url); ?>;
                </script>
                <noscript>
                    <a href="<?php echo htmlspecialchars($authorize_url); ?>" class="sso-retry-link" id="ssoManualLink">
                        <i class="fa-thin fa-arrow-right"></i>
                        Click here to continue
                    </a>
                </noscript>
            <?php endif; ?>
        </div>
    </div>
</section>
