<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
global $translations;
?>

<div class="logo" id="sidebarToggleLogo">
    <a href="#" class="simple-text logo-mini">
        <div class="logo-image-small">
            <img class="logo-img" src="./assets/images/logo-apks.svg" style="width: 24px;">
            <i class="logo-icon fa-thin fa-arrow-right-to-line" data-bs-toggle="tooltip" data-bs-placement="right" title="<?php echo htmlspecialchars($translations['EXPAND_SIDEBAR'] ?? 'Expand sidebar'); ?>"></i>
        </div>
    </a>
    <a href="#" class="simple-text logo-normal pl-2">
        <?php echo htmlspecialchars($translations['APP_NAME'] ?? 'APKS'); ?>
    </a>
    <i class="sidebar-collapse-btn fa-thin fa-arrow-left-to-line" data-bs-toggle="tooltip" data-bs-placement="right" title="<?php echo htmlspecialchars($translations['CLOSE_SIDEBAR'] ?? 'Close sidebar'); ?>"></i>
</div>

<div class="sidebar-wrapper">
  <?php
  // Determine current route
  $currentPage = $_GET['page'] ?? 'guest';
  $currentAction = $_GET['action'] ?? 'home';

  // Helper functions for active/show classes
  $menuActive = function ($cond) {
    return $cond ? 'active' : '';
  };
  $collapseShow = function ($cond) {
    return $cond ? 'show' : '';
  };

  // Rule checker for active state
  $isActiveRule = function($rule) use ($currentPage, $currentAction) {
    if (!$rule) return false;
    switch ($rule['type']) {
        case 'exact_page':
            return $currentPage === $rule['page'];
        case 'in_pages':
            return in_array($currentPage, $rule['pages'], true);
        case 'exact_page_action':
            return $currentPage === $rule['page'] && $currentAction === $rule['action'];
        case 'uri_match':
            return isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], $rule['value']) !== false;
    }
    return false;
  };

  // Load menu items from JSON
  $menuJsonPath = __DIR__ . '/../menu/sidebar.json';
  $menuItems = [];
  if (file_exists($menuJsonPath)) {
      $json = @file_get_contents($menuJsonPath);
      if ($json) {
          $menuItems = json_decode($json, true) ?: [];
      }
  }

  // Separate menu items into top and bottom
  $topItems = [];
  $bottomItems = [];
  foreach ($menuItems as $item) {
      if (isset($item['position']) && $item['position'] === 'bottom') {
          $bottomItems[] = $item;
      } else {
          $topItems[] = $item;
      }
  }

  // Render a menu item function
  $renderItem = function($item) use ($isActiveRule, $menuActive, $collapseShow, $translations) {
      $isActive = $isActiveRule($item['activeRule'] ?? null);
      $title = !empty($item['titleTransKey']) && isset($translations[$item['titleTransKey']]) 
                ? $translations[$item['titleTransKey']] 
                : ($item['defaultTitle'] ?? '');

      $tooltip = !empty($item['tooltipTransKey']) && isset($translations[$item['tooltipTransKey']]) 
                ? $translations[$item['tooltipTransKey']] 
                : ($item['defaultTooltip'] ?? $item['tooltip'] ?? '');

      if (($item['type'] ?? 'item') === 'item') {
          ?>
          <li class="<?php echo $menuActive($isActive); ?>">
              <a class="nav-link" href="<?php echo htmlspecialchars($item['url']); ?>"
                 <?php if (!empty($tooltip)): ?> data-bs-toggle="tooltip" data-bs-placement="right" title="<?php echo htmlspecialchars($tooltip); ?>"<?php endif; ?>>
                  <i class="<?php echo htmlspecialchars($item['icon']); ?>"></i>
                  <p><?php echo htmlspecialchars($title); ?></p>
              </a>
          </li>
          <?php
      } elseif (($item['type'] ?? 'item') === 'collapse') {
          // Check if any child item is active
          $isAnyChildActive = false;
          foreach (($item['children'] ?? []) as $child) {
              if ($isActiveRule($child['activeRule'] ?? null)) {
                  $isAnyChildActive = true;
                  break;
              }
          }
          $isExpanded = $isActive || $isAnyChildActive;
          ?>
          <li class="<?php echo $menuActive($isExpanded); ?>">
              <a data-bs-toggle="collapse" data-bs-target="#<?php echo htmlspecialchars($item['id']); ?>" href="#" class="nav-link <?php echo $isExpanded ? '' : 'collapsed'; ?>" aria-expanded="<?php echo $isExpanded ? 'true' : 'false'; ?>"
                 <?php if (!empty($tooltip)): ?> data-bs-custom-tooltip="true" data-bs-placement="right" title="<?php echo htmlspecialchars($tooltip); ?>"<?php endif; ?>>
                  <i class="<?php echo htmlspecialchars($item['icon']); ?>"></i>
                  <p>
                      <?php echo htmlspecialchars($title); ?>
                  </p>
                  <b class="caret"></b>
              </a>
              <div class="collapse <?php echo $collapseShow($isExpanded); ?>" id="<?php echo htmlspecialchars($item['id']); ?>">
                  <ul class="nav">
                      <?php foreach (($item['children'] ?? []) as $child): ?>
                          <?php 
                           $isChildActive = $isActiveRule($child['activeRule'] ?? null);
                           $childTitle = !empty($child['titleTransKey']) && isset($translations[$child['titleTransKey']])
                                          ? $translations[$child['titleTransKey']]
                                          : ($child['defaultTitle'] ?? '');
                           $childTooltip = !empty($child['tooltipTransKey']) && isset($translations[$child['tooltipTransKey']])
                                          ? $translations[$child['tooltipTransKey']]
                                          : ($child['defaultTooltip'] ?? $child['tooltip'] ?? '');
                          ?>
                           <li class="<?php echo $menuActive($isChildActive); ?>">
                               <a class="nav-link" href="<?php echo htmlspecialchars($child['url']); ?>"
                                  <?php if (!empty($childTooltip)): ?> data-bs-toggle="tooltip" data-bs-placement="right" title="<?php echo htmlspecialchars($childTooltip); ?>"<?php endif; ?>>
                                   <span class="sidebar-mini-icon"><?php echo htmlspecialchars($child['miniIcon']); ?></span>
                                   <span class="sidebar-normal"> <?php echo htmlspecialchars($childTitle); ?> </span>
                               </a>
                           </li>
                      <?php endforeach; ?>
                  </ul>
              </div>
          </li>
          <?php
      }
  };
  ?>

  <!-- Top Menu List -->
  <ul class="nav nav-top">
      <?php foreach ($topItems as $item) { $renderItem($item); } ?>
  </ul>

  <!-- Bottom Menu List -->
  <ul class="nav nav-bottom" style="margin-top: auto;">
      <?php foreach ($bottomItems as $item) { $renderItem($item); } ?>
      <?php
    // User menu with sub-items
    $userMenuExpanded = (isset($_SESSION['loggedin']) && $_SESSION['loggedin']) || ($currentPage === 'user');
    ?>
