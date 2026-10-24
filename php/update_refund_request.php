<?php
session_start();
require_once "database.php";

if(!isset($_SESSION["user_id"])||($_SESSION["role"]??"")!=="admin"){
    header("Location: ../login.php");
    exit;
}
if($_SERVER["REQUEST_METHOD"]!=="POST"){
    header("Location: ../admin.php?page=refunds");
    exit;
}
if(!isset($_SESSION["csrf_token"],$_POST["csrf_token"])||!hash_equals($_SESSION["csrf_token"],(string)$_POST["csrf_token"])){
    http_response_code(403);
    exit("Your session has expired. Refresh the page and try again.");
}

$requestId=(int)($_POST["id"]??0);
$status=trim($_POST["status"]??"");
$note=trim($_POST["admin_note"]??"");
if($requestId<=0||!in_array($status,["Approved","Rejected"],true)||strlen($note)>1000){
    header("Location: ../admin.php?page=refunds");
    exit;
}

$stmt=$conn->prepare("
    UPDATE refund_requests
    SET status=?,admin_note=?,reviewed_by=?,reviewed_at=NOW()
    WHERE id=? AND status='Pending'
");
$stmt->execute([$status,$note?:null,(int)$_SESSION["user_id"],$requestId]);

header("Location: ../admin.php?page=refunds");
exit;
