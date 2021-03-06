<?php
use Dompdf\Dompdf;
use KFall\oxymora\database\DB;
// ========================================
//  FUNCTIONS
// ========================================

// Create Invoice
function createInvoice($template, $customer, $items){
  require_once __DIR__.'/dompdf/autoload.inc.php';
  require_once __DIR__.'/class.customer.php';
  // create db reference
  $id = createDBReference();
  if($id === false){return false;}

  $to = new Customer($customer);

  // html invoice
  $html = createHTMLInvoice($id, $template, $to->getAssoc(), $items);
  $filename = "invoice-$id.pdf";
  $filepath = __DIR__."/../../invoices/$filename";

  // save to reference
  addDataToDBReference($id, $filename, $customer, json_encode($items));

  // instantiate and use the dompdf class
  $dompdf = new Dompdf();
  $dompdf->loadHtml($html);

  // Setup the paper size and orientation
  $dompdf->setPaper('A4', 'portrait');

  // Render the HTML as PDF
  $dompdf->render();

  // Save it
  file_put_contents($filepath, $dompdf->output());
  file_put_contents($filepath.".html", $html);
}

// create html invoice
function createHTMLInvoice($id, $template, $to, $items){
  require_once __DIR__."/../../template/$template.php";
  $invoice = new Template($id, date("d.m.Y"), $to, $items);
  return $invoice->getHtml();
}

// put in db
function createDBReference(){
  $pdo = DB::pdo();
  $prep = $pdo->prepare("INSERT INTO `".TABLE."`() VALUES ()");
  if($prep->execute()){
    return $pdo->lastInsertId();
  }else{
    return false;
  }
}

// put file in reference
function addDataToDBReference($id, $file, $customer, $items){
  $pdo = DB::pdo();
  $prep = $pdo->prepare("UPDATE `".TABLE."` SET `file`=:file,`customer`=:customer,`items`=:items WHERE `id`=:id");
  $prep->bindValue(':id',$id);
  $prep->bindValue(':file',$file);
  $prep->bindValue(':customer',$customer);
  $prep->bindValue(':items',$items);
  return $prep->execute();
}

// get customer
function getCustomer(){
  require_once __DIR__.'/class.customer.php';
  $pdo = DB::pdo();
  $prep = $pdo->prepare("SELECT * FROM `".TABLE_CUSTOMER."` ORDER BY `id` desc");
  $prep->execute();
  $customer = [];
  foreach($prep->fetchAll(PDO::FETCH_ASSOC) as $cdata){
    $c = new Customer();
    foreach($cdata as $key => $value){
      $c->$key = $value;
    }
    $customer[] = $c;
  }
  return $customer;
}

function addCustomer($data){
  require_once __DIR__.'/class.customer.php';
  $c = new Customer();
  foreach($data as $key => $value){
    if(strtolower($key) === "id") continue;
    $c->$key = $value;
  }
  $c->save();
  return true;
}

function deleteCustomer($id){
  require_once __DIR__.'/class.customer.php';
  $pdo = DB::pdo();
  $prep = $pdo->prepare("DELETE FROM `".TABLE_CUSTOMER."` WHERE `id`=:id");
  $prep->bindValue(':id', $id);
  return $prep->execute();
}

function setInvoiceStatus($id, $status){
  $pdo = DB::pdo();
  $prep = $pdo->prepare("UPDATE `".TABLE."` SET `status`=:status WHERE `id`=:id");
  $prep->bindValue(':id',$id);
  $prep->bindValue(':status',$status);
  return $prep->execute();
}

function rollBack(){
  $pdo = DB::pdo();

  // Find
  $prep = $pdo->prepare("SELECT `".TABLE."`.*, `".TABLE_CUSTOMER."`.`firstname`, `".TABLE_CUSTOMER."`.`lastname`
  FROM `".TABLE."` LEFT JOIN `".TABLE_CUSTOMER."` ON `".TABLE."`.`customer`=`".TABLE_CUSTOMER."`.`id` ORDER BY `id` desc LIMIT 1");
  $success = $prep->execute();
  if(!$success){die('something went wrong!');}
  $invoice = $prep->fetch(PDO::FETCH_ASSOC);
  $id = $invoice['id'];

  // Delete from DB
  $pdo->exec("DELETE FROM  `".TABLE."` WHERE `id`=$id");

  // Delete Files
  $filename = "invoice-$id.pdf";
  $filepath = __DIR__."/../../invoices/$filename";
  $filepath2 = $filepath.".html";
  unlink($filepath);
  unlink($filepath2);

  // set id one back
  $newid = intval($id)-1;
  setNextId($newid);

  return true;
}

function getNextId(){
  $pdo = DB::pdo();
  $prep = $pdo->prepare("SELECT `auto_increment` FROM INFORMATION_SCHEMA.TABLES WHERE table_name = '".TABLE."'");
  $success = $prep->execute();
  if(!$success){die('something went wrong!');}
  return $prep->fetch(PDO::FETCH_ASSOC)['auto_increment'];
}

function setNextId($id){
  $pdo = DB::pdo();
  $prep = $pdo->prepare("ALTER TABLE `".TABLE."` AUTO_INCREMENT = :id");
  $prep->bindValue(':id', intval($id), PDO::PARAM_INT);
  $success = $prep->execute();
  if(!$success){die('something went wrong!');}
  return true;
}
