<?php
// Display errors in JSON format, avoiding HTML output
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

const DS = DIRECTORY_SEPARATOR;
$dir_root = realpath(dirname(__FILE__, 2));
$dir_app = $dir_root . DS . 'app';
defined('APPLICATION_PATH') || define('APPLICATION_PATH', $dir_app);

require_once APPLICATION_PATH . DS . 'config' . DS . 'config.php';
require_once APPLICATION_PATH . DS . 'model' . DS . 'oauth.php';
require_once APPLICATION_PATH . DS . 'model' . DS . 'user.php';

// Client Authentication: check Basic Auth first, then request parameters
$client_id = '';
$client_secret = '';

if (isset($_SERVER['PHP_AUTH_USER'])) {
    $client_id = $_SERVER['PHP_AUTH_USER'];
    $client_secret = $_SERVER['PHP_AUTH_PW'] ?? '';
} elseif (isset($_SERVER['HTTP_AUTHORIZATION']) && preg_match('/Basic\s+(.*)$/i', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
    $decoded = base64_decode($matches[1]);
    if ($decoded && strpos($decoded, ':') !== false) {
        list($client_id, $client_secret) = explode(':', $decoded, 2);
    }
} else {
    // Check if JSON request body contains credentials
    $rawInput = file_get_contents('php://input');
    $jsonData = json_decode($rawInput, true) ?: [];
    
    $client_id = $_REQUEST['client_id'] ?? $jsonData['client_id'] ?? '';
    $client_secret = $_REQUEST['client_secret'] ?? $jsonData['client_secret'] ?? '';
}

if (empty($client_id)) {
    http_response_code(401);
    echo json_encode(['error' => 'invalid_client', 'error_description' => 'Client credentials are required.']);
    exit;
}

$client = OAuthProvider::findClient($client_id);
if (!$client || $client['client_secret'] !== $client_secret) {
    http_response_code(401);
    echo json_encode(['error' => 'invalid_client', 'error_description' => 'Client authentication failed.']);
    exit;
}

// Log API access
log_event('api_users_access', 'success', 'Authenticated API request from client: ' . $client_id, $client_id);

// Read inputs from request or JSON body
$rawInput = file_get_contents('php://input');
$jsonData = json_decode($rawInput, true) ?: [];

$method = $_SERVER['REQUEST_METHOD'];
$action = $_REQUEST['action'] ?? $jsonData['action'] ?? '';

// Map request methods to action defaults if not set
if (empty($action)) {
    if ($method === 'GET') {
        $action = 'get';
    } elseif ($method === 'POST') {
        $action = 'create';
    } elseif ($method === 'PUT' || $method === 'PATCH') {
        $action = 'update';
    } elseif ($method === 'DELETE') {
        $action = 'delete';
    }
}

switch (strtolower($action)) {
    case 'get':
    case 'list':
        $username = $_GET['username'] ?? $jsonData['username'] ?? '';
        
        if (!empty($username)) {
            $user = User::findByUsername($username);
            if ($user) {
                echo json_encode([
                    'status' => 'success',
                    'user' => [
                        'id' => (int)$user['id'],
                        'username' => $user['username'],
                        'created_at' => $user['created_at']
                    ]
                ]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'not_found', 'error_description' => 'User not found.']);
            }
        } else {
            $users = User::getAllUsers($_GET['q'] ?? '');
            // Format list cleanly
            $formattedUsers = [];
            foreach ($users as $u) {
                $formattedUsers[] = [
                    'id' => (int)$u['id'],
                    'username' => $u['username'],
                    'application' => $u['source'] ?? 'default_app',
                    'created_at' => $u['created_at']
                ];
            }
            echo json_encode([
                'status' => 'success',
                'users' => $formattedUsers
            ]);
        }
        break;

    case 'create':
        $username = trim($_REQUEST['username'] ?? $jsonData['username'] ?? '');
        $password = $_REQUEST['password'] ?? $jsonData['password'] ?? '';
        $application = trim($_REQUEST['application'] ?? $jsonData['application'] ?? 'default_app');
        
        if (empty($username) || empty($password)) {
            http_response_code(400);
            echo json_encode(['error' => 'invalid_request', 'error_description' => 'Username and password are required.']);
            break;
        }
        
        // Check if user already exists
        if (User::findByUsername($username) !== null) {
            http_response_code(409);
            echo json_encode(['error' => 'conflict', 'error_description' => 'Username already exists.']);
            break;
        }
        
        if (User::createUser($username, $password, $application)) {
            log_event('api_user_create', 'success', "User created via API by client {$client_id}: {$username}", $username);
            http_response_code(201);
            echo json_encode(['status' => 'success', 'message' => 'User created successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'server_error', 'error_description' => 'Failed to create user.']);
        }
        break;

    case 'update':
        $username = trim($_REQUEST['username'] ?? $jsonData['username'] ?? '');
        $password = $_REQUEST['password'] ?? $jsonData['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            http_response_code(400);
            echo json_encode(['error' => 'invalid_request', 'error_description' => 'Username and new password are required.']);
            break;
        }
        
        $user = User::findByUsername($username);
        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'not_found', 'error_description' => 'User not found.']);
            break;
        }
        
        if (User::updatePassword($username, $password)) {
            log_event('api_user_update', 'success', "User password updated via API by client {$client_id}: {$username}", $username);
            echo json_encode(['status' => 'success', 'message' => 'Password updated successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'server_error', 'error_description' => 'Failed to update password.']);
        }
        break;

    case 'delete':
        $username = trim($_REQUEST['username'] ?? $jsonData['username'] ?? '');
        
        if (empty($username)) {
            http_response_code(400);
            echo json_encode(['error' => 'invalid_request', 'error_description' => 'Username is required.']);
            break;
        }
        
        $user = User::findByUsername($username);
        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'not_found', 'error_description' => 'User not found.']);
            break;
        }
        
        if (User::deleteUser($username)) {
            log_event('api_user_delete', 'success', "User deleted via API by client {$client_id}: {$username}", $username);
            echo json_encode(['status' => 'success', 'message' => 'User deleted successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'server_error', 'error_description' => 'Failed to delete user.']);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'invalid_request', 'error_description' => 'Unsupported or invalid action.']);
        break;
}
exit;
