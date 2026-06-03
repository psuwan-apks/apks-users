<?php
/** @var string $lang */
/** @var array $translations */
/** @var string $view */
$lang = $lang ?? $_SESSION['LANGUAGE'] ?? 'th';
$translations = $translations ?? [];
$view = $view ?? '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <link rel="apple-touch-icon" sizes="76x76"
        href="./assets/images/icons/apks/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="16x16"
        href="./assets/images/icons/apks/favicon-16x16.png">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <title>
        Title
    </title>
    <meta content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0, shrink-to-fit=no'
        name='viewport' />

    <!-- <link rel="stylesheet" href="assets/fonts/prompt/css/prompt.css"> -->
    <link rel="stylesheet" href="assets/fonts/google-sans/google-sans.css">
    <link rel="stylesheet" href="assets/fonts/fontawesome/css/all.css">

    <link href="./assets/bootstrap-5.3.8/css/bootstrap.css" rel="stylesheet" />
    <link rel="stylesheet" href="assets/css/sidebar-layout.css" />
    <link rel="stylesheet" href="assets/css/footer-layout.css" />

    <style>
        * {
            font-family: 'Google Sans', sans-serif;
        }
        .navbar-custom {
            background-color: #f4f7f9 !important;
            transition: all 0.3s ease;
        }
        .lang-dropdown-btn {
            border: 1px solid rgba(0, 0, 0, 0.08) !important;
            background-color: rgba(255, 255, 255, 0.8) !important;
        }
        .lang-dropdown-btn:hover {
            background-color: rgba(0, 0, 0, 0.04) !important;
            border-color: rgba(0, 0, 0, 0.15) !important;
            transform: translateY(-1px);
        }
        .lang-dropdown-btn:active {
            transform: translateY(0);
        }
        .dropdown-item.active, .dropdown-item:active {
            background-color: #1abc9c !important;
            color: #fff !important;
        }
    </style>
</head>

