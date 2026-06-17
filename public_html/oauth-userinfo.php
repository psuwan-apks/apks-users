<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

const DS = DIRECTORY_SEPARATOR;
$dir_root = realpath(dirname(__FILE__, 2));
$dir_app = $dir_root . DS . 'app';
defined('APPLICATION_PATH') || define('APPLICATION_PATH', $dir_app);

require_once APPLICATION_PATH . DS . 'config' . DS . 'config.php';
require_once APPLICATION_PATH . DS . 'model' . DS . 'oauth.php';

// Retrieve access token from Authorization header or GET/POST params
$accessToken = '';

if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    if (preg_match('/Bearer\s+(.*)$/i', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
        $accessToken = trim($matches[1]);
    }
}

if (empty($accessToken)) {
    $accessToken = $_REQUEST['access_token'] ?? '';
}

if (empty($accessToken)) {
    http_response_code(401);
    header('WWW-Authenticate: Bearer error="invalid_request", error_description="Access token is required."');
    echo json_encode(['error' => 'invalid_request', 'error_description' => 'Access token is required.']);
    exit;
}

// Verify token
$tokenInfo = OAuthProvider::verifyAccessToken($accessToken);
if (!$tokenInfo) {
    http_response_code(401);
    header('WWW-Authenticate: Bearer error="invalid_token", error_description="The access token is invalid or expired."');
    echo json_encode(['error' => 'invalid_token', 'error_description' => 'The access token is invalid or expired.']);
    exit;
}

$username = $tokenInfo['username'];
$scope = $tokenInfo['scope'] ?? 'profile';

// Build UserInfo response
$response = [
    'sub' => $username,
    'username' => $username,
    'scope' => $scope
];

// If email scope was requested, provide mock email matching user
if (strpos($scope, 'email') !== false) {
    $response['email'] = $username . '@internal.ecosystem';
    $response['email_verified'] = true;
}

echo json_encode($response);
exit;
