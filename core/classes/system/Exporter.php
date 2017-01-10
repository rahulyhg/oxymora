<?php namespace KFall\oxymora\system;
use \PDO;
use \Exception;
use \ZipArchive;
use \RecursiveIteratorIterator;
use \RecursiveDirectoryIterator;
use KFall\oxymora\logs\Logger;
use KFall\oxymora\config\Config;
use KFall\oxymora\database\DB;
use KFall\oxymora\helper\Crypter;

class Exporter{

  private static $backupDirs = [
    ['dir' => TEMPLATE_DIR, 'name' => 'OXY_TEMPLATE'],
    ['dir' => ADDON_DIR, 'name' => 'OXY_ADDONS'],
    ['dir' => ADMIN_DIR."/profil", 'name' => 'OXY_PROFILEPICTURES'],
    ['dir' => FILE_DIR, 'name' => 'OXY_FILE']
  ];

  private static $databaseFileName = "OXY_DB/db.sql";
  private static $configFileName = "OXY_CONFIG/config.json";
  private static $infoFileName = "info.txt";


  public static function getInfo($path, $pass = ""){
    // ==========================================
    // Extract ZIP
    // ==========================================
    try {
      // Return Value
      $info = [];

      // Decrypt File
      if($pass) Crypter::decryptFile($path, $pass);

      // Open ZIP
      $zip = new ZipArchive;
      $res = $zip->open($path);
      if ($res !== TRUE) return false;
      if($pass && !$zip->setPassword($pass)) return false;

      // Has Config ?
      $info['hasConfig'] = ($zip->locateName(self::$configFileName) !== false);

      // Install Folders If Exists in ZIP
      $info['backupDirs'] = [];
      foreach(self::$backupDirs as $bdir){
        $info['backupDirs'][$bdir['dir']] = true;
      }

      // Install Database if Datbase-File exists
      $info['hasDatabase'] = ($zip->locateName(self::$databaseFileName) !== false);

      // Infos
      if($zip->locateName(self::$infoFileName) !== false){
        $zip->extractTo(TEMP_DIR, self::$infoFileName);
        $info['info'] = json_decode(file_get_contents(TEMP_DIR."/".self::$infoFileName), true);
        unlink(TEMP_DIR."/".self::$infoFileName);
      }else{
        $info['info'] = null;
      }


      return $info;
    } catch (Exception $e) {
      Logger::log($e->getMessage(), 'error', 'addonManager.log');
      return false;
    }
  }


  public static function import($path, $pass = ""){
    // ==========================================
    // Extract ZIP
    // ==========================================
    try {
      // Decrypt File
      if($pass) Crypter::decryptFile($path, $pass);

      // Open ZIP
      $zip = new ZipArchive;
      if ($zip->open($path) !== TRUE) return false;
      if($pass && !$zip->setPassword($pass)) return false;

      // Install Config If Exists in ZIP
      if($zip->locateName(self::$configFileName) !== false) $zip->extractTo(ROOT_DIR."config.json", self::$configFileName);

      // Install Folders If Exists in ZIP
      foreach(self::$backupDirs as $bdir){
        if($zip->locateName($bdir['name']) !== false){
          $zip->extractTo($bdir['dir'], $bdir['name']);
        }
      }

      // Install Database if Datbase-File exists
      if($zip->locateName(self::$databaseFileName) !== false){
        $tmp_db_file = tempnam(TEMP_DIR,'');
        $zip->extractTo($tmp_db_file, self::$databaseFileName);
        DB::pdo()->query(file_get_contents($tmp_db_file));
      }

      return true;
    } catch (Exception $e) {
      Logger::log($e->getMessage(), 'error', 'addonManager.log');
      return false;
    }
  }


  public static function export($exportConfig = true, $pass = "") {

    // ==========================================
    // Create ZIP
    // ==========================================
    try {
      // Output Dir
      $outputdir = TEMP_DIR."/exports/";
      if(!file_exists($outputdir)) mkdir($outputdir);

      // Create ZIP
      $zip = new ZipArchive();
      $tmp_file = tempnam($outputdir,'');
      $zip->open($tmp_file, ZipArchive::CREATE);

      // Add Database
      $tmp_db_file = tempnam($outputdir,'');
      file_put_contents($tmp_db_file, self::backupDatabase());
      $zip->addFile($tmp_db_file, self::$databaseFileName);

      // Add Folder
      foreach(self::$backupDirs as $bdir){
        $rootPath = realpath($bdir['dir']);
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($rootPath),RecursiveIteratorIterator::LEAVES_ONLY);
        foreach ($files as $name => $file){
          if (!$file->isDir()){
            $filePath = $file->getRealPath();
            $zipPath = $bdir['name']."/".substr($filePath, strlen($rootPath) + 1);
            $zip->addFile($filePath, $zipPath);
          }
        }
      }

      // Add Config
      if($exportConfig) $zip->addFile(ROOT_DIR."config.json", self::$configFileName);

      // Add extra infos
      $tmp_info_file = tempnam($outputdir,'');
      $info = ['created' => date('Y-m-d H:i:s')];
      file_put_contents($tmp_info_file, json_encode($info));
      $zip->addFile($tmp_info_file, self::$infoFileName);

      // Close & Create ZIP
      $zip->close();

      // Delete Temp-Database-File if created
      if($tmp_db_file) unlink($tmp_db_file);

      // Delete Info-File if created
      if($tmp_info_file) unlink($tmp_info_file);

      // Crypt if password set
      if($pass) Crypter::encryptFile($tmp_file, $pass);

      return $tmp_file;
    } catch (Exception $e) {
      Logger::log($e->getMessage(), 'error', 'exporter.log');
      throw $e;
    }
  }









  public static function backupDatabase() {
    try {
      $config = Config::get();
      $tables = array();
      $result = DB::pdo()->query('SHOW TABLES');
      while($row = $result->fetch(PDO::FETCH_NUM)){
        $tables[] = $row[0];
      }
      $output = 'CREATE DATABASE IF NOT EXISTS '.$config['database']['db'].";\n\n";
      $output .= 'USE '.$config['database']['db'].";\n\n";

      foreach ($tables as $table) {
        // Create Table SQL
        $output .= 'DROP TABLE IF EXISTS ' . $table . ';';
        $tableCreateInfo = DB::pdo()->query('SHOW CREATE TABLE ' . $table)->fetch(PDO::FETCH_NUM);
        $output.= "\n\n" . $tableCreateInfo[1] . ";\n\n";

        // Add Entries SQL
        $result = DB::pdo()->query('SELECT * FROM ' . $table);
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
          $output .= 'INSERT INTO ' . $table . ' VALUES(';
          foreach($row as $value){
            $output .= DB::pdo()->quote($value).",";
          }
          $output = substr($output, 0, -1);
          $output.= ");\n";
        }

      }
      // Return
      $output.="\n\n\n";
      return $output;
    } catch (Exception $e) {
      Logger::log($e->getMessage(), 'error', 'exporter.log');
      return false;
    }

    return $output;
  }

}