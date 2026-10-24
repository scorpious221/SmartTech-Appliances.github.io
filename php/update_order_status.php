<?php
session_start();
require_once "database.php";

header("Content-Type: application/json; charset=utf-8");

function orderStatusResponse(int $statusCode,array $payload):void{
    http_response_code($statusCode);
    echo json_encode($payload,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    exit;
}

if(!isset($_SESSION["user_id"])||($_SESSION["role"]??"")!=="admin"){
    orderStatusResponse(401,["success"=>false,"message"=>"Please sign in with an admin account."]);
}

if($_SERVER["REQUEST_METHOD"]!=="POST"){
    orderStatusResponse(405,["success"=>false,"message"=>"This request method is not allowed."]);
}

if(!isset($_SESSION["csrf_token"],$_POST["csrf_token"])||!hash_equals($_SESSION["csrf_token"],(string)$_POST["csrf_token"])){
    orderStatusResponse(403,["success"=>false,"message"=>"Your session has expired. Refresh the page and try again."]);
}

$id=filter_var($_POST["id"]??null,FILTER_VALIDATE_INT);
$status=trim((string)($_POST["status"]??""));
$reason=trim((string)($_POST["reason"]??""));
$details=trim((string)($_POST["details"]??""));
$allowedStatuses=["Pending","Processing","Shipped","Delivered","Cancelled"];
$allowedReasons=[
    "Customer requested cancellation",
    "Unable to fulfill order",
    "Payment issue",
    "Item unavailable",
    "Other"
];

if($id===false||$id<1||!in_array($status,$allowedStatuses,true)){
    orderStatusResponse(400,["success"=>false,"message"=>"The order or selected status is invalid."]);
}

if($status==="Cancelled"&&(
    !in_array($reason,$allowedReasons,true)||
    strlen($details)>500||
    ($reason==="Other"&&$details==="")
)){
    orderStatusResponse(400,[
        "success"=>false,
        "message"=>$reason==="Other"&&$details===""
            ?"Please explain the cancellation reason."
            :"Choose a valid cancellation reason."
    ]);
}

try{
    $conn->beginTransaction();
    $stmt=$conn->prepare("SELECT status FROM orders WHERE id=? FOR UPDATE");
    $stmt->execute([$id]);
    $currentStatus=$stmt->fetchColumn();

    if($currentStatus===false){
        $conn->rollBack();
        orderStatusResponse(404,["success"=>false,"message"=>"Order not found."]);
    }

    if($currentStatus==="Cancelled"){
        $conn->rollBack();
        orderStatusResponse(409,["success"=>false,"message"=>"Cancelled orders cannot have their status changed."]);
    }

    if($status==="Cancelled"){
        if(!in_array($currentStatus,["Pending","Processing"],true)){
            $conn->rollBack();
            orderStatusResponse(409,["success"=>false,"message"=>"Orders can no longer be cancelled after they have shipped."]);
        }

        $stmt=$conn->prepare("
            UPDATE orders
            SET status='Cancelled',cancellation_reason=?,cancellation_details=?
            WHERE id=?
        ");
        $stmt->execute([$reason,$details!==""?$details:null,$id]);
    }else{
        $stmt=$conn->prepare("UPDATE orders SET status=? WHERE id=?");
        $stmt->execute([$status,$id]);
    }

    if($status==="Delivered"){
        $stmt=$conn->prepare("
            INSERT INTO order_delivery_events(order_id,delivered_at)
            VALUES(?,NOW())
            ON DUPLICATE KEY UPDATE delivered_at=COALESCE(delivered_at,NOW())
        ");
        $stmt->execute([$id]);
    }

    $conn->commit();
    orderStatusResponse(200,["success"=>true,"message"=>"Order status saved."]);
}catch(Throwable $error){
    if($conn->inTransaction())$conn->rollBack();
    error_log("Admin order status update failed: ".$error->getMessage());
    orderStatusResponse(500,["success"=>false,"message"=>"Unable to update the order status right now. Please try again."]);
}
?>
