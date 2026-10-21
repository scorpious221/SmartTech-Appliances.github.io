<?php
session_start();
require_once "database.php";

if(!isset($_SESSION["user_id"])||($_SESSION["role"]??"")!=="admin"){
header("Location: ../login.php");
exit;
}

if($_SERVER["REQUEST_METHOD"]!=="POST"){
header("Location: ../admin.php?page=products");
exit;
}

$id=(int)($_POST["id"]??0);
$amount=(int)($_POST["amount"]??0);

if($id<=0||!in_array($amount,[-1,1],true)){
header("Location: ../admin.php?page=products");
exit;
}

$stmt=$conn->prepare("SELECT stock FROM products WHERE id=? LIMIT 1");
$stmt->execute([$id]);
$current=$stmt->fetchColumn();

if($current===false){
header("Location: ../admin.php?page=products");
exit;
}

$newStock=(int)$current+$amount;

if($newStock<0)$newStock=0;
if($newStock>100)$newStock=100;

$stmt=$conn->prepare("UPDATE products SET stock=? WHERE id=?");
$stmt->execute([$newStock,$id]);

header("Location: ../admin.php?page=products");
exit;
?>