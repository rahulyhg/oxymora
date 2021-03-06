<?php namespace KFall\oxymora\database\modals;
use PDO;
use PDOException;
use KFall\oxymora\database\DB;
use KFall\oxymora\config\Config;


class DBElementSettings{

  public static function addSettings($elementid, $settings, $transaction = true){
    // Start Transaction
    if($transaction){DB::pdo()->beginTransaction();}

    foreach($settings as $setting){
      $setting['settingvalue'] = (isset($setting['settingvalue'])) ? $setting['settingvalue'] : "";
      if(!self::addSetting($elementid, $setting['settingkey'], $setting['settingvalue'], $setting['settingtype'])){
        // ERROR, ROLL BACK
        if($transaction){DB::pdo()->rollBack();}
        return false;
      }
    }

    // All done successfully
    if($transaction){DB::pdo()->commit();}
    return true;
  }

  public static function addSetting($elementid, $settingkey, $settingvalue, $settingtype){
    // If List
    if(is_array($settingvalue)){
      $settingvalue = PREFIX_SETTINGS_LIST.json_encode($settingvalue).PREFIX_SETTINGS_LIST;
    }

    $sth = DB::pdo()->prepare('INSERT INTO `'.Config::get()['database-tables']['elementsettings'].'`(`elementid`,`settingkey`,`settingvalue`, `settingtype`) VALUES (:elementid,:settingkey,:settingvalue,:settingtype)');
    $sth->bindValue(':elementid',$elementid,PDO::PARAM_STR);
    $sth->bindValue(':settingkey',$settingkey,PDO::PARAM_STR);
    $sth->bindValue(':settingvalue',$settingvalue,PDO::PARAM_STR);
    $sth->bindValue(':settingtype',$settingtype,PDO::PARAM_STR);
    return $sth->execute();
  }

  public static function getSettings($elementid){
    $sth = DB::pdo()->prepare('SELECT * FROM `'.Config::get()['database-tables']['elementsettings'].'` WHERE `elementid`=:elementid');
    $sth->bindValue(':elementid',$elementid,PDO::PARAM_STR);
    $sth->execute();
    $result = $sth->fetchAll(PDO::FETCH_ASSOC);

    $result = array_map(function($res){
      if(self::startsEndsWith($res['settingvalue'], PREFIX_SETTINGS_LIST)){
        $res['settingvalue'] = substr($res['settingvalue'], strlen(PREFIX_SETTINGS_LIST), strlen($res['settingvalue']) - (strlen(PREFIX_SETTINGS_LIST) * 2));
        $list = json_decode($res['settingvalue'], true);
        $res['settingvalue'] = $list;
      }
      return $res;
    }, $result);

    return $result;
  }

  public static function clearSettings($elementid){
    $sth = DB::pdo()->prepare('DELETE FROM `'.Config::get()['database-tables']['elementsettings'].'` WHERE `elementid`=:elementid');
    $sth->bindValue(':elementid',$elementid,PDO::PARAM_STR);
    return $sth->execute();
  }


  private static function startsEndsWith($haystack, $needle){
    $length = strlen($needle);
    return (substr($haystack, 0, $length) === $needle) && ($length == 0 || substr($haystack, -$length) === $needle);
  }

}
