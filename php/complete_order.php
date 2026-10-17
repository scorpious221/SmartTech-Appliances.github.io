<?php
session_start();
require_once "database.php";

function returnToOrders():void{
    header("Location: profile.php?tab=orders");
    exit;
}

if(!isset($_SESSION["user_id"])||($_SESSION["role"]??"")!=="customer"){
    header("Location: ../login.php?next=php/profile.php");
    exit;
}
if($_SERVER["REQUEST_METHOD"]!=="POST"){
    returnToOrders();
}
if(!isset($_SESSION["csrf_token"],$_POST["csrf_token"])||!hash_equals($_SESSION["csrf_token"],(string)$_POST["csrf_token"])){
    http_response_code(403);
    exit("Your session has expired. Refresh the page and try again.");
}

$orderId=(int)($_POST["order_id"]??0);
try{
    $conn->beginTransaction();
    $stmt=$conn->prepare("
        SELECT o.status,e.delivered_at
        FROM orders o
        LEFT JOIN order_delivery_events e ON e.order_id=o.id
        WHERE o.id=? AND o.user_id=?
        FOR UPDATE
    ");
    $stmt->execute([$orderId,(int)$_SESSION["user_id"]]);
    $order=$stmt->fetch(PDO::FETCH_ASSOC);

    if(!$order||$order["status"]!=="Delivered"||empty($order["delivered_at"])){
        $conn->rollBack();
        $_SESSION["account_message"]="Only a delivered order can be marked complete.";
        returnToOrders();
    }

    $stmt=$conn->prepare("UPDATE orders SET status='Completed' WHERE id=? AND user_id=? AND status='Delivered'");
    $stmt->execute([$orderId,(int)$_SESSION["user_id"]]);
    if($stmt->rowCount()!==1){
        $conn->rollBack();
        $_SESSION["account_message"]="This order has already been updated.";
        returnToOrders();
    }
    $stmt=$conn->prepare("UPDATE order_delivery_events SET completed_at=NOW() WHERE order_id=?");
    $stmt->execute([$orderId]);
    $conn->commit();
    $_SESSION["account_message"]="Order completed. You can now review the items you purchased.";
}catch(Throwable $error){
    if($conn->inTransaction())$conn->rollBack();
    throw $error;
}
returnToOrders();