<li class="nav-item <?php echo $userMenuExpanded ? 'active' : ''; ?>">
    <a data-bs-toggle="collapse" href="#userMenu" class="nav-link <?php echo $userMenuExpanded ? '' : 'collapsed'; ?>" aria-expanded="<?php echo $userMenuExpanded ? 'true' : 'false'; ?>"
       data-bs-custom-tooltip="true" data-bs-placement="right" title="<?php echo htmlspecialchars($translations['NAV_USER_TOOLTIP'] ?? 'User Account'); ?>">
        <i class="fa-thin fa-user"></i>
        <p>User</p>
    </a>
    <div class="collapse <?php echo $userMenuExpanded ? 'show' : ''; ?>" id="userMenu">
        <ul class="nav">
            <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']): ?>
            <li class="nav-item">
                <a href="./logout.php" class="nav-link"
                   data-bs-toggle="tooltip" data-bs-placement="right" title="<?php echo htmlspecialchars($translations['NAV_LOGOUT_TOOLTIP'] ?? 'Logout'); ?>">
                    <i class="fa-thin fa-right-from-bracket"></i>
                    <p>Logout</p>
                </a>
            </li>
            <?php else: ?>
            <li class="nav-item <?php echo ($currentPage === 'user' && $currentAction === 'user-login') ? 'active' : ''; ?>">
                <a href="?page=user&action=user-login" class="nav-link"
                   data-bs-toggle="tooltip" data-bs-placement="right" title="<?php echo htmlspecialchars($translations['NAV_LOGIN_TOOLTIP'] ?? 'Login'); ?>">
                    <i class="fa-thin fa-user" style="font-size:14px;"></i>
                    <p>Login</p>
                </a>
            </li>
            
            <li class="nav-item <?php echo ($currentPage === 'user' && $currentAction === 'user-register') ? 'active' : ''; ?>">
                <a href="?page=user&action=user-register" class="nav-link"
                   data-bs-toggle="tooltip" data-bs-placement="right" title="<?php echo htmlspecialchars($translations['NAV_REGISTER_TOOLTIP'] ?? 'Register'); ?>">
                    <i class="fa-thin fa-user" style="font-size:14px;"></i>
                    <p>Register</p>
                </a>
            </li>

            <?php endif; ?>
        </ul>
    </div>
</li>
  </ul>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var customTooltips = [].slice.call(document.querySelectorAll('[data-bs-custom-tooltip="true"]'));
    customTooltips.forEach(function (el) {
        new bootstrap.Tooltip(el);
    });
});
</script>