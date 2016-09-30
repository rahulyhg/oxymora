<?php
use KFall\oxymora\database\modals\DBStatic;
use KFall\oxymora\database\modals\DBNavigation;
use KFall\oxymora\memberSystem\MemberSystem;
use KFall\oxymora\pageBuilder\PageEditor;
use KFall\oxymora\pluginManager\PluginManager;
require_once '../php/admin.php';
loginCheck();

// Current Page
$action = (isset($_GET['a'])) ? $_GET['a'] : error("No Action set.. What do you try to do??");
$answer = ["error"=>false,"data"=>""];

switch ($action) {
  case 'getPlugins':
    $answer["data"] = PluginManager::listPlugins(TEMPLATE);
    break;

  case 'pluginSettings':
    // GIVE ALL POSSIBLE SETTINGS 
    break;

  case 'renderPlugin':
      $plugin = (isset($_GET['plugin'])) ? $_GET['plugin'] : error("No Plugin set.. What do you try to do??");
      $pluginSettings = (isset($_GET['settings'])) ? json_decode($_GET['settings']) : "";
      $answer["data"] = renderPluginPreview($plugin,$pluginSettings);
      break;

  default:
    # code...
    break;
}

echo json_encode($answer);



function renderPluginPreview($plugin, $settings){
  if(!PageEditor::loadTemplate(TEMPLATE)){
    die("There is a problem with your template!");
  }
  PageEditor::setCustomPath("../../");
  PageEditor::setMenuItems(DBNavigation::getItems());
  PageEditor::setTemplateVars(DBStatic::getVars());
  PageEditor::loadCurrentPage($page);

  // ECHOS THE HTML OF PLUGIN
  return PageEditor::getPluginHTML($plugin,$settings);
}





// THIS RUNS WHEN SOMETHING BAD HAPPEND :S
function error($message){
  die(json_encode(["error"=>true,"data"=>$message]));
}
