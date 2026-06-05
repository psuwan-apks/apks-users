<?php
// Session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../model/user.php';

$error = '';
$success = '';
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
            header('Location: ../../index.php');
            exit;
        } else {
            log_event('user_register', 'failure', 'Registration failed: Username already exists: ' . $username, $username);
            $error = 'Username already exists.';
        }
    }
}
?>
<div class="login-container">
    <h2>Register</h2>
    <?php if ($error): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php elseif ($success): ?>
        <p class="success"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>
    <form method="post" action="">
        <label for="username">Username:</label>
        <input type="text" name="username" id="username" required>
        <label for="password">Password:</label>
        <input type="password" name="password" id="password" required>
        <label for="confirm">Confirm Password:</label>
        <input type="password" name="confirm" id="confirm" required>
        <button type="submit">Register</button>
    </form>
</div>
