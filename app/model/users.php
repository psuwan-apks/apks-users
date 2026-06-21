<?php
global $config;

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure user is logged in
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    $current_url = 'index.php?' . $_SERVER['QUERY_STRING'];
    header('Location: ./index.php?page=user&action=provider-login&redirect=' . urlencode($current_url));
    exit;
}

// Ensure user is admin
if (strtolower($_SESSION['username'] ?? '') !== 'admin') {
    header('HTTP/1.0 404 Not Found');
    $view = $config['PATH_TO_VIEW'] . '404.php';
    return;
}

// Include User model
require_once APPLICATION_PATH . DS . 'model' . DS . 'user.php';

$error = '';
$success = '';

$ACT2PROCESS = get("action", "users-view");

switch ($ACT2PROCESS):
    case "users-view":
    default:
        // Handle POST requests
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $form_action = get('form_action');

            if ($form_action === 'create_user') {
                $username = trim($_POST['username'] ?? '');
                $password = $_POST['password'] ?? '';
                $confirm = $_POST['confirm'] ?? '';

                if ($username === '' || $password === '') {
                    $error = 'Username and password are required.';
                } elseif ($password !== $confirm) {
                    $error = 'Passwords do not match.';
                } else {
                    if (User::createUser($username, $password)) {
                        $success = 'User registered successfully!';
                        log_event('user_admin_create', 'success', 'Admin created user: ' . $username, $_SESSION['username']);
                    } else {
                        $error = 'Username already exists.';
                        log_event('user_admin_create', 'failure', 'Admin failed to create user: ' . $username . ' (Username already exists)', $_SESSION['username']);
                    }
                }
            } elseif ($form_action === 'update_user') {
                $username = trim($_POST['username'] ?? '');
                $password = $_POST['password'] ?? '';

                if ($username === '' || $password === '') {
                    $error = 'Username and password are required.';
                } else {
                    if (User::updatePassword($username, $password)) {
                        $success = 'Password updated successfully.';
                        log_event('user_admin_update', 'success', 'Admin updated password for user: ' . $username, $_SESSION['username']);
                    } else {
                        $error = 'Failed to update password.';
                        log_event('user_admin_update', 'failure', 'Admin failed to update password for user: ' . $username, $_SESSION['username']);
                    }
                }
            } elseif ($form_action === 'delete_user') {
                $username = trim($_POST['username'] ?? '');

                if ($username === '') {
                    $error = 'Username is required.';
                } elseif (strtolower($username) === strtolower($_SESSION['username'])) {
                    $error = 'You cannot delete your own logged-in account!';
                    log_event('user_admin_delete', 'failure', 'Admin attempted to delete their own account', $_SESSION['username']);
                } else {
                    if (User::deleteUser($username)) {
                        $success = 'User deleted successfully.';
                        log_event('user_admin_delete', 'success', 'Admin deleted user: ' . $username, $_SESSION['username']);
                    } else {
                        $error = 'Failed to delete user or user not found.';
                        log_event('user_admin_delete', 'failure', 'Admin failed to delete user: ' . $username, $_SESSION['username']);
                    }
                }
            }
        }

        // Fetch users
        $search = get('q');
        $users = User::getAllUsers($search);

        $view = $config['PATH_TO_VIEW'] . 'users.php';
        break;
endswitch;
