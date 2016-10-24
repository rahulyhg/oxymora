<?php
use KFall\oxymora\memberSystem\MemberSystem;
use KFall\oxymora\addons\AddonManager;
require_once 'php/admin.php';
loginCheck();
AddonManager::triggerEvent(ADDON_EVENT_OPEN, null)
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
  <title>Oxymora | Dashboard</title>
  <link rel="stylesheet" href="css/font-awesome.min.css">
  <link rel="stylesheet" href="css/master.css">
  <link rel="stylesheet" href="css/content.css">
  <link rel="stylesheet" href="css/menuToggle.css">
  <script src="js/jquery-3.1.1.min.js" charset="utf-8"></script>
</head>
<body>

  <!-- ======================================================== -->
  <!--                     HEADER                               -->
  <!-- ======================================================== -->
  <div id="header">
    <div class="container">
      <!-- Menu Toggle -->
      <div id="menuToggle">
        <span></span>
        <span></span>
        <span></span>
      </div>
    </div>
  </div>

  <!-- ======================================================== -->
  <!--                   SIDE MENU                              -->
  <!-- ======================================================== -->
  <div id="sidemenu" class="">
    <!-- Oxymora Logo -->
    <div class="headbar">Oxymora</div>
    <!-- Side Menu Container -->
    <div class="side-container">
      <!-- User Info -->
      <div class="userinfo">
        <div class="image">
          <i class="fa fa-user" aria-hidden="true"></i>
        </div>
        <div class="name">
          <?php echo MemberSystem::init()->member->username; ?>
        </div>


      </div>
      <ul>
        <li class="topic">Management</li>
        <li><a class="nav active" onclick="loadPage('dashboard')"  href="#dashboard"><i class="fa fa-tachometer" aria-hidden="true"></i> Dashboard</a></li>
        <li><a class="nav" onclick="loadPage('pages')"      href="#pages"><i class="fa fa-th-list" aria-hidden="true"></i> Pages & Navigation</a></li>
        <li><a class="nav" onclick="loadPage('files')"     href="#files"><i class="fa fa-archive" aria-hidden="true"></i> File-Manager</a></li>
        <li><a class="nav" onclick="loadPage('member')"     href="#member"><i class="fa fa-users" aria-hidden="true"></i> Member</a></li>
        <li><a class="nav" onclick="loadPage('addons')"   href="#addons"><i class="fa fa-puzzle-piece" aria-hidden="true"></i> Addon-Manager</a></li>
        <li><a class="nav" onclick="loadPage('settings')"   href="#settings"><i class="fa fa-cogs" aria-hidden="true"></i> Settings</a></li>
        <li id="addonTopic" style="display:none;" class="topic">Addons</li>
        <li class="topic">Other</li>
        <li><a href="../" target="_blank"><i class="fa fa-external-link" aria-hidden="true"></i> Open Website</a></li>
        <li><a href="logout.php"><i class="fa fa-sign-out" aria-hidden="true"></i> Logout</a></li>
      </ul>
      <footer>Oxymora v1.0 Development Edition</footer>
    </div>
  </div>

  <!-- ======================================================== -->
  <!--                     CONTENT                              -->
  <!-- ======================================================== -->

  <div id="wrapper">
    <div id="content">

    </div>

    <!-- ======================================================== -->
    <!--                    PRELOADER                             -->
    <!-- ======================================================== -->
    <div id="preloader">
      <canvas id="canvas" width="480" height="270" style="display: block; background-color:rgba(255, 255, 255, 1.00)"></canvas>
    </div>
  </div>


  <!-- ======================================================== -->
  <!--                    LIGHTBOX                              -->
  <!-- ======================================================== -->
  <div id="lightbox">
    <div class="container">
      <div class="dialog">
        <div class="content"></div>
        <div class="footer">
          <button class="success">Ok</button>
          <button class="cancel">Cancel</button>
        </div>
      </div>
    </div>
  </div>


  <!-- ======================================================== -->
  <!--                    FALLBACK                              -->
  <!-- ======================================================== -->
  <div id="fallback">
    <a href="https://www.google.de/chrome/browser/desktop/"><img src="img/browserOld.gif" alt="" /></a>
  </div>


  <!-- ======================================================== -->
  <!--                    SCRIPTS                               -->
  <!-- ======================================================== -->
  <!-- SETUP -->
  <script src="js/browserOld.js" charset="utf-8"></script>
  <script src="js/define.js" charset="utf-8"></script>
  <!-- PRELOADER -->
  <script src="https://code.createjs.com/createjs-2015.11.26.min.js"></script>
  <script src="js/preloader.js"></script>
  <script src="js/oxymora_zahnrad.js"></script>
  <!-- OTHER -->
  <script src="js/functions.js" charset="utf-8"></script>
  <script src="js/master.js" charset="utf-8"></script>
  <script src="js/pageEditor.js" charset="utf-8"></script>
  <script src="js/fileManager.js" charset="utf-8"></script>
</body>
</html>
