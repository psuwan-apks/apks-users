<?php
// Session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../model/user.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (User::authenticate($username, $password)) {
        log_event('user_login', 'success', 'User logged in: ' . $username, $username);
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['USER'] = ['username' => $username];
        header('Location: ../../index.php');
        exit;
    } else {
        log_event('user_login', 'failure', 'Login failed for: ' . $username, $username);
        $error = 'Invalid username or password.';
    }
}
?>
<div class="login-container">
    <h2>Login</h2>
    <?php if ($error): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <form method="post" action="">
        <label for="username">Username:</label>
        <input type="text" name="username" id="username" required>
        <label for="password">Password:</label>
        <input type="password" name="password" id="password" required>
        <button type="submit">Login</button>
    </form>
</div>
