<?php
session_start();
require_once "database.php";

if(!isset($_SESSION["user_id"])||($_SESSION["role"]??"")!=="customer"){
    header("Location: ../login.php?next=php/profile.php%3Ftab%3Dorders");
    exit;
}

if($_SERVER["REQUEST_METHOD"]!=="POST"){
    header("Location: profile.php?tab=orders");
    exit;
}

if(!isset($_SESSION["csrf_token"],$_POST["csrf_token"])||!hash_equals($_SESSION["csrf_token"],(string)$_POST["csrf_token"])){
    http_response_code(403);
    exit("Your session has expired. Refresh the page and try again.");
}

$orderId=filter_var($_POST["order_id"]??null,FILTER_VALIDATE_INT);
$reason=trim((string)($_POST["reason"]??""));
$details=trim((string)($_POST["details"]??""));
$allowedReasons=[
    "Changed my mind",
    "Ordered by mistake",
    "Found a better price",
    "Want to change my items or address",
    "Delivery is taking too long",
    "Payment issue",
    "No longer need the item",
    "Other"
];

if($orderId===false||$orderId<1||!in_array($reason,$allowedReasons,true)||strlen($details)>500||($reason==="Other"&&$details==="")){
    $_SESSION["account_message"]="Choose a valid cancellation reason".($reason==="Other"?" and tell us more.":".");
    header("Location: profile.php?tab=orders");
    exit;
}

try{
    $stmt=$conn->prepare("
        UPDATE orders
        SET status='Cancelled',cancellation_reason=?,cancellation_details=?
        WHERE id=? AND user_id=? AND status IN ('Pending','Processing')
    ");
    $stmt->execute([$reason,$details!==""?$details:null,$orderId,(int)$_SESSION["user_id"]]);

    if($stmt->rowCount()!==1){
        $_SESSION["account_message"]="This order can no longer be cancelled. Orders may only be cancelled before they ship.";
    }else{
        $_SESSION["account_message"]="Your order has been cancelled. Thank you for letting us know why.";
    }
}catch(PDOException $error){
    error_log("Order cancellation failed: ".$error->getMessage());
    http_response_code(500);
    exit("Unable to cancel this order right now. Please try again.");
}

header("Location: profile.php?tab=orders");
exit;
?>
