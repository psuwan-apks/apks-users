<?php
// Display all error for PHP in JSON format
ini_set('display_errors', 0); // Disable HTML error output to avoid corrupting JSON
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

const DS = DIRECTORY_SEPARATOR;
$dir_root = realpath(dirname(__FILE__, 2));
$dir_app = $dir_root . DS . 'app';
defined('APPLICATION_PATH') || define('APPLICATION_PATH', $dir_app);

require_once APPLICATION_PATH . DS . 'config' . DS . 'config.php';
require_once APPLICATION_PATH . DS . 'model' . DS . 'oauth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'invalid_request', 'error_description' => 'Only POST requests are allowed.']);
    exit;
}

// Client Authentication: check Basic Auth first, then POST params
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
    $client_id = $_POST['client_id'] ?? '';
    $client_secret = $_POST['client_secret'] ?? '';
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

// Validate Grant Type
$grant_type = $_POST['grant_type'] ?? '';
if ($grant_type !== 'authorization_code') {
    http_response_code(400);
    echo json_encode(['error' => 'unsupported_grant_type', 'error_description' => 'Only authorization_code grant type is supported.']);
    exit;
}

$code = $_POST['code'] ?? '';
$redirect_uri = $_POST['redirect_uri'] ?? '';

if (empty($code) || empty($redirect_uri)) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid_request', 'error_description' => 'Missing code or redirect_uri parameters.']);
    exit;
}

// Verify Code (this also invalidates the code since it is single-use)
$codeInfo = OAuthProvider::verifyAuthCode($client_id, $code, $redirect_uri);
if (!$codeInfo) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid_grant', 'error_description' => 'The authorization code is invalid, expired, or redirect_uri mismatch.']);
    exit;
}

// Create Access Token
$tokenResponse = OAuthProvider::createAccessToken($client_id, $codeInfo['username'], $codeInfo['scope']);

echo json_encode($tokenResponse);
exit;