<body class="">

    <div class="wrapper" data-sidebar-position="left">
        <!-- Mobile top bar: logo + app name + user + language icon -->
        <div class="mobile-topbar justify-content-between align-items-center" id="mobileTopbar">
            <div class="d-flex align-items-center">
                <a href="#" class="mobile-logo" id="mobileToggleLogo">
                    <div class="mobile-logo-image">
                        <img class="mobile-logo-img" src="./assets/images/logo-apks.svg">
                        <i class="mobile-logo-icon fa-thin fa-arrow-right-to-line"></i>
                    </div>
                </a>
                <span class="mobile-app-name ms-1">
                    <?php echo htmlspecialchars($translations['APP_NAME'] ?? 'APKS'); ?>
                </span>
            </div>
            
            <div class="d-flex align-items-center gap-3">
                <!-- Mobile user -->
                <div class="dropdown">
                    <button class="btn btn-link p-0 border-0 d-flex align-items-center text-secondary text-decoration-none" 
                            type="button" id="mobileUserDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php if (isset($_SESSION['USER'])): ?>
                            <i class="fa-thin fa-user-circle" style="font-size: 1.25rem;"></i>
                            <span class="d-none d-sm-inline ms-1 small fw-semibold"><?php echo htmlspecialchars($_SESSION['USER']['username']); ?></span>
                        <?php else: ?>
                            <i class="fa-thin fa-user" style="font-size: 1.25rem;"></i>
                        <?php endif; ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg mt-2 py-2 rounded-3" aria-labelledby="mobileUserDropdown" style="min-width: 120px;">
                        <?php if (isset($_SESSION['USER'])): ?>
                            <li class="px-3 py-1.5 border-bottom d-sm-none">
                                <span class="small fw-semibold text-dark"><?php echo htmlspecialchars($_SESSION['USER']['username']); ?></span>
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2 py-2 px-3" href="./logout.php">
                                    <i class="fa-thin fa-right-from-bracket text-danger"></i>
                                    <span class="small text-danger fw-medium">Logout</span>
                                </a>
                            </li>
                        <?php else: ?>
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2 py-2 px-3" href="?page=user&action=user-login">
                                    <i class="fa-thin fa-user" style="font-size: 14px;"></i>
                                    <span class="small fw-medium">Login</span>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2 py-2 px-3" href="?page=user&action=user-register">
                                    <i class="fa-thin fa-user-plus" style="font-size: 14px;"></i>
                                    <span class="small fw-medium">Register</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Mobile language switcher (only flag icon) -->
                <div class="dropdown">
                    <button class="btn btn-link dropdown-toggle p-0 border-0 d-flex align-items-center" 
                            type="button" id="mobileLanguageDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="./assets/images/flag-<?php echo $lang === 'th' ? 'th' : 'uk'; ?>.png" 
                             alt="<?php echo $lang === 'th' ? 'Thai' : 'English'; ?>" 
                             style="width: 20px; height: 14px; border-radius: 2px; object-fit: cover; box-shadow: 0 1px 2px rgba(0,0,0,0.15);">
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg mt-2 py-2 rounded-3" aria-labelledby="mobileLanguageDropdown" style="min-width: 120px;">
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2 py-2 px-3 <?php echo $lang === 'th' ? 'active' : ''; ?>" 
                               href="#" onclick="changeLanguage('th'); return false;">
                                <img src="./assets/images/flag-th.png" alt="Thai" style="width: 18px; height: 12px; border-radius: 1px; object-fit: cover;">
                                <span class="small fw-medium">ไทย</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2 py-2 px-3 <?php echo $lang === 'en' ? 'active' : ''; ?>" 
                               href="#" onclick="changeLanguage('en'); return false;">
                                <img src="./assets/images/flag-uk.png" alt="English" style="width: 18px; height: 12px; border-radius: 1px; object-fit: cover;">
                                <span class="small fw-medium">English</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <!-- Overlay backdrop for mobile sidebar -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <div class="sidebar collapsed" id="sidebarContainer" data-color="default" data-active-color="danger">
            <script>
                (function() {
                    var state = localStorage.getItem('sidebar-state');
                    if (state === 'expanded') {
                        document.getElementById('sidebarContainer').classList.remove('collapsed');
                    } else if (state === 'collapsed') {
                        document.getElementById('sidebarContainer').classList.add('collapsed');
                    }
                })();
            </script>
            <?php
            require_once 'menu-sidebar.php';
            ?>
        </div>
        <div class="main-panel">
            <!-- Navbar -->
            <nav class="navbar navbar-expand-lg navbar-light navbar-custom px-4 py-2 mb-4 rounded-3 border-0 d-none d-md-flex">
                <div class="container-fluid p-0 d-flex justify-content-between align-items-center">
                    <!-- Title/Brand or welcome message -->
                    <div class="d-flex align-items-center">
                        <span class="navbar-brand fw-semibold text-dark m-0 p-0" style="font-size: 1.1rem; letter-spacing: 0.2px;">
                            <?php 
                            $currentPage = $_GET['page'] ?? 'guest';
                            $titleKey = 'NAV_' . strtoupper(str_replace('-', '_', $currentPage));
                            echo htmlspecialchars($translations[$titleKey] ?? $translations[strtoupper($currentPage) . '_TITLE'] ?? ucfirst($currentPage)); 
                            ?>
                        </span>
                    </div>

                    <!-- Language Switcher & User Status -->
                    <div class="d-flex align-items-center gap-3">
                        <!-- User Status -->
                        <?php if (isset($_SESSION['USER'])): ?>
                            <div class="d-flex align-items-center gap-2 bg-white px-3 py-2 rounded-pill border" style="box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                                <span class="text-secondary small fw-semibold" style="font-size: 0.85rem;">
                                    <i class="fa-thin fa-user-circle me-1"></i>
                                    <?php echo htmlspecialchars($_SESSION['USER']['username']); ?>
                                </span>
                            </div>
                        <?php endif; ?>

                        <!-- Language Changer Selector -->
                        <div class="dropdown">
                            <button class="btn btn-link dropdown-toggle d-flex align-items-center gap-2 border-0 px-3 py-2 rounded-pill text-decoration-none lang-dropdown-btn" 
                                    type="button" id="languageDropdown" data-bs-toggle="dropdown" aria-expanded="false" 
                                    style="transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                                <img src="./assets/images/flag-<?php echo $lang === 'th' ? 'th' : 'uk'; ?>.png" 
                                     alt="<?php echo $lang === 'th' ? 'Thai' : 'English'; ?>" 
                                     style="width: 20px; height: 14px; border-radius: 2px; object-fit: cover; box-shadow: 0 1px 2px rgba(0,0,0,0.15);">
                                <span class="fw-medium text-dark small" style="font-size: 0.85rem;"><?php echo $lang === 'th' ? 'ไทย' : 'English'; ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg mt-2 py-2 rounded-3" aria-labelledby="languageDropdown" style="min-width: 140px;">
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2 py-2 px-3 <?php echo $lang === 'th' ? 'active' : ''; ?>" 
                                       href="#" onclick="changeLanguage('th'); return false;" style="border-radius: 4px; margin: 0 6px;">
                                        <img src="./assets/images/flag-th.png" alt="Thai" style="width: 18px; height: 12px; border-radius: 1px; object-fit: cover;">
                                        <span class="small fw-medium">ไทย</span>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2 py-2 px-3 <?php echo $lang === 'en' ? 'active' : ''; ?>" 
                                       href="#" onclick="changeLanguage('en'); return false;" style="border-radius: 4px; margin: 0 6px;">
                                        <img src="./assets/images/flag-uk.png" alt="English" style="width: 18px; height: 12px; border-radius: 1px; object-fit: cover;">
                                        <span class="small fw-medium">English</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>
            <div class="content">
                <?php
                if (!empty($view) && file_exists($view)) {
                    include_once $view;
                }
                ?>
            </div>

            <?php
            require_once 'menu-footer.php';
            ?>
        </div>
    </div>


    <script src="assets/js/jquery-4.0.0.min.js"></script>
    <script src="assets/js/popper.min.js"></script>
    <script src="assets/bootstrap-5.3.8/js/bootstrap.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.querySelector('.sidebar');
            const logo = document.getElementById('sidebarToggleLogo');
            const mobileLogo = document.getElementById('mobileToggleLogo');
            const overlay = document.getElementById('sidebarOverlay');
            const isMobile = function() {
                return window.innerWidth <= 768;
            };

            // Desktop: click logo area to toggle collapsed
            if (sidebar && logo) {
                logo.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (!isMobile()) {
                        sidebar.classList.toggle('collapsed');
                        const isCollapsed = sidebar.classList.contains('collapsed');
                        localStorage.setItem('sidebar-state', isCollapsed ? 'collapsed' : 'expanded');
                    }
                });
            }

            // Mobile: click mobile topbar logo to open sidebar
            if (mobileLogo && sidebar) {
                mobileLogo.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (isMobile()) {
                        sidebar.classList.add('mobile-open');
                        sidebar.classList.remove('collapsed');
                        if (overlay) overlay.classList.add('active');
                    }
                });
            }

            // Mobile: click overlay to close sidebar
            if (overlay && sidebar) {
                overlay.addEventListener('click', function() {
                    sidebar.classList.remove('mobile-open');
                    overlay.classList.remove('active');
                });
            }

            // Mobile: click collapse button inside sidebar to close
            if (sidebar) {
                sidebar.addEventListener('click', function(e) {
                    if (isMobile() && e.target.closest('.sidebar-collapse-btn')) {
                        sidebar.classList.remove('mobile-open');
                        if (overlay) overlay.classList.remove('active');
                    }
                });
            }
        });

        function changeLanguage(lang) {
            fetch('./process.php?CMD2PROCESS=LANGUAGE_SET', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    language: lang
                }),
            })
            .then((response) => {
                if (response.ok) {
                    window.location.reload();
                } else {
                    console.error('Failed to change language');
                }
            })
            .catch((error) => console.error('Error changing language:', error));
        }
    </script>
</body>

</html>