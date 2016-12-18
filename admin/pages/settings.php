<?php
// core stuff
use KFall\oxymora\pageBuilder\CurrentTemplate;
use KFall\oxymora\database\modals\DBGroups;
use KFall\oxymora\database\modals\DBStatic;
use KFall\oxymora\memberSystem\MemberSystem;
use KFall\oxymora\addons\AddonManager;
use KFall\oxymora\permissions\UserPermissionSystem;
require_once '../php/admin.php';
require_once '../php/htmlComponents.php';

// Check Login
loginCheck();

// Check Permissions
if(!UserPermissionSystem::checkPermission("oxymora_settings")) die(html_error("You do not have the required rights to continue!"));

// Tab Change event
AddonManager::triggerEvent(ADDON_EVENT_TABCHANGE, 'settings');

$vars = DBStatic::getVars();
?>
<!-- <div class="headerbox flat-box">
<h1>Settings</h1>
<h3>Settings all over the place, but be careful!</h3>
</div> -->
<div class="settings">

  <div class="tabContainer">
    <ul>
      <li><a data-tab="system">System</a></li>
      <li><a data-tab="database">Database</a></li>
      <li><a data-tab="template">Template</a></li>
      <li><a data-tab="account">Account</a></li>
    </ul>
    <div class="tabContent">

      <div class="tab" data-tab="system">
        <div class="dataContainer">
          <div class="info">
            <img src="img/oxy.svg">
            <span class="oxy-h1">XYMORA</span><br>
            <span class="oxy-h2"> VERSION <?php echo OXY_VERSION; ?></span>
          </div>
        </div>
      </div>

      <div class="tab" data-tab="database">
        <div class="dataContainer">

          <form class="oxform settings template" action="index.html" method="post">
            <?php
            // Change against get Database settings
            $settings = CurrentTemplate::getStaticSettings();
            foreach($settings as $setting){
              ?>
              <label><?php echo $setting['displayname'] ?></label>
              <input data-initial="<?php echo $setting['value'] ?>" data-key="<?php echo $setting['key'] ?>" class="oxinput" type="text" value="<?php echo $setting['value'] ?>">
              <?php
            }
            ?>
            <div class="user-actions">
              <button class="templateDiscard" type="button">Discard</button>
              <button class="templateSave" type="submit">Save</button>
            </div>
          </form>

        </div>
      </div>

      <div class="tab" data-tab="template">
        <div class="dataContainer">
          <form class="oxform settings template" action="index.html" method="post">
            <?php
            $settings = CurrentTemplate::getStaticSettings();
            foreach($settings as $setting){
              ?>
              <label><?php echo $setting['displayname'] ?></label>
              <input data-initial="<?php echo $setting['value'] ?>" data-key="<?php echo $setting['key'] ?>" class="oxinput" type="text" value="<?php echo $setting['value'] ?>">
              <?php
            }
            ?>
            <div class="user-actions">
              <button class="templateDiscard" type="button">Discard</button>
              <button class="templateSave" type="submit">Save</button>
            </div>
          </form>
        </div>
      </div>

      <div class="tab" data-tab="account">
        <div class="dataContainer">
          <div class="user">
            <div class="userimg">
              <img src="<?php echo MemberSystem::init()->member->image; ?>" alt="">
            </div>
            <div class="userinfo">
              <label>Username</label>
              <input readonly class="oxinput" type="text" value="<?php echo MemberSystem::init()->member->username; ?>">
              <label>Email</label>
              <input readonly class="oxinput" type="text" value="<?php echo MemberSystem::init()->member->email; ?>">
              <label>Group</label>
              <input readonly class="oxinput" type="text" value="<?php echo DBGroups::getGroupInfo(MemberSystem::init()->member->groupid)['name']; ?>">
            </div>
          </div>
          <div class="user-actions">
            <button class="changePw" type="button"><i class="fa fa-cog" aria-hidden="true"></i> Change Password</button>
            <button class="deleteAcc"type="button"><i class="fa fa-trash-o" aria-hidden="true"></i> Delete Account</button>
          </div>
        </div>
      </div>

    </div>
  </div>


</div>

<script type="text/javascript">
// TEMPLATE FORM
let templateForm = $('.settings.template');
// discard form
templateForm.find('.templateDiscard').on('click', function(){
  templateForm.find('input').each(function(){
    $(this).val($(this).data('initial'));
  });
});
// form submit
templateForm.on('submit', function(e){
  e.preventDefault();
  console.log('send');
});

function setNewInitial(){
  templateForm.find('input').each(function(){
    $(this).data('initial', $(this).val())
  });
}
</script>
