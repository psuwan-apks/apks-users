<?php
class User {
    // Ensure default users exist in the database and migrate JSON users if any
    public static function init(): void {
        try {
            $pdo = db_connected();
            
            // 1. Seed default users if table is empty
            $stmt = $pdo->query("SELECT COUNT(*) FROM `tbl4users_users`");
            $count = $stmt->fetchColumn();
            
            if ($count == 0) {
                $defaultUsers = [
                    [
                        'username' => 'admin',
                        'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
                        'application' => 'default_app',
                    ],
                    [
                        'username' => 'user',
                        'password_hash' => password_hash('password', PASSWORD_DEFAULT),
                        'application' => 'default_app',
                    ]
                ];
                
                $stmtInsert = $pdo->prepare("INSERT INTO `tbl4users_users` (`username`, `password_hash`, `application`) VALUES (:username, :password_hash, :application)");
                foreach ($defaultUsers as $user) {
                    $stmtInsert->execute([
                        ':username' => $user['username'],
                        ':password_hash' => $user['password_hash'],
                        ':application' => $user['application']
                    ]);
                }
            } else {
                // Ensure 'user' (default) is also seeded if not exists
                $stmtUserCheck = $pdo->prepare("SELECT COUNT(*) FROM `tbl4users_users` WHERE LOWER(`username`) = LOWER('user')");
                $stmtUserCheck->execute();
                if ($stmtUserCheck->fetchColumn() == 0) {
                    $stmtInsert = $pdo->prepare("INSERT INTO `tbl4users_users` (`username`, `password_hash`) VALUES (:username, :password_hash)");
                    $stmtInsert->execute([
                        ':username' => 'user',
                        ':password_hash' => password_hash('password', PASSWORD_DEFAULT)
                    ]);
                }
            }
            
            // 2. Migrate existing users from JSON files into the database
            $jsonFiles = [
                __DIR__ . '/users.json',
                __DIR__ . '/../../../apks-erp/app/model/users.json',
                __DIR__ . '/../../../apks-web/app/model/users.json',
            ];
            
            $stmtExists = $pdo->prepare("SELECT COUNT(*) FROM `tbl4users_users` WHERE LOWER(`username`) = LOWER(:username)");
            $stmtInsert = $pdo->prepare("INSERT INTO `tbl4users_users` (`username`, `password_hash`) VALUES (:username, :password_hash)");
            
            foreach ($jsonFiles as $path) {
                if (file_exists($path)) {
                    $json = @file_get_contents($path);
                    $users = json_decode($json, true);
                    if (is_array($users)) {
                        foreach ($users as $u) {
                            if (isset($u['username'], $u['password_hash'])) {
                                $stmtExists->execute([':username' => $u['username']]);
                                if ($stmtExists->fetchColumn() == 0) {
                                    $stmtInsert->execute([
                                        ':username' => $u['username'],
                                        ':password_hash' => $u['password_hash']
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
        } catch (PDOException $e) {
            // Table might not exist yet, we will handle database creation separately
        }
    }

    // Find a user by username
    public static function findByUsername(string $username): ?array {
        self::init();
        try {
            $pdo = db_connected();
            $stmt = $pdo->prepare("SELECT * FROM `tbl4users_users` WHERE LOWER(`username`) = LOWER(:username)");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();
            if ($user) {
                $user['source'] = 'Database';
                return $user;
            }
        } catch (PDOException $e) {
            // Ignore DB exception
        }
        return null;
    }

    // Authenticate a user by username and password
    public static function authenticate(string $username, string $password): bool {
        $user = self::findByUsername($username);
        if ($user && password_verify($password, $user['password_hash'])) {
            return true;
        }
        return false;
    }

    // Create a new user with hashed password
    public static function createUser(string $username, string $password, string $application = 'default_app'): bool {
        self::init();
        // Prevent duplicate usernames across all sources
        if (self::findByUsername($username) !== null) {
            return false;
        }
        
        try {
            $pdo = db_connected();
            $stmt = $pdo->prepare("INSERT INTO `tbl4users_users` (`username`, `password_hash`, `application`) VALUES (:username, :password_hash, :application)");
            return $stmt->execute([
                ':username' => $username,
                ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
                ':application' => $application
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    // Update a user's password
    public static function updatePassword(string $username, string $password): bool {
        self::init();
        try {
            $pdo = db_connected();
            $stmt = $pdo->prepare("UPDATE `tbl4users_users` SET `password_hash` = :password_hash WHERE LOWER(`username`) = LOWER(:username)");
            $stmt->execute([
                ':username' => $username,
                ':password_hash' => password_hash($password, PASSWORD_DEFAULT)
            ]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    // Delete a user and clean up active authorization codes & access tokens
    public static function deleteUser(string $username): bool {
        self::init();
        try {
            $pdo = db_connected();
            
            // Delete associated authorization codes
            $stmtCodes = $pdo->prepare("DELETE FROM `tbl4users_oauth_codes` WHERE LOWER(`username`) = LOWER(:username)");
            $stmtCodes->execute([':username' => $username]);
            
            // Delete associated access tokens
            $stmtTokens = $pdo->prepare("DELETE FROM `tbl4users_oauth_tokens` WHERE LOWER(`username`) = LOWER(:username)");
            $stmtTokens->execute([':username' => $username]);

            // Delete the user record
            $stmt = $pdo->prepare("DELETE FROM `tbl4users_users` WHERE LOWER(`username`) = LOWER(:username)");
            $stmt->execute([':username' => $username]);
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    // Fetch all users, with optional search filter
    public static function getAllUsers(?string $searchQuery = null): array {
        self::init();
        $users = [];

        try {
            $pdo = db_connected();
            if ($searchQuery !== null && trim($searchQuery) !== '') {
                $stmt = $pdo->prepare("SELECT `id`, `username`, `application`, `created_at` FROM `tbl4users_users` WHERE `username` LIKE :search ORDER BY `id` DESC");
                $stmt->execute([':search' => '%' . $searchQuery . '%']);
            } else {
                $stmt = $pdo->query("SELECT `id`, `username`, `application`, `created_at` FROM `tbl4users_users` ORDER BY `id` DESC");
            }
            $dbUsers = $stmt->fetchAll() ?: [];
            foreach ($dbUsers as $u) {
                $users[] = [
                    'id' => (int)$u['id'],
                    'username' => $u['username'],
                    'created_at' => $u['created_at'],
                    'source' => !empty($u['application']) ? $u['application'] : 'Database'
                ];
            }
        } catch (PDOException $e) {
            // Ignore
        }

        return $users;
    }
}

if (isset($page) && $page === 'user') {
    global $config;

    $current_view = $config['PATH_TO_VIEW'].DS.'user'.DS;

    $ACT2PROCESS = get("action");

    switch ($ACT2PROCESS):
        default:
        case "user-login":
            // SSO Initiator - If the user is already logged in, redirect to dashboard
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']) {
                header('Location: ./index.php');
                exit;
            }
            $view = $current_view . "user-login.php";
            break;

        case "user-register":
            $view = $current_view . "user-register.php";
            break;

        case "provider-login":
            // Provider Login - Handle the credential check in the model (before layout renders HTML)
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $provider_error = '';
            $provider_redirect = $_REQUEST['redirect'] ?? '';

            // If already logged in, redirect back
            if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] && !empty($provider_redirect)) {
                $redirectUrl = $provider_redirect;
                if (preg_match('/^https?:\/\//i', $provider_redirect)) {
                    $host = parse_url($provider_redirect, PHP_URL_HOST);
                    if ($host !== $_SERVER['HTTP_HOST']) {
                        $redirectUrl = 'index.php';
                    }
                }
                header('Location: ' . $redirectUrl);
                exit;
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $username = trim($_POST['username'] ?? '');
                $password = $_POST['password'] ?? '';
                $provider_redirect = $_POST['redirect'] ?? '';

                if (User::authenticate($username, $password)) {
                    log_event('provider_login', 'success', 'User authenticated via provider: ' . $username, $username);
                    $_SESSION['loggedin'] = true;
                    $_SESSION['username'] = $username;
                    $_SESSION['USER'] = ['username' => $username];

                    // Redirect back to the OAuth flow (authorize endpoint)
                    $redirectUrl = 'index.php';
                    if (!empty($provider_redirect)) {
                        if (!preg_match('/^https?:\/\//i', $provider_redirect)) {
                            $redirectUrl = $provider_redirect;
                        } else {
                            $host = parse_url($provider_redirect, PHP_URL_HOST);
                            if ($host === $_SERVER['HTTP_HOST']) {
                                $redirectUrl = $provider_redirect;
                            }
                        }
                    }
                    header('Location: ' . $redirectUrl);
                    exit;
                } else {
                    log_event('provider_login', 'failure', 'Provider login failed for: ' . $username, $username);
                    $provider_error = 'Invalid username or password.';
                }
            }

            $view = $current_view . "provider-login.php";
            break;

        case "oauth-callback":
            // OAuth2 Authorization Code callback handler
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $code = get('code');
            $state = get('state');
            $error_param = get('error');

            if (!empty($error_param)) {
                header('Location: ./index.php?page=user&action=user-login&error=' . urlencode($error_param));
                exit;
            }

            if (empty($code)) {
                header('Location: ./index.php?page=user&action=user-login');
                exit;
            }

            // Exchange the authorization code for an access token using direct calls to OAuthProvider
            // to avoid deadlocking the single-threaded PHP built-in web server.
            require_once APPLICATION_PATH . DS . 'model' . DS . 'oauth.php';
            
            $redirect_uri = 'http://localhost:8000/index.php?page=user&action=oauth-callback';
            $client_id = 'apks-users-client';
            $client_secret = 'apks-users-secret';

            // 1. Verify client credentials (first-party)
            $client = OAuthProvider::findClient($client_id);
            if (!$client || $client['client_secret'] !== $client_secret) {
                log_event('oauth_callback', 'failure', 'First-party client validation failed');
                header('Location: ./index.php?page=user&action=user-login&error=invalid_client');
                exit;
            }

            // 2. Verify Code
            $codeInfo = OAuthProvider::verifyAuthCode($client_id, $code, $redirect_uri);
            if (!$codeInfo) {
                log_event('oauth_callback', 'failure', 'Invalid or expired authorization code');
                header('Location: ./index.php?page=user&action=user-login&error=invalid_grant');
                exit;
            }

            // 3. Create Access Token
            $tokenResponse = OAuthProvider::createAccessToken($client_id, $codeInfo['username'], $codeInfo['scope']);
            if (!isset($tokenResponse['access_token'])) {
                log_event('oauth_callback', 'failure', 'Failed to generate access token');
                header('Location: ./index.php?page=user&action=user-login&error=token_generation_failed');
                exit;
            }

            // 4. Verify Access Token to retrieve user info (equivalent to userinfo.php logic)
            $tokenInfo = OAuthProvider::verifyAccessToken($tokenResponse['access_token']);
            if (!$tokenInfo) {
                log_event('oauth_callback', 'failure', 'Access token verification failed');
                header('Location: ./index.php?page=user&action=user-login&error=token_verification_failed');
                exit;
            }

            $username = $tokenInfo['username'];

            // Establish the local session
            $_SESSION['loggedin'] = true;
            $_SESSION['username'] = $username;
            $_SESSION['USER'] = ['username' => $username];
            $_SESSION['oauth_access_token'] = $tokenResponse['access_token'];

            log_event('user_login', 'success', 'User logged in via OAuth SSO (Direct Exchange): ' . $username, $username);

            header('Location: ./index.php');
            exit;
            break;
            
    endswitch;
}
?>