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
if(!isset($_SESSION["csrf_token"],$_POST["csrf_token"])||!hash_equals($_SESSION["csrf_token"],(string)$_POST["csrf_token"])){
    http_response_code(403);
    exit("Your session has expired. Refresh the page and try again.");
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

try{
    $conn->beginTransaction();
    $stmt=$conn->prepare("UPDATE orders SET status=? WHERE id=?");
    $stmt->execute([$status,$id]);

    if($status==="Delivered"){
        $stmt=$conn->prepare("
            INSERT INTO order_delivery_events(order_id,delivered_at)
            VALUES(?,NOW())
            ON DUPLICATE KEY UPDATE delivered_at=COALESCE(delivered_at,NOW())
        ");
        $stmt->execute([$id]);
    }

    $conn->commit();
}catch(Throwable $error){
    if($conn->inTransaction())$conn->rollBack();
    throw $error;
}

header("Location: ../admin.php?page=orders");
exit;
?>