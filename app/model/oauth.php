<?php
class OAuthProvider {
    // Ensure clients exist in the database, seeding defaults if empty
    public static function init(): void {
        try {
            $pdo = db_connected();
            $stmt = $pdo->query("SELECT COUNT(*) FROM `tbl4users_oauth_clients`");
            $count = $stmt->fetchColumn();
            
            if ($count == 0) {
                $defaultClients = [
                    [
                        'client_id' => 'demo-client',
                        'client_secret' => 'demo-secret',
                        'name' => 'Demo Application',
                        'redirect_uri' => 'http://localhost:8000/oauth-callback-demo.php',
                        'scope' => 'profile',
                        'first_party' => 0
                    ],
                    [
                        'client_id' => 'apks-users-client',
                        'client_secret' => 'apks-users-secret',
                        'name' => 'APKS Users Portal (First-Party)',
                        'redirect_uri' => 'http://localhost:8000/index.php?page=user&action=oauth-callback',
                        'scope' => 'profile',
                        'first_party' => 1
                    ]
                ];
                
                $stmtInsert = $pdo->prepare("INSERT INTO `tbl4users_oauth_clients` (`client_id`, `client_secret`, `name`, `redirect_uri`, `scope`, `first_party`) VALUES (:client_id, :client_secret, :name, :redirect_uri, :scope, :first_party)");
                foreach ($defaultClients as $c) {
                    $stmtInsert->execute([
                        ':client_id' => $c['client_id'],
                        ':client_secret' => $c['client_secret'],
                        ':name' => $c['name'],
                        ':redirect_uri' => $c['redirect_uri'],
                        ':scope' => $c['scope'],
                        ':first_party' => $c['first_party']
                    ]);
                }
            } else {
                // Ensure the first-party client always exists
                $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM `tbl4users_oauth_clients` WHERE `client_id` = 'apks-users-client'");
                $stmtCheck->execute();
                if ($stmtCheck->fetchColumn() == 0) {
                    $stmtInsert = $pdo->prepare("INSERT INTO `tbl4users_oauth_clients` (`client_id`, `client_secret`, `name`, `redirect_uri`, `scope`, `first_party`) VALUES (:client_id, :client_secret, :name, :redirect_uri, :scope, :first_party)");
                    $stmtInsert->execute([
                        ':client_id' => 'apks-users-client',
                        ':client_secret' => 'apks-users-secret',
                        ':name' => 'APKS Users Portal (First-Party)',
                        ':redirect_uri' => 'http://localhost:8000/index.php?page=user&action=oauth-callback',
                        ':scope' => 'profile',
                        ':first_party' => 1
                    ]);
                }
            }
        } catch (PDOException $e) {
            // Table might not exist yet, we will handle database creation separately
        }
    }

