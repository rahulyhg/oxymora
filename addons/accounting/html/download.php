<?php
use KFall\oxymora\database\DB;
var_dump($_POST);

$table = "accounting_invoices";
$pdo = DB::pdo();
// GET LATEST VISITORS
$prep = $pdo->prepare("SELECT * FROM `".$table."` WHERE `id`=:id");
$prep->bindValue(':id', $_POST['invoice']);
$success = $prep->execute();
if(!$success || $prep->rowCount() < 1){die('something went wrong!');}
$invoice = $prep->fetch(PDO::FETCH_ASSOC);

$filename = $invoice['file'];
$filepath = __DIR__."/../invoices/$filename";

header("Content-type:application/pdf");
header("Content-Disposition:attachment;filename='$filename'");
readfile($filepath);
