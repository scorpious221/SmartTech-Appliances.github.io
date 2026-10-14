<?php
session_start();
require_once "database.php";

if(!isset($_SESSION["user_id"])||($_SESSION["role"]??"")!=="admin"){
    header("Location: ../login.php");
    exit;
}

if($_SERVER["REQUEST_METHOD"]!=="POST"){
    header("Location: ../admin.php?page=orders");
    exit;
}

$id=(int)($_POST["id"]??0);
$status=trim($_POST["status"]??"");

$allowed=[
    "Pending",
    "Processing",
    "Shipped",
    "Delivered",
    "Cancelled"
];

if($id<=0||!in_array($status,$allowed,true)){
    header("Location: ../admin.php?page=orders");
    exit;
}

$stmt=$conn->prepare("
    UPDATE orders
    SET status=?
    WHERE id=?
");

$stmt->execute([
    $status,
    $id
]);

header("Location: ../admin.php?page=orders");
exit;
?>