    public static function getClients(): array {
        self::init();
        try {
            $pdo = db_connected();
            $stmt = $pdo->query("SELECT * FROM `tbl4users_oauth_clients` ORDER BY `created_at` DESC");
            return $stmt->fetchAll() ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

    public static function findClient(string $clientId): ?array {
        self::init();
        try {
            $pdo = db_connected();
            $stmt = $pdo->prepare("SELECT * FROM `tbl4users_oauth_clients` WHERE `client_id` = :client_id");
            $stmt->execute([':client_id' => $clientId]);
            $client = $stmt->fetch();
            return $client ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }

    public static function createClient(string $name, string $redirectUri, string $scope = 'profile'): array {
        self::init();
        $clientId = 'client_' . token_gen(16);
        $clientSecret = 'secret_' . token_gen(32);
        
        try {
            $pdo = db_connected();
            $stmt = $pdo->prepare("INSERT INTO `tbl4users_oauth_clients` (`client_id`, `client_secret`, `name`, `redirect_uri`, `scope`, `first_party`) VALUES (:client_id, :client_secret, :name, :redirect_uri, :scope, 0)");
            $stmt->execute([
                ':client_id' => $clientId,
                ':client_secret' => $clientSecret,
                ':name' => $name,
                ':redirect_uri' => $redirectUri,
                ':scope' => $scope
            ]);
            
            return [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'name' => $name,
                'redirect_uri' => $redirectUri,
                'scope' => $scope
            ];
        } catch (PDOException $e) {
            throw new Exception("Failed to register OAuth client: " . $e->getMessage());
        }
    }

    public static function deleteClient(string $clientId): bool {
        self::init();
        try {
            $pdo = db_connected();

            // Manually cascade-delete orphaned codes & tokens (no FK CASCADE anymore)
            $pdo->prepare("DELETE FROM `tbl4users_oauth_codes` WHERE `client_id` = :client_id")
                ->execute([':client_id' => $clientId]);
            $pdo->prepare("DELETE FROM `tbl4users_oauth_tokens` WHERE `client_id` = :client_id")
                ->execute([':client_id' => $clientId]);

            $stmt = $pdo->prepare("DELETE FROM `tbl4users_oauth_clients` WHERE `client_id` = :client_id");
            $stmt->execute([':client_id' => $clientId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    public static function createAuthCode(string $clientId, string $redirectUri, string $username, string $scope, string $state): string {
        self::init();
        $now = time();
        
        try {
            $pdo = db_connected();
            
            // Clean up expired codes
            $stmtClean = $pdo->prepare("DELETE FROM `tbl4users_oauth_codes` WHERE `expires_at` < :now");
            $stmtClean->execute([':now' => $now]);

            $code = 'code_' . token_gen(32);
            $expiresAt = $now + 300; // 5 minutes validity

            $stmtInsert = $pdo->prepare("INSERT INTO `tbl4users_oauth_codes` (`code`, `client_id`, `redirect_uri`, `username`, `scope`, `state`, `expires_at`) VALUES (:code, :client_id, :redirect_uri, :username, :scope, :state, :expires_at)");
            $stmtInsert->execute([
                ':code' => $code,
                ':client_id' => $clientId,
                ':redirect_uri' => $redirectUri,
                ':username' => $username,
                ':scope' => $scope,
                ':state' => $state,
                ':expires_at' => $expiresAt
            ]);

            return $code;
        } catch (PDOException $e) {
            throw new Exception("Failed to generate authorization code: " . $e->getMessage());
        }
    }

    public static function verifyAuthCode(string $clientId, string $code, string $redirectUri): ?array {
        self::init();
        $now = time();
        
        try {
            $pdo = db_connected();
            
            // Query the code
            $stmt = $pdo->prepare("SELECT * FROM `tbl4users_oauth_codes` WHERE `code` = :code AND `client_id` = :client_id");
            $stmt->execute([
                ':code' => $code,
                ':client_id' => $clientId
            ]);
            $item = $stmt->fetch();

            // Codes are single-use! Delete it regardless of validity.
            $stmtDelete = $pdo->prepare("DELETE FROM `tbl4users_oauth_codes` WHERE `code` = :code");
            $stmtDelete->execute([':code' => $code]);

            if ($item) {
                // Check expiry and redirect URI
                if ($item['expires_at'] > $now && $item['redirect_uri'] === $redirectUri) {
                    return $item;
                }
            }
            return null;
        } catch (PDOException $e) {
            return null;
        }
    }

    public static function createAccessToken(string $clientId, string $username, string $scope): array {
        self::init();
        $now = time();
        
        try {
            $pdo = db_connected();
            
            // Clean up expired tokens
            $stmtClean = $pdo->prepare("DELETE FROM `tbl4users_oauth_tokens` WHERE `expires_at` < :now");
            $stmtClean->execute([':now' => $now]);

            $accessToken = 'token_' . token_gen(40);
            $expiresIn = 3600; // 1 hour
            $expiresAt = $now + $expiresIn;

            $stmtInsert = $pdo->prepare("INSERT INTO `tbl4users_oauth_tokens` (`access_token`, `client_id`, `username`, `scope`, `expires_at`) VALUES (:access_token, :client_id, :username, :scope, :expires_at)");
            $stmtInsert->execute([
                ':access_token' => $accessToken,
                ':client_id' => $clientId,
                ':username' => $username,
                ':scope' => $scope,
                ':expires_at' => $expiresAt
            ]);

            return [
                'access_token' => $accessToken,
                'token_type' => 'Bearer',
                'expires_in' => $expiresIn,
                'scope' => $scope
            ];
        } catch (PDOException $e) {
            throw new Exception("Failed to generate access token: " . $e->getMessage());
        }
    }

    public static function verifyAccessToken(string $token): ?array {
        self::init();
        $now = time();
        
        try {
            $pdo = db_connected();
            $stmt = $pdo->prepare("SELECT * FROM `tbl4users_oauth_tokens` WHERE `access_token` = :token AND `expires_at` > :now");
            $stmt->execute([
                ':token' => $token,
                ':now' => $now
            ]);
            $item = $stmt->fetch();
            return $item ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }
}

if (isset($page) && $page === 'oauth') {
    global $config;

    $current_view = $config['PATH_TO_VIEW'] . 'oauth' . DS;

    $ACT2PROCESS = get("action");

    switch ($ACT2PROCESS):
        case "clients":
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
                $current_url = 'index.php?' . $_SERVER['QUERY_STRING'];
                header('Location: ./index.php?page=user&action=provider-login&redirect=' . urlencode($current_url));
                exit;
            }

            $error = '';
            $success = '';
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $form_action = get('form_action');
                if ($form_action === 'create_client') {
                    $name = trim($_POST['name'] ?? '');
                    $redirect_uri = trim($_POST['redirect_uri'] ?? '');
                    $scope = trim($_POST['scope'] ?? 'profile');

                    if ($name === '' || $redirect_uri === '') {
                        $error = 'Client name and Redirect URI are required.';
                    } elseif (!filter_var($redirect_uri, FILTER_VALIDATE_URL)) {
                        $error = 'Invalid Redirect URI format.';
                    } else {
                        $new_client = OAuthProvider::createClient($name, $redirect_uri, $scope);
                        $success = 'Client registered successfully!';
                        log_event('oauth_client_create', 'success', 'Client created: ' . $name . ' (' . $new_client['client_id'] . ')', $_SESSION['username']);
                    }
                } elseif ($form_action === 'delete_client') {
                    $client_id = $_POST['client_id'] ?? '';
                    if (OAuthProvider::deleteClient($client_id)) {
                        $success = 'Client application deleted successfully.';
                        log_event('oauth_client_delete', 'success', 'Client deleted: ' . $client_id, $_SESSION['username']);
                    } else {
                        $error = 'Failed to delete client or client not found.';
                    }
                }
            }

            $view = $config['PATH_TO_VIEW'] . 'oauth' . DS . 'clients.php';
            break;

        case "authorize":
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $client_id = get('client_id');
            $redirect_uri = get('redirect_uri');
            $response_type = get('response_type');
            $scope = get('scope', 'profile');
            $state = get('state');

            $client = OAuthProvider::findClient($client_id);
            if (!$client) {
                $error = 'OAuth error: Invalid Client ID.';
                $view = $config['PATH_TO_VIEW'] . 'oauth' . DS . 'error.php';
                break;
            }

            if (strcasecmp($client['redirect_uri'], $redirect_uri) !== 0) {
                $error = 'OAuth error: Redirect URI mismatch. Registered: ' . $client['redirect_uri'] . ', Requested: ' . $redirect_uri;
                $view = $config['PATH_TO_VIEW'] . 'oauth' . DS . 'error.php';
                break;
            }

            if ($response_type !== 'code') {
                $error = 'OAuth error: Unsupported response_type. Only response_type=code is supported.';
                $view = $config['PATH_TO_VIEW'] . 'oauth' . DS . 'error.php';
                break;
            }

            // Redirect unauthenticated users to the provider-login form (NOT user-login, to avoid SSO loops)
            if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
                $current_url = 'index.php?' . $_SERVER['QUERY_STRING'];
                header('Location: ./index.php?page=user&action=provider-login&redirect=' . urlencode($current_url));
                exit;
            }

            // --- First-Party Auto-Approval ---
            // If the requesting client is a first-party app, skip the consent screen entirely.
            if (!empty($client['first_party'])) {
                $code = OAuthProvider::createAuthCode($client_id, $redirect_uri, $_SESSION['username'], $scope, $state);
                $redirect_target = $redirect_uri . (strpos($redirect_uri, '?') === false ? '?' : '&') . 'code=' . urlencode($code);
                if (!empty($state)) {
                    $redirect_target .= '&state=' . urlencode($state);
                }
                log_event('oauth_authorize', 'auto-approved', 'First-party auto-approval for user ' . $_SESSION['username'] . ' on client: ' . $client_id, $_SESSION['username']);
                header('Location: ' . $redirect_target);
                exit;
            }

            // --- Third-Party Consent Flow ---
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $decision = $_POST['decision'] ?? 'deny';
                if ($decision === 'approve') {
                    $code = OAuthProvider::createAuthCode($client_id, $redirect_uri, $_SESSION['username'], $scope, $state);
                    $redirect_target = $redirect_uri . (strpos($redirect_uri, '?') === false ? '?' : '&') . 'code=' . urlencode($code);
                    if (!empty($state)) {
                        $redirect_target .= '&state=' . urlencode($state);
                    }
                    log_event('oauth_authorize', 'success', 'User ' . $_SESSION['username'] . ' authorized client: ' . $client_id, $_SESSION['username']);
                    header('Location: ' . $redirect_target);
                    exit;
                } else {
                    $redirect_target = $redirect_uri . (strpos($redirect_uri, '?') === false ? '?' : '&') . 'error=access_denied';
                    if (!empty($state)) {
                        $redirect_target .= '&state=' . urlencode($state);
                    }
                    log_event('oauth_authorize', 'denied', 'User ' . $_SESSION['username'] . ' denied access for client: ' . $client_id, $_SESSION['username']);
                    header('Location: ' . $redirect_target);
                    exit;
                }
            }

            $view = $config['PATH_TO_VIEW'] . 'oauth' . DS . 'authorize.php';
            break;

        case "callback-demo":
            $view = $config['PATH_TO_VIEW'] . 'oauth' . DS . 'callback-demo.php';
            break;

        default:
            $view = $config['PATH_TO_VIEW'] . '404.php';
            break;
    endswitch;
}
