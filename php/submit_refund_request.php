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
$itemId=(int)($_POST["order_item_id"]??0);
$reason=trim($_POST["reason"]??"");
$details=trim($_POST["details"]??"");
$allowedReasons=["Damaged or defective","Wrong item","Missing parts","Other"];

if(!in_array($reason,$allowedReasons,true)||strlen($details)<10||strlen($details)>1000){
    $_SESSION["account_message"]="Choose a reason and describe the problem in at least 10 characters.";
    returnToOrders();
}

try{
    $conn->beginTransaction();
    $stmt=$conn->prepare("
        SELECT o.status,e.delivered_at,
               (e.delivered_at>=DATE_SUB(NOW(),INTERVAL 7 DAY)) AS within_window
        FROM orders o
        JOIN order_delivery_events e ON e.order_id=o.id
        JOIN order_items oi ON oi.order_id=o.id
        WHERE o.id=? AND o.user_id=? AND oi.id=?
        FOR UPDATE
    ");
    $stmt->execute([$orderId,(int)$_SESSION["user_id"],$itemId]);
    $order=$stmt->fetch(PDO::FETCH_ASSOC);

    if(!$order||!in_array($order["status"],["Delivered","Completed"],true)||empty($order["within_window"])){
        $conn->rollBack();
        $_SESSION["account_message"]="Refund/return requests are available only within 7 days after delivery.";
        returnToOrders();
    }

    $stmt=$conn->prepare("SELECT id FROM refund_requests WHERE order_item_id=? AND status='Pending' LIMIT 1");
    $stmt->execute([$itemId]);
    if($stmt->fetchColumn()){
        $conn->rollBack();
        $_SESSION["account_message"]="A request for this item is already waiting for review.";
        returnToOrders();
    }

    $stmt=$conn->prepare("
        INSERT INTO refund_requests(order_id,order_item_id,user_id,reason,details)
        VALUES(?,?,?,?,?)
    ");
    $stmt->execute([$orderId,$itemId,(int)$_SESSION["user_id"],$reason,$details]);
    $conn->commit();
    $_SESSION["account_message"]="Your return/refund request was submitted for admin review. No refund is processed automatically.";
}catch(Throwable $error){
    if($conn->inTransaction())$conn->rollBack();
    throw $error;
}
returnToOrders();
