<?php
// PHP Error output fix.
ob_get_contents();
ob_end_clean();
error_reporting(0);
ini_set('display_errors', 'Off');
session_start();
use KFall\oxymora\system\Exporter;
use KFall\oxymora\helper\Crypter;
require '../../core/autoload.php';
require '../../core/statics.php';

if(configExists() && (!isset($_SESSION['installing']) || $_SESSION['installing'] == false)) error('Oxymora seems already setup ...');

define('BACKUP_FOLDER', __DIR__."/upload");
define('BACKUP_FILE', BACKUP_FOLDER."/backup.oxybackup");
$action = (isset($_GET['action'])) ? $_GET['action'] : "";
$deleteConfigOnError = false;

switch($action){
  case 'setup':
  try{
    $_SESSION['installing'] = true;
    require 'setup_installer.php';
  } catch(Exception $e){
    if(configExists()) removeConfig();
    session_destroy();
    error($e->getMessage());
  }
  break;

  case 'restore':
  try{
    $_SESSION['installing'] = true;
    require 'backup_installer.php';
  } catch(Exception $e){
    if(configExists()) removeConfig();
    session_destroy();
    error($e->getMessage());
  }
  break;

  case 'checkBackupDB':
  try{
    if(!file_exists(BACKUP_FILE)) error('No Backup-Container found.');
    $info = Exporter::getConfig(BACKUP_FILE);
    if($info === false || !isset($info['database'])) error('No valid Config found!');
    $host = $info['database']['host'];
    $user = $info['database']['user'];
    $pass = $info['database']['pass'];
    $pdo = connectDB($host,$user,$pass);
    if(!$pdo) throw new Exception('Cant connect to Database!');
    success();
  } catch(Exception $e){
    error($e->getMessage());
  }
  break;

  case 'checkDB':
  try{
    $host = (isset($_POST['host'])) ? $_POST['host'] : "";
    $user = (isset($_POST['user'])) ? $_POST['user'] : "";
    $pass = (isset($_POST['pass'])) ? $_POST['pass'] : "";
    $pdo = connectDB($host,$user,$pass);
    if(!$pdo) throw new Exception('Cant connect to Database!');
    success();
  } catch(Exception $e){
    error($e->getMessage());
  }
  break;

  case 'uploadBackup':
  if(isset($_FILES['file'])){
    if($_FILES['file']['error'] !== UPLOAD_ERR_OK) error(uploadErrorCodeToMessage($_FILES['file']['error']));
    $password = (isset($_POST['password'])) ? $_POST['password'] : "";
    if(!file_exists(BACKUP_FOLDER)) $createdFolder = mkdir(BACKUP_FOLDER);
    if($createdFolder === false) error('Cant create upload-folder!');
    $moved = move_uploaded_file($_FILES['file']['tmp_name'], BACKUP_FILE);
    if($moved === false) error('Cant move uploaded File!');
    if($password) Crypter::decryptFile(BACKUP_FILE, $password);
    $info = Exporter::getInfo(BACKUP_FILE);
    if($info === false) error('Wrong Password or broken Backup Container!');
    success($info);
  }else{
    error('Couldn\'t get File! Check "post_max_size" and "upload_max_filesize" in your php.ini.');
  }
  break;

  default:
  error('invalid action');
}


function connectDB($host, $user, $pass){
  $pdo = new PDO('mysql:host='.$host.';', $user, $pass, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
  $pdo->exec('SET NAMES UTF8');
  return $pdo;
}

function getDefaultConfig(){
  return json_decode(file_get_contents(__DIR__."/config/config.json"), true);
}

function setConfig($assoc){
  return file_put_contents(ROOT_DIR.'config.json', json_encode($assoc));
}

function configExists(){
  return file_exists(ROOT_DIR.'config.json');
}

function removeConfig(){
  return unlink(ROOT_DIR.'config.json');
}

function success($message = null){
  die(json_encode(['error' => false, 'message' => $message]));
}
function error($message = null){
  die(json_encode(['error' => true, 'message' => $message]));
}

function uploadErrorCodeToMessage($code){
  switch ($code) {
    case UPLOAD_ERR_INI_SIZE:
    $message = "The uploaded file exceeds the upload_max_filesize directive in php.ini";
    break;
    case UPLOAD_ERR_FORM_SIZE:
    $message = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
    break;
    case UPLOAD_ERR_PARTIAL:
    $message = "The uploaded file was only partially uploaded";
    break;
    case UPLOAD_ERR_NO_FILE:
    $message = "No file was uploaded";
    break;
    case UPLOAD_ERR_NO_TMP_DIR:
    $message = "Missing a temporary folder";
    break;
    case UPLOAD_ERR_CANT_WRITE:
    $message = "Failed to write file to disk";
    break;
    case UPLOAD_ERR_EXTENSION:
    $message = "File upload stopped by extension";
    break;

    default:
    $message = "Unknown upload error";
    break;
  }
  return $message;
}

?>
