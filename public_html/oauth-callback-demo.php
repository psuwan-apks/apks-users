<?php
// Standalone visual OAuth2 Client Simulator
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$scriptName = $_SERVER['SCRIPT_NAME'];
$currentDir = dirname($scriptName);

$clientUrl = $protocol . "://" . $host . $scriptName;
$authorizeUrl = "index.php?page=oauth&action=authorize&response_type=code&client_id=demo-client&redirect_uri=" . urlencode($clientUrl) . "&state=demo_state_123&scope=profile";

$tokenEndpoint = $protocol . "://" . $host . $currentDir . "/oauth-token.php";
$userinfoEndpoint = $protocol . "://" . $host . $currentDir . "/oauth-userinfo.php";

$code = $_GET['code'] ?? '';
$error = $_GET['error'] ?? '';
$state = $_GET['state'] ?? '';

$step1 = false;
$step2 = false;
$step3 = false;
$step4 = false;

$tokenRequest = '';
$tokenResponse = '';
$userinfoRequest = '';
$userinfoResponse = '';
$userData = null;

if (!empty($code)) {
    $step1 = true;
    
    // Step 2: Exchange Code for Access Token
    $postFields = [
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => $clientUrl,
        'client_id' => 'demo-client',
        'client_secret' => 'demo-secret'
    ];
    
    $tokenRequest = "POST " . parse_url($tokenEndpoint, PHP_URL_PATH) . " HTTP/1.1\r\n" .
                    "Host: " . parse_url($tokenEndpoint, PHP_URL_HOST) . "\r\n" .
                    "Content-Type: application/x-www-form-urlencoded\r\n\r\n" .
                    http_build_query($postFields);
                    
    $opts = [
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($postFields),
            'ignore_errors' => true
        ]
    ];
    
    $context = stream_context_create($opts);
    $tokenResponse = @file_get_contents($tokenEndpoint, false, $context);
    
    if ($tokenResponse) {
        $step2 = true;
        $tokenData = json_decode($tokenResponse, true);
        $accessToken = $tokenData['access_token'] ?? '';
        
        if (!empty($accessToken)) {
            // Step 3: Fetch User Info
            $userinfoRequest = "GET " . parse_url($userinfoEndpoint, PHP_URL_PATH) . " HTTP/1.1\r\n" .
                               "Host: " . parse_url($userinfoEndpoint, PHP_URL_HOST) . "\r\n" .
                               "Authorization: Bearer " . $accessToken . "\r\n";
                               
            $optsInfo = [
                'http' => [
                    'method' => 'GET',
                    'header' => "Authorization: Bearer " . $accessToken . "\r\n",
                    'ignore_errors' => true
                ]
            ];
            $contextInfo = stream_context_create($optsInfo);
            $userinfoResponse = @file_get_contents($userinfoEndpoint, false, $contextInfo);
            
            if ($userinfoResponse) {
                $step3 = true;
                $userData = json_decode($userinfoResponse, true);
                if (isset($userData['username'])) {
                    $step4 = true;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OAuth2 Client Simulator</title>
    <link rel="stylesheet" href="../../assets/fonts/google-sans/google-sans.css">
    <link rel="stylesheet" href="../../assets/fonts/fontawesome/css/all.css">
    <link href="../../assets/bootstrap-5.3.8/css/bootstrap.css" rel="stylesheet" />
    <style>
        * {
            font-family: 'Google Sans', sans-serif;
        }
        body {
            background-color: #f4f7f9;
        }
        .step-badge {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        pre {
            background-color: #1e1e1e;
            color: #d4d4d4;
            padding: 15px;
            border-radius: 8px;
            font-size: 0.85rem;
            max-height: 250px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <!-- Portal Brand -->
                <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary text-white rounded-3 p-2.5 d-flex align-items-center justify-content-center me-3 shadow-sm">
                            <i class="fa-thin fa-browser fs-3"></i>
                        </div>
                        <div>
                            <h4 class="mb-0 fw-bold text-dark">Demo Client Application</h4>
                            <p class="mb-0 text-secondary small">External App Simulating OAuth2 Flow Integration</p>
                        </div>
                    </div>
                    <a href="index.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                        <i class="fa-thin fa-arrow-left me-1"></i> Back to Provider
                    </a>
                </div>

                <?php if (empty($code) && empty($error)): ?>
                    <!-- Landing view: Login prompt -->
                    <div class="card border-0 shadow-lg rounded-4 p-5 text-center bg-white">
                        <div class="mb-4">
                            <i class="fa-thin fa-right-to-bracket text-primary" style="font-size: 5rem;"></i>
                        </div>
                        <h2 class="fw-bold text-dark mb-3">OAuth2 Authentication Demo</h2>
                        <p class="text-secondary mx-auto mb-4" style="max-width: 600px;">
                            This client application simulates an external web portal integrated with the APKS Central Authentication Service using the standard secure Authorization Code Grant flow.
                        </p>
                        <div class="bg-light p-4 rounded-3 text-start mx-auto mb-5 border border-light-subtle" style="max-width: 600px;">
                            <h5 class="fw-bold text-dark mb-3"><i class="fa-thin fa-circle-info text-info me-2"></i>How the demo works:</h5>
                            <ol class="m-0 ps-3 text-secondary">
                                <li class="mb-2">Click the button below to start the flow.</li>
                                <li class="mb-2">You will be redirected to the provider's consent page (and logged in if needed).</li>
                                <li class="mb-2">Upon approval, you redirect back with an authorization code.</li>
                                <li>The client exchanges the code for a token and fetches your profile data.</li>
                            </ol>
                        </div>
                        <div>
                            <a href="<?php echo $authorizeUrl; ?>" class="btn btn-primary btn-lg rounded-pill px-5 py-3 fw-bold shadow-sm">
                                <i class="fa-thin fa-key me-2"></i> Login with Central Auth Service
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Result View: Step-by-step progress -->
                    <div class="row">
                        <!-- Left side: progress checklist -->
                        <div class="col-md-5 mb-4">
                            <div class="card border-0 shadow-sm rounded-4 mb-4">
                                <div class="card-header bg-white py-3 border-light">
                                    <h6 class="m-0 fw-bold text-dark">Authorization Flow Steps</h6>
                                </div>
                                <div class="card-body p-4">
                                    <ul class="list-unstyled m-0 d-flex flex-column gap-4">
                                        <!-- Step 1 -->
                                        <li class="d-flex align-items-center gap-3">
                                            <span class="step-badge <?php echo $step1 ? 'bg-success text-white' : 'bg-secondary text-white'; ?>">
                                                <?php echo $step1 ? '✓' : '1'; ?>
                                            </span>
                                            <div>
                                                <span class="fw-bold d-block <?php echo $step1 ? 'text-success' : 'text-secondary'; ?>">Code Received</span>
                                                <small class="text-muted">Auth code sent to callback redirect.</small>
                                            </div>
                                        </li>
                                        <!-- Step 2 -->
                                        <li class="d-flex align-items-center gap-3">
                                            <span class="step-badge <?php echo $step2 ? 'bg-success text-white' : 'bg-secondary text-white'; ?>">
                                                <?php echo $step2 ? '✓' : '2'; ?>
                                            </span>
                                            <div>
                                                <span class="fw-bold d-block <?php echo $step2 ? 'text-success' : 'text-secondary'; ?>">Token Exchanged</span>
                                                <small class="text-muted">Code swapped for Access Token.</small>
                                            </div>
                                        </li>
                                        <!-- Step 3 -->
                                        <li class="d-flex align-items-center gap-3">
                                            <span class="step-badge <?php echo $step3 ? 'bg-success text-white' : 'bg-secondary text-white'; ?>">
                                                <?php echo $step3 ? '✓' : '3'; ?>
                                            </span>
                                            <div>
                                                <span class="fw-bold d-block <?php echo $step3 ? 'text-success' : 'text-secondary'; ?>">User Info Retrieved</span>
                                                <small class="text-muted">Profile fetched using token.</small>
                                            </div>
                                        </li>
                                        <!-- Step 4 -->
                                        <li class="d-flex align-items-center gap-3">
                                            <span class="step-badge <?php echo $step4 ? 'bg-success text-white' : 'bg-secondary text-white'; ?>">
                                                <?php echo $step4 ? '✓' : '4'; ?>
                                            </span>
                                            <div>
                                                <span class="fw-bold d-block <?php echo $step4 ? 'text-success' : 'text-secondary'; ?>">Authenticated Session</span>
                                                <small class="text-muted">User successfully logged in!</small>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            
                            <?php if ($step4 && $userData): ?>
                                <!-- User profile display -->
                                <div class="card border-0 shadow-lg rounded-4 overflow-hidden" style="background: linear-gradient(135deg, #2c3e50, #34495e); color: white;">
                                    <div class="card-body p-4 text-center">
                                        <div class="mb-3">
                                            <i class="fa-thin fa-user-circle" style="font-size: 5rem; color: #1abc9c;"></i>
                                        </div>
                                        <h4 class="fw-bold mb-1">Welcome back!</h4>
                                        <p class="text-white-50 mb-3">Successfully Logged In via SSO</p>
                                        <div class="bg-white text-dark py-2 px-4 rounded-pill d-inline-block fw-bold shadow-sm">
                                            User: <?php echo htmlspecialchars($userData['username']); ?>
                                        </div>
                                        <?php if (isset($userData['email'])): ?>
                                        <p class="text-white-50 small mt-2 mb-0">Email: <?php echo htmlspecialchars($userData['email']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($error)): ?>
                                <div class="alert alert-danger rounded-3 p-3 mt-3">
                                    <h6 class="fw-bold"><i class="fa-thin fa-circle-exclamation me-1"></i> OAuth Error</h6>
                                    <p class="small mb-0"><?php echo htmlspecialchars($error); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Right side: inspect payload console -->
                        <div class="col-md-7">
                            <div class="card border-0 shadow-sm rounded-4 mb-4">
                                <div class="card-header bg-white py-3 border-light">
                                    <h6 class="m-0 fw-bold text-dark"><i class="fa-thin fa-terminal me-2"></i>API Inspector Logs</h6>
                                </div>
                                <div class="card-body p-4">
                                    <?php if ($step1): ?>
                                        <h6 class="fw-bold text-secondary mb-2">1. Auth Code Received in Query Parameters:</h6>
                                        <pre>$_GET['code'] = "<?php echo htmlspecialchars($code); ?>"</pre>
                                    <?php endif; ?>

                                    <?php if ($step2): ?>
                                        <h6 class="fw-bold text-secondary mt-4 mb-2">2. Client POST Request to Token Endpoint:</h6>
                                        <pre><?php echo htmlspecialchars($tokenRequest); ?></pre>
                                        
                                        <h6 class="fw-bold text-secondary mt-3 mb-2">Token Response Payload:</h6>
                                        <pre><?php echo htmlspecialchars($tokenResponse); ?></pre>
                                    <?php endif; ?>

                                    <?php if ($step3): ?>
                                        <h6 class="fw-bold text-secondary mt-4 mb-2">3. Client GET Request to Resource Endpoint:</h6>
                                        <pre><?php echo htmlspecialchars($userinfoRequest); ?></pre>
                                        
                                        <h6 class="fw-bold text-secondary mt-3 mb-2">UserInfo Response Payload:</h6>
                                        <pre><?php echo htmlspecialchars($userinfoResponse); ?></pre>
                                    <?php endif; ?>
                                    
                                    <div class="text-center mt-4 pt-2 border-top">
                                        <a href="oauth-callback-demo.php" class="btn btn-outline-primary rounded-pill btn-sm px-4">
                                            <i class="fa-thin fa-arrows-rotate me-1"></i> Restart Demo Flow
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
