<?php
global $translations;

if (session_status() == PHP_SESSION_NONE) {
  // Session is not active, start it
  session_start();
}
// Set the default language to English ('en') if not already set
if (!isset($_SESSION['LANGUAGE'])) {
  $_SESSION['LANGUAGE'] = 'en';
}

$currentLanguage = $_SESSION['LANGUAGE']; // Current language
$nextLanguage = $currentLanguage === 'en' ? 'th' : 'en'; // Determine the next language

?>
<nav class="navbar navbar-expand-lg navbar-absolute fixed-top navbar-transparent">
  <div class="container-fluid">
    <div class="navbar-wrapper">
      <div class="navbar-minimize">
        <button id="minimizeSidebar" class="btn btn-icon btn-round">
          <i class="nc-icon nc-minimal-right text-center visible-on-sidebar-mini"></i>
          <i class="nc-icon nc-minimal-left text-center visible-on-sidebar-regular"></i>
        </button>
      </div>
      <div class="navbar-toggle">
        <button type="button" class="navbar-toggler">
          <span class="navbar-toggler-bar bar1"></span>
          <span class="navbar-toggler-bar bar2"></span>
          <span class="navbar-toggler-bar bar3"></span>
        </button>
      </div>
      <a class="navbar-brand" href="javascript:;">&nbsp;<?= $translations['APP_NAME'] ?></a>
    </div>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navigation" aria-controls="navigation-index" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-bar navbar-kebab"></span>
      <span class="navbar-toggler-bar navbar-kebab"></span>
      <span class="navbar-toggler-bar navbar-kebab"></span>
    </button>

    <div class="collapse navbar-collapse justify-content-end" id="navigation">
      <!-- <form>
              <div class="input-group no-border">
                <input type="text" value="" class="form-control" placeholder="Search...">
                <div class="input-group-append">
                  <div class="input-group-text">
                    <i class="nc-icon nc-zoom-split"></i>
                  </div>
                </div>
              </div>
            </form> -->

      <ul class="navbar-nav">
        <!-- flag for lang -->
        <li class="nav-item" id="language-toggle" style="cursor: pointer; display: inline-block; padding-top: 0.5rem; padding-right: 1.25rem">
          <img
            src="<?php echo $currentLanguage === 'en'
                    ? './assets/images/flag-uk.png'
                    : './assets/images/flag-th.png'; ?>"
            alt="<?php echo $currentLanguage === 'en' ? 'English' : 'Thai'; ?>"
            title="Switch to <?php echo $nextLanguage === 'en' ? 'English' : 'Thai'; ?>"
            id="language-flag"
            width="50%" />
        </li>
        <!-- end of flag for lang -->

        <!-- <li class="nav-item">
          <a class="nav-link btn-magnify" href="javascript:;">
            <i class="nc-icon nc-layout-11"></i>
            <p>
              <span class="d-lg-none d-md-block">Stats</span>
            </p>
          </a>
        </li> -->

        <li class="nav-item btn-magnify dropdown">
          <a class="nav-link dropdown-toggle" href="http://example.com" id="navbarDropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="nc-icon nc-single-02"></i>
            <p>
              <span class="d-lg-none d-md-block">User</span>
            </p>
          </a>
          <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdownMenuLink">
            <a class="dropdown-item" href="./register.php">Register</a>
            <a class="dropdown-item" href="./login.php">Login</a>
            <a class="dropdown-item" href="./logout.php">Logout</a>
          </div>
        </li>

        <!-- <li class="nav-item">
          <a class="nav-link btn-rotate" href="javascript:;">
            <i class="nc-icon nc-settings-gear-65"></i>
            <p>
              <span class="d-lg-none d-md-block">Account</span>
            </p>
          </a>
        </li> -->

      </ul>
    </div>
  </div>
</nav>


<script>
  document.querySelector('#language-toggle').addEventListener('click', () => {
    const nextLanguage = '<?php echo $nextLanguage; ?>';

    // Send a POST request to update the session language
    fetch('./process.php?CMD2PROCESS=LANGUAGE_SET', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          language: nextLanguage
        }),
      })
      .then((response) => {
        if (response.ok) {
          // Reload the page to reflect the updated language
          window.location.reload();
        } else {
          console.error('Failed to change language');
        }
      })
      .catch((error) => console.error('Error:', error));
  });
</script>