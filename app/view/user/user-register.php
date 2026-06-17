<?php
// Session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../model/user.php';

$error = '';
$success = '';
$redirect = $_REQUEST['redirect'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username and password are required.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $created = User::createUser($username, $password);
        if ($created) {
            log_event('user_register', 'success', 'User registered successfully: ' . $username, $username);
            // Auto-login after registration
            $_SESSION['loggedin'] = true;
            $_SESSION['username'] = $username;
            $_SESSION['USER'] = ['username' => $username];
            $success = 'Registration successful. Redirecting...';
            
            $redirectUrl = 'index.php';
            if (!empty($redirect)) {
                if (!preg_match('/^https?:\/\//i', $redirect)) {
                    $redirectUrl = $redirect;
                } else {
                    $host = parse_url($redirect, PHP_URL_HOST);
                    if ($host === $_SERVER['HTTP_HOST']) {
                        $redirectUrl = $redirect;
                    }
                }
            }
            header('Location: ' . $redirectUrl);
            exit;
        } else {
            log_event('user_register', 'failure', 'Registration failed: Username already exists: ' . $username, $username);
            $error = 'Username already exists.';
        }
    }
}
?>

<style>
    /* ─── Register Page Styles ─── */
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
        background: linear-gradient(135deg, #1abc9c 0%, #16a085 100%);
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
        padding: 2rem 2rem 1.75rem;
    }

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
        animation: shakeError 0.4s ease-in-out;
    }

    .login-success {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-radius: 12px;
        padding: 0.75rem 1rem;
        margin-bottom: 1.25rem;
        color: #16a34a;
        font-size: 0.875rem;
        font-weight: 500;
    }

    @keyframes shakeError {
        0%, 100% { transform: translateX(0); }
        20%, 60% { transform: translateX(-6px); }
        40%, 80% { transform: translateX(6px); }
    }

    .login-error i, .login-success i { font-size: 1rem; flex-shrink: 0; }

    .login-form-group {
        margin-bottom: 1.25rem;
    }

    .login-form-group label {
        display: block;
        font-size: 0.8rem;
        font-weight: 600;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.4rem;
    }

    .login-form-group .input-wrapper {
        position: relative;
    }

    .login-form-group .input-wrapper i {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 1rem;
        transition: color 0.2s ease;
        pointer-events: none;
    }

    .login-form-group input {
        width: 100%;
        padding: 0.75rem 0.875rem 0.75rem 2.75rem;
        border: 1.5px solid #e2e8f0;
        border-radius: 12px;
        font-size: 0.95rem;
        color: #1e293b;
        background: #f8fafc;
        transition: all 0.25s ease;
        outline: none;
        font-family: inherit;
    }

    .login-form-group input:hover {
        border-color: #cbd5e1;
        background: #fff;
    }

    .login-form-group input:focus {
        border-color: #1abc9c;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(26, 188, 156, 0.12);
    }

    .login-form-group .input-wrapper:focus-within i {
        color: #1abc9c;
    }

    .login-form-group input::placeholder {
        color: #94a3b8;
        font-weight: 400;
    }

    .login-submit-btn {
        width: 100%;
        padding: 0.85rem 1.5rem;
        border: none;
        border-radius: 12px;
        background: linear-gradient(135deg, #1abc9c 0%, #16a085 100%);
        color: #fff;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        margin-top: 0.5rem;
        font-family: inherit;
        letter-spacing: 0.02em;
    }

    .login-submit-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
        transition: left 0.6s ease;
    }

    .login-submit-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px -5px rgba(26, 188, 156, 0.4);
    }

    .login-submit-btn:hover::before { left: 100%; }
    .login-submit-btn:active { transform: translateY(0); }

    .login-card-footer {
        text-align: center;
        padding: 0 2rem 1.75rem;
    }

    .login-card-footer a {
        color: #1abc9c;
        text-decoration: none;
        font-size: 0.875rem;
        font-weight: 500;
        transition: color 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }

    .login-card-footer a:hover { color: #16a085; }

    .login-divider {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin: 1.25rem 0;
        color: #94a3b8;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-weight: 500;
    }

    .login-divider::before,
    .login-divider::after {
        content: '';
        flex: 1;
        height: 1px;
        background: #e2e8f0;
    }

    @media (max-width: 480px) {
        .login-card { border-radius: 16px; margin: 0 0.25rem; }
        .login-card-header { padding: 1.75rem 1.5rem 1.5rem; }
        .login-card-body { padding: 1.5rem 1.5rem 1.25rem; }
        .login-card-footer { padding: 0 1.5rem 1.5rem; }
    }
</style>

<section class="login-section">
    <div class="login-card">
        <!-- Header -->
        <div class="login-card-header">
            <div class="login-brand-icon">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="8.5" cy="7" r="4"></circle>
                    <line x1="20" y1="8" x2="20" y2="14"></line>
                    <line x1="23" y1="11" x2="17" y2="11"></line>
                </svg>
            </div>
            <h2>Create Account</h2>
            <p>Join APKS to get started</p>
        </div>

        <!-- Body -->
        <div class="login-card-body">
            <?php if ($error): ?>
                <div class="login-error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php elseif ($success): ?>
                <div class="login-success">
                    <i class="fa-solid fa-circle-check"></i>
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>
            <?php endif; ?>

            <form method="post" action="" id="registerForm">
                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">

                <div class="login-form-group">
                    <label for="reg-username">Username</label>
                    <div class="input-wrapper">
                        <input type="text" name="username" id="reg-username"
                               required autofocus autocomplete="username"
                               placeholder="Choose a username">
                        <i class="fa-thin fa-user"></i>
                    </div>
                </div>

                <div class="login-form-group">
                    <label for="reg-password">Password</label>
                    <div class="input-wrapper">
                        <input type="password" name="password" id="reg-password"
                               required autocomplete="new-password"
                               placeholder="Create a password">
                        <i class="fa-thin fa-lock"></i>
                    </div>
                </div>

                <div class="login-form-group">
                    <label for="reg-confirm">Confirm Password</label>
                    <div class="input-wrapper">
                        <input type="password" name="confirm" id="reg-confirm"
                               required autocomplete="new-password"
                               placeholder="Confirm your password">
                        <i class="fa-thin fa-shield-check"></i>
                    </div>
                </div>

                <button type="submit" class="login-submit-btn" id="registerSubmitBtn">
                    <i class="fa-thin fa-user-plus" style="margin-right: 0.4rem;"></i>
                    Create Account
                </button>
            </form>

            <div class="login-divider">or</div>
        </div>

        <!-- Footer -->
        <div class="login-card-footer">
            <a href="./index.php?page=user&action=user-login" id="loginLink">
                <i class="fa-thin fa-right-to-bracket"></i>
                Already have an account? Sign In
            </a>
        </div>
    </div>
</section>
