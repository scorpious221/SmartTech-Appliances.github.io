<?php
session_start();
require_once "database.php";
require_once "vouchers.php";

if(!isset($_SESSION["user_id"])||($_SESSION["role"]??"")!=="customer"){
    header("Location: ../login.php?next=php/profile.php");
    exit;
}

$userId=(int)$_SESSION["user_id"];
$stmt=$conn->prepare("SELECT COUNT(*) FROM orders WHERE user_id=?");
$stmt->execute([$userId]);
$firstOrderAvailable=(int)$stmt->fetchColumn()===0;
$availableVouchers=voucherOffers($conn);
$message=$_SESSION["account_message"]??"";
unset($_SESSION["account_message"]);
$error=false;
$addressId=(int)($_POST["address_id"]??0);
if(empty($_SESSION["csrf_token"]))$_SESSION["csrf_token"]=bin2hex(random_bytes(32));
$csrfToken=$_SESSION["csrf_token"];

if($_SERVER["REQUEST_METHOD"]==="POST"&&(!isset($_POST["csrf_token"])||!hash_equals($csrfToken,(string)$_POST["csrf_token"]))){
    http_response_code(403);
    exit("Your session has expired. Refresh the page and try again.");
}

function syncDefaultAddress(PDO $conn,int $userId,?array $address):void{
    if(!$address){
        $stmt=$conn->prepare("UPDATE users SET contact=NULL,address=NULL WHERE id=?");
        $stmt->execute([$userId]);
        return;
    }

    $formatted=$address["address_line"].", Barangay ".$address["barangay"].", ".$address["city"].", ".$address["province"]." ".$address["postal_code"];
    $stmt=$conn->prepare("UPDATE users SET contact=?,address=? WHERE id=?");
    $stmt->execute([$address["phone"],$formatted,$userId]);
}

if($_SERVER["REQUEST_METHOD"]==="POST"&&($_POST["action"]??"")==="avatar"){
    if(!isset($_FILES["profile_image"])||$_FILES["profile_image"]["error"]===UPLOAD_ERR_NO_FILE){
        $message="Choose a profile picture to upload.";
        $error=true;
    }elseif($_FILES["profile_image"]["error"]===UPLOAD_ERR_INI_SIZE||$_FILES["profile_image"]["error"]===UPLOAD_ERR_FORM_SIZE){
        $message="Profile pictures must be 3 MB or smaller.";
        $error=true;
    }elseif($_FILES["profile_image"]["error"]!==UPLOAD_ERR_OK){
        $message="The profile picture could not be uploaded. Please try again.";
        $error=true;
    }elseif($_FILES["profile_image"]["size"]>3*1024*1024){
        $message="Profile pictures must be 3 MB or smaller.";
        $error=true;
    }else{
        $tmpPath=$_FILES["profile_image"]["tmp_name"];
        $fileInfo=new finfo(FILEINFO_MIME_TYPE);
        $mimeType=$fileInfo->file($tmpPath);
        $imageInfo=@getimagesize($tmpPath);
        $extensions=[
            "image/jpeg"=>"jpg",
            "image/png"=>"png",
            "image/webp"=>"webp"
        ];

        if(!$imageInfo||!isset($extensions[$mimeType])||$imageInfo["mime"]!==$mimeType){
            $message="Choose a valid JPG, PNG, or WebP image.";
            $error=true;
        }else{
            $uploadDirectory=__DIR__."/../images/profile-pictures";

            if(!is_dir($uploadDirectory)&&!mkdir($uploadDirectory,0755,true)&&!is_dir($uploadDirectory)){
                throw new RuntimeException("Unable to create the profile picture folder.");
            }

            $filename=$userId."_".bin2hex(random_bytes(12)).".".$extensions[$mimeType];
            $destination=$uploadDirectory."/".$filename;

            if(!move_uploaded_file($tmpPath,$destination)){
                throw new RuntimeException("Unable to save the uploaded profile picture.");
            }

            try{
                $stmt=$conn->prepare("UPDATE users SET profile_image=? WHERE id=?");
                $stmt->execute(["images/profile-pictures/".$filename,$userId]);
                $message="Profile picture updated.";
            }catch(Throwable $e){
                unlink($destination);
                throw $e;
            }
        }
    }
}

if($_SERVER["REQUEST_METHOD"]==="POST"&&($_POST["action"]??"")==="profile"){
    $email=trim($_POST["email"]??"");
    $name=trim($_POST["full_name"]??"");
    $contact=preg_replace("/\D/","",$_POST["contact"]??"");

    if(!$email||!$name||($contact&&strlen($contact)<10)){
        $message="Please complete all fields.";
        $error=true;
    }elseif(!filter_var($email,FILTER_VALIDATE_EMAIL)){
        $message="Enter a valid email address.";
        $error=true;
    }else{
        try{
            $stmt=$conn->prepare("UPDATE users SET email=?,full_name=?,contact=? WHERE id=?");
            $stmt->execute([$email,$name,$contact?:null,$userId]);
            $_SESSION["full_name"]=$name;
            $message="Profile updated successfully.";
        }catch(PDOException $e){
            $message=$e->getCode()==="23000"?"That email is already being used.":"Unable to update profile.";
            $error=true;
        }
    }
}

if($_SERVER["REQUEST_METHOD"]==="POST"&&($_POST["action"]??"")==="address"){
    $label=trim($_POST["label"]??"Home");
    $recipient=trim($_POST["recipient_name"]??"");
    $phone=preg_replace("/\D/","",$_POST["phone"]??"");
    $line=trim($_POST["address_line"]??"");
    $barangay=trim($_POST["barangay"]??"");
    $city=trim($_POST["city"]??"");
    $province=trim($_POST["province"]??"");
    $postal=trim($_POST["postal_code"]??"");

    if(!in_array($label,["Home","Work","Other"],true)||!$recipient||strlen($phone)<10||!$line||!$barangay||!$city||!$province||!$postal){
        $message="Complete all delivery address fields with a valid phone number.";
        $error=true;
    }else{
        $wasDefault=false;
        if($addressId){
            $stmt=$conn->prepare("SELECT is_default FROM user_addresses WHERE id=? AND user_id=?");
            $stmt->execute([$addressId,$userId]);
            $existingDefault=$stmt->fetchColumn();
            if($existingDefault===false){
                $message="That saved address could not be found.";
                $error=true;
            }else{
                $wasDefault=(int)$existingDefault===1;
            }
        }

        if(!$error){
            $check=$conn->prepare("SELECT COUNT(*) FROM user_addresses WHERE user_id=?");
            $check->execute([$userId]);
            $makeDefault=!empty($_POST["is_default"])||$wasDefault||(int)$check->fetchColumn()===0;

            try{
                $conn->beginTransaction();

                if($makeDefault){
                    $stmt=$conn->prepare("UPDATE user_addresses SET is_default=0 WHERE user_id=?");
                    $stmt->execute([$userId]);
                }

                if($addressId){
                    $stmt=$conn->prepare("UPDATE user_addresses SET label=?,recipient_name=?,phone=?,address_line=?,barangay=?,city=?,province=?,postal_code=?,is_default=? WHERE id=? AND user_id=?");
                    $stmt->execute([$label,$recipient,$phone,$line,$barangay,$city,$province,$postal,(int)$makeDefault,$addressId,$userId]);
                    if(!$stmt->rowCount()){
                        $check=$conn->prepare("SELECT id FROM user_addresses WHERE id=? AND user_id=?");
                        $check->execute([$addressId,$userId]);
                        if(!$check->fetchColumn())throw new RuntimeException("That saved address could not be found.");
                        $stmt=$conn->prepare("UPDATE user_addresses SET is_default=? WHERE id=? AND user_id=?");
                        $stmt->execute([(int)$makeDefault,$addressId,$userId]);
                    }
                    $savedId=$addressId;
                }else{
                    $stmt=$conn->prepare("INSERT INTO user_addresses(user_id,label,recipient_name,phone,address_line,barangay,city,province,postal_code,is_default) VALUES(?,?,?,?,?,?,?,?,?,?)");
                    $stmt->execute([$userId,$label,$recipient,$phone,$line,$barangay,$city,$province,$postal,(int)$makeDefault]);
                    $savedId=(int)$conn->lastInsertId();
                }

                if($makeDefault){
                    $stmt=$conn->prepare("SELECT * FROM user_addresses WHERE id=? AND user_id=?");
                    $stmt->execute([$savedId,$userId]);
                    syncDefaultAddress($conn,$userId,$stmt->fetch(PDO::FETCH_ASSOC)?:null);
                }

                $conn->commit();
                $message="Delivery address saved.";
            }catch(Throwable $e){
                if($conn->inTransaction())$conn->rollBack();
                throw $e;
            }
        }
    }
}

if($_SERVER["REQUEST_METHOD"]==="POST"&&($_POST["action"]??"")==="default_address"){
    $stmt=$conn->prepare("SELECT * FROM user_addresses WHERE id=? AND user_id=?");
    $stmt->execute([$addressId,$userId]);
    $selected=$stmt->fetch(PDO::FETCH_ASSOC);

    if(!$selected){
        $message="That saved address could not be found.";
        $error=true;
    }else{
        $conn->beginTransaction();
        $stmt=$conn->prepare("UPDATE user_addresses SET is_default=0 WHERE user_id=?");
        $stmt->execute([$userId]);
        $stmt=$conn->prepare("UPDATE user_addresses SET is_default=1 WHERE id=? AND user_id=?");
        $stmt->execute([$addressId,$userId]);
        syncDefaultAddress($conn,$userId,$selected);
        $conn->commit();
        $message="Default delivery address updated.";
    }
}

if($_SERVER["REQUEST_METHOD"]==="POST"&&($_POST["action"]??"")==="delete_address"){
    $stmt=$conn->prepare("SELECT * FROM user_addresses WHERE id=? AND user_id=?");
    $stmt->execute([$addressId,$userId]);
    $deleted=$stmt->fetch(PDO::FETCH_ASSOC);

    if(!$deleted){
        $message="That saved address could not be found.";
        $error=true;
    }else{
        $conn->beginTransaction();
        $stmt=$conn->prepare("DELETE FROM user_addresses WHERE id=? AND user_id=?");
        $stmt->execute([$addressId,$userId]);

        if((int)$deleted["is_default"]===1){
            $stmt=$conn->prepare("SELECT * FROM user_addresses WHERE user_id=? ORDER BY id ASC LIMIT 1");
            $stmt->execute([$userId]);
            $next=$stmt->fetch(PDO::FETCH_ASSOC)?:null;
            if($next){
                $stmt=$conn->prepare("UPDATE user_addresses SET is_default=1 WHERE id=? AND user_id=?");
                $stmt->execute([$next["id"],$userId]);
            }
            syncDefaultAddress($conn,$userId,$next);
        }

        $conn->commit();
        $message="Delivery address removed.";
    }
}

if($_SERVER["REQUEST_METHOD"]==="POST"&&($_POST["action"]??"")==="password"){
    $current=$_POST["current_password"]??"";
    $new=$_POST["new_password"]??"";
    $confirm=$_POST["confirm_password"]??"";

    $stmt=$conn->prepare("SELECT password FROM users WHERE id=?");
    $stmt->execute([$userId]);
    $hash=$stmt->fetchColumn();

    if(!$hash||!password_verify($current,$hash)){
        $message="Current password is incorrect.";
        $error=true;
    }elseif(strlen($new)<8){
        $message="New password must be at least 8 characters.";
        $error=true;
    }elseif($new!==$confirm){
        $message="Passwords do not match.";
        $error=true;
    }else{
        $stmt=$conn->prepare("UPDATE users SET password=? WHERE id=?");
        $stmt->execute([password_hash($new,PASSWORD_DEFAULT),$userId]);
        $message="Password updated successfully.";
    }
}

if($_SERVER["REQUEST_METHOD"]==="POST"&&($_POST["action"]??"")==="app_feedback"){
    $rating=(int)($_POST["rating"]??0);
    $comment=trim($_POST["comment"]??"");

    if($rating<1||$rating>5||strlen($comment)>1000){
        $message="Choose a rating from 1 to 5 and keep feedback under 1,000 characters.";
        $error=true;
    }else{
        $stmt=$conn->prepare("
            INSERT INTO app_feedback(user_id,rating,comment)
            VALUES(?,?,?)
            ON DUPLICATE KEY UPDATE rating=VALUES(rating),comment=VALUES(comment),updated_at=CURRENT_TIMESTAMP
        ");
        $stmt->execute([$userId,$rating,$comment?:null]);
        $message="Thanks for rating the SmartTech web app.";
    }
}

if($_SERVER["REQUEST_METHOD"]==="POST"&&($_POST["action"]??"")==="delete_account_request"){
    if(empty($_POST["confirm_deletion_request"])){
        $message="Please confirm that you want to submit an account deletion request.";
        $error=true;
    }else{
        $stmt=$conn->prepare("SELECT id FROM account_deletion_requests WHERE user_id=? AND status='Pending' LIMIT 1");
        $stmt->execute([$userId]);
        if($stmt->fetchColumn()){
            $message="You already have a pending account deletion request.";
        }else{
            $stmt=$conn->prepare("INSERT INTO account_deletion_requests(user_id) VALUES(?)");
            $stmt->execute([$userId]);
            $message="Your account deletion request was sent for review. Your account has not been deleted.";
        }
    }
}

$stmt=$conn->prepare("SELECT id,username,email,full_name,age,sex,birthday,contact,address,profile_image FROM users WHERE id=? AND role='customer' LIMIT 1");
$stmt->execute([$userId]);
$user=$stmt->fetch(PDO::FETCH_ASSOC);

if(!$user){
    session_destroy();
    header("Location: ../login.php");
    exit;
}

$stmt=$conn->prepare("SELECT * FROM user_addresses WHERE user_id=? ORDER BY is_default DESC,id DESC");
$stmt->execute([$userId]);
$addresses=$stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt=$conn->prepare("
    SELECT o.id,o.order_number,o.status,o.grand_total,o.voucher_code,o.discount_amount,
           o.cancellation_reason,o.cancellation_details,o.created_at,e.delivered_at,
           (e.delivered_at>=DATE_SUB(NOW(),INTERVAL 7 DAY)) AS refund_window_open
    FROM orders o
    LEFT JOIN order_delivery_events e ON e.order_id=o.id
    WHERE o.user_id=?
    ORDER BY o.id DESC
");
$stmt->execute([$userId]);
$orders=$stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt=$conn->prepare("
    SELECT oi.id AS order_item_id,oi.order_id,oi.product_id,oi.product_name,oi.price,oi.quantity,
           p.product_code,p.image AS product_image,pr.id AS review_id,
           rr.status AS refund_status,rr.admin_note AS refund_admin_note
    FROM orders o
    JOIN order_items oi ON oi.order_id=o.id
    LEFT JOIN products p ON p.id=oi.product_id
    LEFT JOIN product_reviews pr ON pr.order_item_id=oi.id
    LEFT JOIN refund_requests rr ON rr.order_item_id=oi.id
       AND rr.id=(SELECT MAX(rr2.id) FROM refund_requests rr2 WHERE rr2.order_item_id=oi.id)
    WHERE o.user_id=?
    ORDER BY oi.id
");
$stmt->execute([$userId]);
$orderItems=[];
while($orderItem=$stmt->fetch(PDO::FETCH_ASSOC)){
    $orderItems[(int)$orderItem["order_id"]][]=$orderItem;
}

$purchaseCounts=["pay"=>0,"ship"=>0,"receive"=>0,"rate"=>0];
foreach($orders as $order){
    if($order["status"]==="Pending")$purchaseCounts["pay"]++;
    if($order["status"]==="Processing")$purchaseCounts["ship"]++;
    if(in_array($order["status"],["Shipped","Delivered"],true))$purchaseCounts["receive"]++;
    if($order["status"]==="Completed"){
        foreach($orderItems[(int)$order["id"]]??[] as $orderItem){
            if(!$orderItem["review_id"]&&!empty($orderItem["product_code"]))$purchaseCounts["rate"]++;
        }
    }
}

$stmt=$conn->prepare("SELECT rating,comment FROM app_feedback WHERE user_id=? LIMIT 1");
$stmt->execute([$userId]);
$appFeedback=$stmt->fetch(PDO::FETCH_ASSOC)?:["rating"=>"","comment"=>""];
$stmt=$conn->prepare("SELECT status,requested_at FROM account_deletion_requests WHERE user_id=? ORDER BY id DESC LIMIT 1");
$stmt->execute([$userId]);
$deletionRequest=$stmt->fetch(PDO::FETCH_ASSOC)?:null;

function e($v){
    return htmlspecialchars((string)$v,ENT_QUOTES,"UTF-8");
}

function peso($v){
    return "₱".number_format((float)$v,2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>My Profile — SmartTech Appliances</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<link href="../styles.css?v=mobile-header-search-2" rel="stylesheet">
<link href="../shop.css" rel="stylesheet">
<link href="../pages.css?v=mobile-gutters-2" rel="stylesheet">

<style>
.account-order-items{display:grid;gap:10px;width:100%;margin-top:6px}
.account-order-item{display:grid;grid-template-columns:52px minmax(0,1fr) auto;align-items:center;gap:8px 12px;padding:12px;border:1px solid var(--st-sand);border-radius:12px;background:#fff;font-size:14px}
.account-order-thumb{display:grid;width:52px;height:52px;place-items:center;overflow:hidden;border-radius:10px;background:#f1f2f6;color:var(--st-ink-2)}
.account-order-thumb img{width:100%;height:100%;padding:4px;object-fit:contain}
.account-order-thumb i{font-size:20px}
.account-order-product{min-width:0}
.account-order-product-name{display:block;line-height:1.45}
.account-order-item-actions{grid-column:1/-1;display:flex;gap:12px;flex-wrap:wrap}
.refund-admin-note{flex-basis:100%;padding:8px 10px;border-left:3px solid var(--st-clay);border-radius:0 8px 8px 0;background:var(--st-cream);color:var(--st-ink)!important;line-height:1.5}
.account-refund{grid-column:1/-1;margin-top:4px}
.account-refund summary{color:var(--st-clay);font-size:13px;font-weight:700;cursor:pointer}
.account-refund form{max-width:540px;padding-top:10px}
.account-refund textarea{display:block;width:100%;min-height:90px;margin-top:6px;padding:12px;border:1px solid var(--st-sand);border-radius:12px}
.account-cancel{flex:1 1 100%;margin-top:4px}
.account-cancel summary{color:#A3321A;font-size:13px;font-weight:700;cursor:pointer}
.account-cancel form{max-width:540px;padding-top:10px}
.account-cancel select,.account-cancel textarea{display:block;width:100%;margin-top:6px;padding:12px;border:1px solid var(--st-sand);border-radius:12px;background:#fff}
.account-cancel textarea{min-height:84px}
.account-cancel .cancel-details[hidden]{display:none}
.account-order-item a,.order>a{color:var(--st-clay);font-weight:700}
@media(max-width:991.98px){.account-order-item{font-size:13px}}
.pf-back-label{display:none}
.container-xxl.pg{padding-left:28px!important;padding-right:28px!important}.pf-back{display:none;align-items:center;justify-content:center;width:44px;height:44px;margin:0 0 12px -12px;border-radius:50%;color:var(--st-clay);font-size:26px;line-height:1;text-decoration:none;transition:background .2s,color .2s}.pf-back:hover{background:var(--st-sand);color:var(--st-ink)}.pf-top{display:flex;align-items:center;gap:20px;flex-wrap:wrap;margin-bottom:24px}.pf-av{width:72px;height:72px;border-radius:50%;background:var(--st-ink);color:#fff;display:grid;place-items:center;font-size:28px;font-weight:800;object-fit:cover;flex:none}.pf-avatar-wrap{position:relative;flex:none}.pf-avatar-edit{position:absolute;right:-4px;bottom:-4px;width:30px;height:30px;display:grid;place-items:center;border:2px solid #fff;border-radius:50%;background:var(--st-clay);color:#fff;cursor:pointer}.pf-avatar-edit:hover{background:var(--st-clay-2)}.pf-avatar-form input{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;clip-path:inset(50%)}.pf-top h1{font-size:30px;margin:0}.pf-top small{color:var(--st-ink-2)}.pf-page [hidden]{display:none!important}.pf{display:grid;grid-template-columns:230px 1fr;gap:24px}.pf-nav{padding:12px;display:flex;flex-direction:column;gap:4px}.pf-nav button,.pf-nav a{display:flex;align-items:center;gap:12px;min-height:48px;padding:0 14px;border:0;border-radius:12px;background:none;color:var(--st-ink);font-size:14px;font-weight:600;text-decoration:none;text-align:left}.pf-nav button{cursor:pointer}.pf-nav button:hover,.pf-nav a:hover{background:var(--st-cream)}.pf-nav button.active{background:var(--st-cream);color:var(--st-clay);font-weight:800}.pf-nav .out{color:#A3321A}.g2{display:grid;grid-template-columns:1fr 1fr;gap:0 16px}.g2 .full{grid-column:1/-1}.field select{display:block;width:100%;margin-top:6px;border:1px solid var(--st-sand);border-radius:12px;padding:14px;background:#fff}.field input[readonly]{background:var(--st-cream)}.orders{display:grid;gap:12px}.order{padding:16px;border:1px solid var(--st-sand);border-radius:14px;background:#fff;display:flex;justify-content:space-between;align-items:center;gap:16px;text-decoration:none}.order small{display:block;color:var(--st-ink-2);margin-top:4px}.status{font-weight:700;color:var(--st-clay)}.address-list{display:grid;gap:12px;margin-bottom:24px}.address-card{padding:18px;border:1px solid var(--st-sand);border-radius:16px;background:#fff}.address-card-head{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:10px}.address-card p{margin:0;color:var(--st-ink-2);font-size:14px;line-height:1.6}.address-badge{display:inline-flex;padding:4px 9px;border-radius:999px;background:#E9F2EE;color:#1B6B4A;font-size:11px;font-weight:700}.address-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}.address-actions form{margin:0}.address-action{min-height:40px;padding:0 12px;border:1px solid var(--st-sand);border-radius:999px;background:#fff;color:var(--st-ink);font-size:12px;font-weight:700}.address-action:hover{border-color:var(--st-clay);color:var(--st-clay)}.address-action.danger{color:#A3321A}.pf-support{margin-top:18px;padding:16px;border-radius:14px;background:var(--st-cream);font-size:13px;color:var(--st-ink-2)}.pf-support a{color:var(--st-clay);font-weight:700}
@media(max-width:991.98px){.container-xxl.pg{padding-left:20px!important;padding-right:20px!important}.pf-back{display:flex}.pf{grid-template-columns:1fr}.pf-nav{flex-direction:column;overflow:visible}.g2{grid-template-columns:1fr}.order{align-items:flex-start;flex-direction:column}}
</style>
<style>
@media(max-width:991.98px){
    body.pf-page{background:var(--st-cream)}
    .pf-page .pg{padding:0 20px 96px!important}
    .pf-page .pf-top{
        position:relative;
        width:100vw;
        min-height:300px;
        margin:0 0 0 calc(50% - 50vw);
        padding:76px 24px 60px;
        flex-direction:column;
        justify-content:center;
        gap:12px;
        background:var(--st-ink);
        color:var(--st-cream);
        text-align:center;
        border-radius:0 0 28px 28px;
    }
    .pf-page .pf-top h1{font-size:24px;line-height:1.25}
    .pf-page .pf-top small{color:var(--st-mist);font-size:12px;overflow-wrap:anywhere}
    .pf-page .pf-av{width:92px;height:92px;border:3px solid rgba(255,255,255,.85);font-size:34px}
    .pf-page .pf-avatar-edit{right:0;bottom:0;width:32px;height:32px}
    .pf-page .pf-back{
        position:absolute;
        z-index:2;
        top:16px;
        left:8px;
        margin:0;
        color:var(--st-cream);
    }
    .pf-page .pf-back:hover{background:rgba(255,255,255,.12);color:#fff}
    .pf-page .pf-back[data-back-to-overview="true"]{
        width:auto;
        gap:2px;
        padding:0 12px 0 4px;
        border:1px solid rgba(255,255,255,.35);
        border-radius:999px;
        background:rgba(255,255,255,.08);
    }
    .pf-page .pf-back-label{font-size:12px;font-weight:700;white-space:nowrap}
    .pf-page .pf-back[data-back-to-overview="true"] .pf-back-label{display:inline}
    .pf-page .pf{position:relative;display:flex;flex-direction:column;gap:14px}
    .pf-page .pf-nav{
        order:0;
        display:flex;
        flex-direction:column;
        overflow:visible;
        padding:18px 16px 12px;
        border-radius:22px;
        gap:2px;
        box-shadow:0 8px 24px rgba(15,42,36,.08);
    }
    .pf-nav-title{
        margin:0 0 4px;
        padding:0 6px;
        color:var(--st-ink-2);
        font-size:16px;
        font-weight:800;
    }
    .pf-nav-title.pf-nav-spaced{margin-top:18px}
    .pf-page .pf-nav button,
    .pf-page .pf-nav a{
        position:relative;
        min-height:54px;
        padding:0 8px;
        border-radius:10px;
        font-size:15px;
    }
    .pf-page .pf-nav button>i:first-child,
    .pf-page .pf-nav a>i:first-child{
        width:48px;
        color:var(--st-ink-2);
        font-size:20px;
        text-align:center;
    }
    .pf-page .pf-nav [data-tab]::after,
    .pf-page .pf-nav a:not(.out)::after{
        content:"";
        width:9px;
        height:9px;
        margin-left:auto;
        border-top:2px solid currentColor;
        border-right:2px solid currentColor;
        transform:rotate(45deg);
        opacity:.65;
    }
    .pf-page .pf-nav button.active{background:var(--st-cream)}
    .pf-page .pf-nav .out{color:#A3321A}
    .pf-page .pf>.panel{
        order:1;
        padding:22px 18px;
        border-radius:22px;
        box-shadow:0 8px 24px rgba(15,42,36,.06);
    }
    .pf-page .pf>.panel h2{font-size:20px!important}
    .pf-page .msg{margin:12px 0;text-align:center}
}
</style>
<style>
.pf-page .pf{display:block}
.pf-page .pf-top{position:relative}
.pf-settings{position:absolute;top:0;right:0;z-index:5}
.pf-settings summary{list-style:none;display:grid;place-items:center;width:44px;height:44px;border:1px solid var(--st-sand);border-radius:50%;background:#fff;color:var(--st-ink);font-size:20px;cursor:pointer}
.pf-settings summary::-webkit-details-marker{display:none}
.pf-settings-menu{position:absolute;top:52px;right:0;width:min(310px,calc(100vw - 36px));max-height:70vh;overflow:auto;padding:14px;border:1px solid var(--st-sand);border-radius:16px;background:#fff;box-shadow:0 16px 40px rgba(15,42,36,.18);text-align:left}
.pf-settings-menu strong{display:block;padding:8px 10px 4px;color:var(--st-ink-2);font-size:11px;text-transform:uppercase;letter-spacing:.08em}
.pf-settings-menu button,.pf-settings-menu a{display:flex;width:100%;align-items:center;gap:10px;padding:10px;border:0;border-radius:9px;background:transparent;color:var(--st-ink);font-family:inherit;font-size:14px;font-weight:600;text-decoration:none;text-align:left}
.pf-settings-menu button:hover,.pf-settings-menu a:hover{background:var(--st-cream)}
.pf-settings-menu .danger-link{color:#A3321A}
.account-overview{display:grid;gap:18px;margin:18px 0 24px}
.purchase-shortcuts{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}
.purchase-shortcut{min-height:100px;display:grid;place-items:center;align-content:center;gap:8px;padding:14px 8px;border:1px solid var(--st-sand);border-radius:14px;background:#fff;color:var(--st-ink);text-align:center;text-decoration:none}
.purchase-shortcut i{font-size:22px;color:var(--st-clay)}
.purchase-shortcut b{font-size:13px}
.purchase-shortcut small{color:var(--st-ink-2)}
.voucher-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
.voucher-card{display:grid;grid-template-columns:auto minmax(0,1fr);align-items:start;gap:12px;padding:14px;border:1px solid var(--st-sand);border-radius:14px;background:#fff}
.voucher-card>i{font-size:22px;color:var(--st-clay)}
.voucher-card b{display:block;font-size:14px}
.voucher-card p{margin:4px 0;color:var(--st-ink-2);font-size:12px;line-height:1.45}
.voucher-code{display:inline-flex;align-items:center;margin-top:4px;padding:4px 8px;border:1px dashed var(--st-clay);border-radius:6px;color:var(--st-ink);font-size:12px;font-weight:800;letter-spacing:.05em}
.voucher-card .voucher-status{color:#1B6B4A;font-size:11px;font-weight:700}
.voucher-card.unavailable{background:var(--st-cream)}
.voucher-card.unavailable .voucher-status{color:var(--st-ink-2)}
.voucher-note{margin:10px 0 0;color:var(--st-ink-2);font-size:12px}
.order-filter{display:flex;gap:8px;flex-wrap:wrap;margin:-8px 0 18px}
.order-filter button{padding:8px 12px;border:1px solid var(--st-sand);border-radius:999px;background:#fff;color:var(--st-ink);font-size:12px;font-weight:700}
.order-filter button.active{border-color:var(--st-clay);background:var(--st-clay);color:#fff}
.order-filter .overview-back{border-color:var(--st-ink);background:var(--st-ink);color:#fff}
.order-filter .overview-back:hover{background:#18403a}
@media(max-width:991.98px){.order-filter .overview-back{display:none}}
.rating-options{display:flex;gap:8px;margin:12px 0 18px}
.rating-options label{cursor:pointer}
.rating-options input{position:absolute;opacity:0}
.rating-options span{display:grid;place-items:center;width:44px;height:44px;border:1px solid var(--st-sand);border-radius:10px;color:var(--st-ink-2);font-size:20px}
.rating-options input:checked+span{border-color:var(--st-clay);background:var(--st-cream);color:#ed941f}
.rating-options input:focus-visible+span{outline:2px solid var(--st-clay);outline-offset:2px}
.settings-note{padding:16px;border-radius:12px;background:var(--st-cream);color:var(--st-ink-2);font-size:14px}
.settings-note.danger{background:#fff3f0;color:#87331f}
.settings-note form{margin-top:14px}
.settings-note textarea{width:100%;margin:6px 0 14px;padding:12px;border:1px solid var(--st-sand);border-radius:10px}
.settings-note .form-check{margin-top:10px}
.orders .order{display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:start;gap:14px 18px;padding:20px;border-radius:18px;box-shadow:0 5px 18px rgba(15,42,36,.045)}
.order-head{grid-column:1/-1;display:flex;align-items:flex-start;justify-content:space-between;gap:16px}
.order-meta b{display:block;font-size:15px;letter-spacing:.01em}
.order-meta small{font-size:12px;line-height:1.5}
.order-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.order-status{display:inline-flex;align-items:center;min-height:30px;padding:5px 11px;border-radius:999px;background:#fff4e6;color:#9a5b00;font-size:12px;font-weight:800;white-space:nowrap}
.order-status[data-status="Cancelled"]{background:#f3eeee;color:#766565}
.order-status[data-status="Delivered"],.order-status[data-status="Completed"]{background:#e9f2ee;color:#1b6b4a}
.order-track{display:inline-flex;min-height:38px;align-items:center;justify-content:center;padding:0 14px;border:1px solid var(--st-sand);border-radius:999px;color:var(--st-ink)!important;font-size:12px;font-weight:800;text-decoration:none}
.order-track:hover{border-color:var(--st-ink);background:var(--st-cream)}
.orders .account-order-items{grid-column:1/-1;margin-top:0;padding-top:14px;border-top:1px solid var(--st-sand)}
.account-order-item{padding:12px 14px;background:#fff}
.account-cancel{grid-column:1/-1;width:100%;margin:0;padding:12px 14px;border:1px solid #f0d8d2;border-radius:14px;background:#fffaf8}
.account-cancel summary{display:flex;align-items:center;gap:9px;min-height:30px;color:#a3321a;list-style:none}
.account-cancel summary::-webkit-details-marker{display:none}
.account-cancel summary span{color:var(--st-ink-2);font-size:12px;font-weight:500}
.account-cancel summary::before{content:"!";display:grid;width:22px;height:22px;place-items:center;border-radius:50%;background:#f8e5df;font-size:12px}
.account-cancel summary::after{content:"+";margin-left:auto;font-size:20px;font-weight:500}
.account-cancel[open] summary::after{content:"−"}
.account-cancel form{max-width:none;padding-top:14px}
.cancel-intro{margin:0 0 12px;color:var(--st-ink-2);font-size:13px}
.cancel-reasons{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;margin:0;padding:0;border:0}
.cancel-reasons legend{margin-bottom:8px;font-size:13px;font-weight:800}
.cancel-reason-option{display:flex;align-items:flex-start;gap:9px;min-height:44px;margin:0;padding:11px 12px;border:1px solid var(--st-sand);border-radius:11px;background:#fff;color:var(--st-ink);font-size:12px;line-height:1.4;cursor:pointer}
.cancel-reason-option:has(input:checked){border-color:var(--st-clay);background:#fff5f1}
.cancel-reason-option input{margin-top:1px;accent-color:var(--st-clay);flex:none}
.cancel-details{display:block;margin-top:12px}
.cancel-details textarea{min-height:88px}
.cancel-note{display:flex;align-items:flex-start;gap:8px;margin:14px 0;color:var(--st-ink-2);font-size:12px;line-height:1.5}
.cancel-note i{color:var(--st-clay);margin-top:1px}
.cancel-confirm{display:inline-flex;min-height:42px;align-items:center;justify-content:center;gap:8px;padding:0 16px;border:0;border-radius:999px;background:#a3321a;color:#fff;font-size:13px;font-weight:800}
.cancel-confirm:hover{background:#852a17}
.cancel-confirm:focus-visible{outline:3px solid var(--st-gold);outline-offset:2px}
.cancelled-reason{grid-column:1/-1;padding:10px 12px;border-radius:10px;background:#f5f4f1;color:#625e5b;font-size:12px}
@media(max-width:767.98px){
    .purchase-shortcuts{grid-template-columns:repeat(2,minmax(0,1fr))}
    .pf-settings{top:16px;right:8px}
    .pf-settings summary{width:44px;height:44px;border-color:rgba(255,255,255,.35);background:rgba(255,255,255,.08);color:#fff}
    .pf-settings-menu{top:48px}
    .voucher-list{grid-template-columns:1fr}
    .orders .order{grid-template-columns:minmax(0,1fr);gap:12px;padding:16px}
    .order-head{grid-column:1;flex-direction:column;gap:10px}
    .order-actions{width:100%;justify-content:space-between}
    .orders .account-order-items,.account-cancel,.cancelled-reason{grid-column:1}
    .account-order-item{grid-template-columns:46px minmax(0,1fr) auto;gap:8px}
    .account-order-thumb{width:46px;height:46px}
    .cancel-reasons{grid-template-columns:1fr}
    .account-cancel{padding:11px}
}
</style>
</head>

<body class="pf-page">

<div class="d-none d-lg-block">
<div class="st-topbar text-center">Free delivery over ₱3,000 • 1-Year Warranty • GCash, Maya, COD</div>
<header class="st-header sticky-top">
<nav class="navbar navbar-expand-lg navbar-dark container-xxl px-lg-5" aria-label="Primary">
<a class="navbar-brand st-logo order-1 me-auto" href="../index.html" aria-label="SmartTech home">
<img class="d-none d-lg-block" src="../images/smarttechappliances.svg" alt="" width="60" height="49">
<span>SmartTech</span>
</a>
<div class="collapse navbar-collapse order-3 order-lg-2 show" id="profile-nav">
<ul class="navbar-nav me-lg-3 gap-lg-3 pt-2 pt-lg-0">
<li class="nav-item"><a class="nav-link" href="../index.html">Home</a></li>
<li class="nav-item"><a class="nav-link" href="../products.php?cat=household">Household</a></li>
<li class="nav-item"><a class="nav-link" href="../products.php?cat=entertainment">Entertainment</a></li>
<li class="nav-item"><a class="nav-link" href="../products.php?cat=kitchen">Kitchen</a></li>
</ul>
<form class="st-search ms-lg-auto flex-grow-1 my-3 my-lg-0" role="search" onsubmit="return false">
<i class="bi bi-search" aria-hidden="true"></i>
<input type="search" class="form-control" placeholder="Search appliances" aria-label="Search products" readonly>
</form>
</div>
<div class="d-flex gap-2 order-2 order-lg-3 ms-lg-4">
<a class="st-icon-btn d-none d-lg-inline-grid" href="../wishlist.html" aria-label="Wishlist"><i class="bi bi-heart"></i></a>
<a class="st-icon-btn st-cart-btn" href="../cart.php" aria-label="Cart"><i class="bi bi-bag"></i></a>
<a class="st-icon-btn d-none d-lg-inline-grid" href="profile.php" aria-label="Account" aria-current="page"><i class="bi bi-person"></i></a>
</div>
</nav>
</header>
<span class="st-icons" hidden></span>
</div>

<main id="main" class="container-xxl px-lg-5 pg">

<div class="pf-top">

<a class="pf-back" href="../index.html">
<i class="bi bi-chevron-left" aria-hidden="true"></i>
<span class="pf-back-label" aria-hidden="true"></span>
<span class="visually-hidden">Back to store</span>
</a>

<div class="pf-avatar-wrap">
<?php if($user["profile_image"]): ?>
<img class="pf-av" src="../<?php echo e($user["profile_image"]); ?>" alt="Profile picture">
<?php else: ?>
<span class="pf-av"><?php echo e(strtoupper(substr($user["full_name"]?:$user["username"],0,1))); ?></span>
<?php endif; ?>
<form class="pf-avatar-form" id="avatarForm" method="POST" enctype="multipart/form-data">
<input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
<input type="hidden" name="action" value="avatar">
<label class="pf-avatar-edit" for="profileImageInput" title="Change profile picture">
<i class="bi bi-camera-fill" aria-hidden="true"></i>
<span class="visually-hidden">Change profile picture</span>
</label>
<input id="profileImageInput" name="profile_image" type="file" accept="image/jpeg,image/png,image/webp">
</form>
</div>

<div>
<h1><?php echo e($user["full_name"]); ?></h1>
<small>@<?php echo e($user["username"]); ?> · <?php echo e($user["email"]); ?></small>
</div>
<details class="pf-settings">
<summary aria-label="Settings" title="Settings"><i class="bi bi-gear"></i></summary>
<div class="pf-settings-menu">
<strong>My account</strong>
<button type="button" data-tab="profile"><i class="bi bi-person"></i>Account</button>
<button type="button" data-tab="password"><i class="bi bi-shield-lock"></i>Security</button>
<button type="button" data-tab="addresses"><i class="bi bi-geo-alt"></i>My addresses</button>
<a href="../wishlist.html"><i class="bi bi-heart"></i>Wishlist</a>
<strong>Support</strong>
<a href="../help.html"><i class="bi bi-question-circle"></i>Help centre</a>
<a href="../policies.html#community"><i class="bi bi-people"></i>Community rules</a>
<a href="../policies.html#policies"><i class="bi bi-file-earmark-text"></i>Policies</a>
<a href="../about.html"><i class="bi bi-info-circle"></i>About us</a>
<button type="button" data-tab="feedback"><i class="bi bi-star"></i>Rate this web app</button>
<button type="button" data-tab="deletion"><i class="bi bi-person-x"></i>Request account deletion</button>
<a class="danger-link" href="logout.php?next=../login.php"><i class="bi bi-box-arrow-right"></i>Log out / switch account</a>
</div>
</details>
</div>

<?php if($message): ?>
<p class="msg <?php echo $error?"err":"ok"; ?>">
<?php echo e($message); ?>
</p>
<?php endif; ?>

<div class="account-overview" id="accountOverview">
<section>
<div class="d-flex justify-content-between align-items-center gap-3 mb-3">
<h2 class="fs-5 fw-bold mb-0">My Purchases</h2>
<button class="address-action" type="button" data-purchase-filter="all">View all</button>
</div>
<div class="purchase-shortcuts">
<button class="purchase-shortcut" type="button" data-purchase-filter="pay"><i class="bi bi-wallet2"></i><b>To Pay</b><small><?php echo $purchaseCounts["pay"]; ?> orders</small></button>
<button class="purchase-shortcut" type="button" data-purchase-filter="ship"><i class="bi bi-box-seam"></i><b>To Ship</b><small><?php echo $purchaseCounts["ship"]; ?> orders</small></button>
<button class="purchase-shortcut" type="button" data-purchase-filter="receive"><i class="bi bi-truck"></i><b>To Receive</b><small><?php echo $purchaseCounts["receive"]; ?> orders</small></button>
<button class="purchase-shortcut" type="button" data-purchase-filter="rate"><i class="bi bi-star"></i><b>To Rate</b><small><?php echo $purchaseCounts["rate"]; ?> items</small></button>
</div>
</section>
<section>
<h2 class="fs-5 fw-bold mb-3">Available Vouchers</h2>
<div class="voucher-list">
<?php foreach($availableVouchers as $code=>$voucher):
    $unavailable=$voucher["first_order"]&&!$firstOrderAvailable;
?>
<article class="voucher-card<?php echo $unavailable?" unavailable":""; ?>">
<i class="bi bi-ticket-perforated" aria-hidden="true"></i>
<div>
<b><?php echo e($voucher["label"]); ?></b>
<p><?php echo e($voucher["terms"]); ?></p>
<span class="voucher-code"><?php echo e($code); ?></span>
<p class="voucher-status"><?php echo $unavailable?"First-order offer — not available after your first order":(!empty($voucher["ends_at"])?"Available · Ends ".e(date("M j, Y",strtotime($voucher["ends_at"]))):"Available · No expiry"); ?></p>
</div>
</article>
<?php endforeach; ?>
</div>
<p class="voucher-note">Enter one code at checkout. Vouchers cannot be combined; no expiry date.</p>
</section>
</div>

<div class="panel">

<section id="profileSec">

<form method="POST">

<input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
<input type="hidden" name="action" value="profile">

<div class="g2">

<label class="field">
Username
<input value="<?php echo e($user["username"]); ?>" readonly>
</label>

<label class="field">
Email
<input name="email" type="email" value="<?php echo e($user["email"]); ?>" readonly required>
</label>

<label class="field full">
Full name
<input name="full_name" value="<?php echo e($user["full_name"]); ?>" readonly required>
</label>

<label class="field">
Contact number
<input name="contact" type="tel" inputmode="tel" autocomplete="tel" value="<?php echo e($user["contact"]); ?>" placeholder="Optional phone number" readonly>
</label>

</div>

<div class="d-flex gap-2">
<button class="btn-clay" type="button" id="editProfileButton">
Edit
</button>
<button class="btn-clay" type="submit" id="saveProfileButton" hidden>
Save Changes
</button>
<button class="address-action" type="button" id="cancelProfileEditButton" hidden>Cancel</button>
</div>

</form>

</section>

<section id="addressesSec" hidden>

<h2 class="fs-4 mb-2">Delivery addresses</h2>
<p class="text-secondary mb-4">Save delivery details for quicker checkout. Your default address will be pre-filled automatically.</p>

<div class="address-list">
<?php if($addresses): ?>
<?php foreach($addresses as $savedAddress): ?>
<article class="address-card">
<div class="address-card-head">
<strong><?php echo e($savedAddress["label"]); ?></strong>
<?php if((int)$savedAddress["is_default"]===1): ?>
<span class="address-badge">Default</span>
<?php endif; ?>
</div>
<p><strong><?php echo e($savedAddress["recipient_name"]); ?></strong> · <?php echo e($savedAddress["phone"]); ?></p>
<p><?php echo e($savedAddress["address_line"]); ?><br>
Barangay <?php echo e($savedAddress["barangay"]); ?>, <?php echo e($savedAddress["city"]); ?>, <?php echo e($savedAddress["province"]); ?> <?php echo e($savedAddress["postal_code"]); ?></p>
<div class="address-actions">
<button class="address-action" type="button" data-edit-address="<?php echo e(json_encode($savedAddress,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)); ?>">Edit</button>
<?php if((int)$savedAddress["is_default"]!==1): ?>
<form method="POST">
<input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
<input type="hidden" name="action" value="default_address">
<input type="hidden" name="address_id" value="<?php echo (int)$savedAddress["id"]; ?>">
<button class="address-action" type="submit">Set as default</button>
</form>
<?php endif; ?>
<form method="POST" class="delete-address-form">
<input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
<input type="hidden" name="action" value="delete_address">
<input type="hidden" name="address_id" value="<?php echo (int)$savedAddress["id"]; ?>">
<button class="address-action danger" type="submit">Remove</button>
</form>
</div>
</article>
<?php endforeach; ?>
<?php else: ?>
<div class="text-center py-4">
<i class="bi bi-geo-alt fs-1 text-secondary"></i>
<p class="mt-2 mb-0">No saved addresses yet. Add one for faster checkout.</p>
</div>
<?php endif; ?>
</div>

<form method="POST" id="addressForm">
<input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
<input type="hidden" name="action" value="address">
<input type="hidden" name="address_id" id="addressId">
<h3 class="fs-5 mb-3" id="addressFormTitle">Add a delivery address</h3>
<div class="g2">
<label class="field">Address label
<select name="label" id="addressLabel" required>
<option value="Home">Home</option>
<option value="Work">Work</option>
<option value="Other">Other</option>
</select>
</label>
<label class="field">Recipient name
<input name="recipient_name" id="recipientName" autocomplete="name" required>
</label>
<label class="field">Phone number
<input name="phone" id="addressPhone" type="tel" inputmode="tel" autocomplete="tel" required>
</label>
<label class="field">House / street / unit
<input name="address_line" id="addressLine" autocomplete="street-address" required>
</label>
<label class="field">Province
<select name="province" id="addressProvince" required>
<option value="">Loading provinces...</option>
</select>
</label>
<label class="field">City / municipality
<select name="city" id="addressCity" required disabled>
<option value="">Select a province first</option>
</select>
</label>
<label class="field">Barangay
<select name="barangay" id="addressBarangay" required disabled>
<option value="">Select a city / municipality first</option>
</select>
</label>
<p class="msg full" id="addressLocationStatus" role="status" aria-live="polite">Loading Philippine locations...</p>
<label class="field">Postal code
<input name="postal_code" id="postalCode" inputmode="numeric" autocomplete="postal-code" required>
</label>
</div>
<label class="d-flex align-items-center gap-2 mb-3">
<input type="checkbox" name="is_default" value="1">
Set as my default delivery address
</label>
<div class="d-flex gap-2 flex-wrap">
<button class="btn-clay" type="submit">Save address</button>
<button class="address-action" type="button" id="cancelAddressEdit" hidden>Cancel edit</button>
</div>
</form>

</section>

<section id="ordersSec" hidden>

<div class="d-flex justify-content-between align-items-center gap-3 mb-4 flex-wrap">
<h2 class="fs-4 mb-0">My Orders</h2>
</div>
<div class="order-filter" role="group" aria-label="Filter orders">
<button type="button" class="overview-back" data-tab="profile"><i class="bi bi-chevron-left" aria-hidden="true"></i> Back to Account Overview</button>
<button type="button" class="active" data-order-filter="all">All orders</button>
<button type="button" data-order-filter="pay">To Pay</button>
<button type="button" data-order-filter="ship">To Ship</button>
<button type="button" data-order-filter="receive">To Receive</button>
<button type="button" data-order-filter="rate">To Rate</button>
</div>

<?php if($orders): ?>

<div class="orders">

<?php foreach($orders as $order): ?>

<?php
$hasUnratedItem=false;
foreach($orderItems[(int)$order["id"]]??[] as $item){
    if(!$item["review_id"]&&!empty($item["product_code"]))$hasUnratedItem=true;
}
$purchaseCategory=$order["status"]==="Pending"?"pay":($order["status"]==="Processing"?"ship":(in_array($order["status"],["Shipped","Delivered"],true)?"receive":($order["status"]==="Completed"&&$hasUnratedItem?"rate":"none")));
?>
<article class="order" data-order-status="<?php echo e($order["status"]); ?>" data-order-category="<?php echo e($purchaseCategory); ?>">

<header class="order-head">
<div class="order-meta">
<b><?php echo e($order["order_number"]); ?></b>
<small><?php echo e($order["created_at"]); ?> · <?php echo peso($order["grand_total"]); ?><?php if((float)$order["discount_amount"]>0): ?> · <?php echo e($order["voucher_code"]); ?> saved <?php echo peso($order["discount_amount"]); ?><?php endif; ?></small>
</div>

<div class="order-actions">
<span class="order-status" data-status="<?php echo e($order["status"]); ?>">
<?php echo $order["status"]==="Pending"?"Waiting for seller confirmation":($order["status"]==="Processing"?"Order confirmed · Processing":e($order["status"])); ?>
</span>
<a class="order-track" href="../track.php?order=<?php echo urlencode($order["order_number"]); ?>">Track order <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
</div>
</header>

<?php if($order["status"]==="Cancelled"&&!empty($order["cancellation_reason"])): ?>
<div class="cancelled-reason"><b>Cancellation reason:</b> <?php echo e($order["cancellation_reason"]); ?><?php if(!empty($order["cancellation_details"])): ?> — <?php echo e($order["cancellation_details"]); ?><?php endif; ?></div>
<?php endif; ?>

<?php if(in_array($order["status"],["Pending","Processing"],true)): ?>
<details class="account-cancel">
<summary><b>Need to cancel?</b><span><?php echo $order["status"]==="Pending"?"This order is still waiting for seller confirmation.":"This confirmed order can still be cancelled before it ships."; ?></span></summary>
<form method="POST" action="cancel_order.php">
<input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
<input type="hidden" name="order_id" value="<?php echo (int)$order["id"]; ?>">
<p class="cancel-intro">Why do you want to cancel this order?</p>
<fieldset class="cancel-reasons">
<legend class="visually-hidden">Choose a cancellation reason</legend>
<?php foreach(["Changed my mind","Ordered by mistake","Found a better price","Want to change my items or address","Delivery is taking too long","Payment issue","No longer need the item","Other"] as $reason): ?>
<label class="cancel-reason-option">
<input type="radio" name="reason" value="<?php echo e($reason); ?>" class="cancel-reason" required>
<span><?php echo e($reason); ?></span>
</label>
<?php endforeach; ?>
</fieldset>
<label class="field cancel-details" hidden>Tell us more
<textarea name="details" maxlength="500" placeholder="Please share the reason for cancelling."></textarea>
</label>
<p class="cancel-note"><i class="bi bi-info-circle" aria-hidden="true"></i><span>Cancelling takes effect immediately and cannot be undone. Orders can no longer be cancelled once shipped.</span></p>
<button class="cancel-confirm" type="submit"><i class="bi bi-x-circle" aria-hidden="true"></i>Confirm cancellation</button>
</form>
</details>
<?php endif; ?>

<?php if(!empty($orderItems[(int)$order["id"]])): ?>
<div class="account-order-items">
<?php foreach($orderItems[(int)$order["id"]] as $orderItem): ?>
<div class="account-order-item">
<div class="account-order-thumb">
<?php if(!empty($orderItem["product_image"])):
    $productImage=(string)$orderItem["product_image"];
    $productImage=(strncmp($productImage,"images/",7)===0||strncmp($productImage,"uploads/",8)===0)
        ? "../".$productImage
        : "../images/".$productImage;
?>
<img src="<?php echo e($productImage); ?>" alt="" loading="lazy" onerror="this.hidden=true">
<?php else: ?>
<i class="bi bi-box-seam" aria-hidden="true"></i>
<?php endif; ?>
</div>
<div class="account-order-product">
<b class="account-order-product-name"><?php echo e($orderItem["product_name"]); ?> × <?php echo (int)$orderItem["quantity"]; ?></b>
<div class="account-order-item-actions">
<?php if($order["status"]==="Completed"&&!$orderItem["review_id"]&&!empty($orderItem["product_code"])): ?>
<a href="../product.php?id=<?php echo urlencode($orderItem["product_code"]); ?>#reviews">Rate this product</a>
<?php elseif($orderItem["review_id"]): ?>
<span class="small text-success">Reviewed</span>
<?php endif; ?>
<?php if(!empty($orderItem["refund_status"])): ?>
<span class="small">Return/refund request: <?php echo e($orderItem["refund_status"]); ?><?php echo $orderItem["refund_status"]==="Approved"?" — payment refund still needs manual processing.":""; ?></span>
<?php if(!empty($orderItem["refund_admin_note"])): ?>
<span class="small refund-admin-note"><b>Message from the seller:</b> <?php echo nl2br(e($orderItem["refund_admin_note"])); ?></span>
<?php endif; ?>
<?php endif; ?>
</div>
</div>
<span><?php echo peso((float)$orderItem["price"]*(int)$orderItem["quantity"]); ?></span>
<?php if(in_array($order["status"],["Delivered","Completed"],true)&&!empty($order["refund_window_open"])&&$orderItem["refund_status"]!=="Pending"): ?>
<details class="account-refund">
<summary>Request return / refund</summary>
<form method="POST" action="submit_refund_request.php">
<input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
<input type="hidden" name="order_id" value="<?php echo (int)$order["id"]; ?>">
<input type="hidden" name="order_item_id" value="<?php echo (int)$orderItem["order_item_id"]; ?>">
<label class="field">Reason
<select name="reason" required>
<option value="">Choose a reason</option>
<option>Damaged or defective</option>
<option>Wrong item</option>
<option>Missing parts</option>
<option>Other</option>
</select>
</label>
<label class="field">Tell us what happened
<textarea name="details" minlength="10" maxlength="1000" required></textarea>
</label>
<p class="small text-secondary">Requests must be sent within 7 days of delivery. An admin will review it; this does not automatically issue a refund.</p>
<button class="address-action" type="submit">Submit request</button>
</form>
</details>
<?php endif; ?>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php if($order["status"]==="Delivered"): ?>
<form method="POST" action="complete_order.php" onsubmit="return confirm('Confirm that you received this order and complete it?');">
<input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
<input type="hidden" name="order_id" value="<?php echo (int)$order["id"]; ?>">
<button class="btn-clay" type="submit">Complete order</button>
</form>
<?php endif; ?>

</article>

<?php endforeach; ?>

</div>

<?php else: ?>

<div class="text-center py-5">

<i class="bi bi-receipt fs-1"></i>

<p class="mt-3">
You have no orders yet.
</p>

<a class="btn-clay d-inline-grid align-items-center text-decoration-none px-4" href="../products.php">
Go Shopping
</a>

</div>

<?php endif; ?>

</section>

<section id="passwordSec" hidden>

<h2 class="fs-4 mb-4">Change Password</h2>

<form method="POST" style="max-width:440px">

<input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
<input type="hidden" name="action" value="password">

<label class="field">
Current password
<input name="current_password" type="password" required>
</label>

<label class="field">
New password
<input name="new_password" type="password" minlength="8" required>
</label>

<label class="field">
Confirm new password
<input name="confirm_password" type="password" minlength="8" required>
</label>

<button class="btn-clay" type="submit">
Update Password
</button>

</form>

</section>

<section id="feedbackSec" hidden>
<h2 class="fs-4 mb-2">Rate this web app</h2>
<p class="text-secondary">Tell us how your SmartTech shopping experience is going.</p>
<form method="POST" action="profile.php?tab=feedback" style="max-width:520px">
<input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
<input type="hidden" name="action" value="app_feedback">
<span class="form-label d-block">Your rating</span>
<div class="rating-options" role="radiogroup" aria-label="Rate the web app">
<?php for($rating=1;$rating<=5;$rating++): ?>
<label><input type="radio" name="rating" value="<?php echo $rating; ?>" required <?php echo (int)$appFeedback["rating"]===$rating?"checked":""; ?>><span aria-label="<?php echo $rating; ?> stars">★</span></label>
<?php endfor; ?>
</div>
<label class="field">Feedback (optional)
<textarea name="comment" rows="4" maxlength="1000" placeholder="What can we improve?"><?php echo e($appFeedback["comment"]); ?></textarea>
</label>
<button class="btn-clay" type="submit"><?php echo $appFeedback["rating"]?"Update rating":"Submit rating"; ?></button>
</form>
</section>

<section id="deletionSec" hidden>
<h2 class="fs-4 mb-2">Request account deletion</h2>
<div class="settings-note danger">
Submitting a request does not delete your account immediately. The team will review it, and you can continue to use your account while it is pending.
</div>
<?php if($deletionRequest&&$deletionRequest["status"]==="Pending"): ?>
<p class="mt-3">Your request is pending review (submitted <?php echo e(date("M j, Y",strtotime($deletionRequest["requested_at"]))); ?>).</p>
<?php elseif($deletionRequest): ?>
<p class="mt-3">Your previous request was <?php echo e(strtolower($deletionRequest["status"])); ?>. You may submit a new request if needed.</p>
<?php endif; ?>
<?php if(!$deletionRequest||$deletionRequest["status"]!=="Pending"): ?>
<form method="POST" action="profile.php?tab=deletion" onsubmit="return confirm('Send an account deletion request for review? Your account will not be deleted immediately.');">
<input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
<input type="hidden" name="action" value="delete_account_request">
<label class="form-check"><input class="form-check-input" type="checkbox" name="confirm_deletion_request" value="1" required><span class="form-check-label">I understand this submits a request for review and does not delete my account immediately.</span></label>
<button class="btn-clay mt-3" type="submit">Submit deletion request</button>
</form>
<?php endif; ?>
</section>

</div>

</div>

</main>

<nav class="st-bottomnav d-lg-none" aria-label="Main navigation">
<a href="../index.html">
<i class="bi bi-house" aria-hidden="true"></i><span>Home</span>
</a>
<a href="../products.php">
<i class="bi bi-shop" aria-hidden="true"></i><span>Shop</span>
</a>
<a href="../wishlist.html">
<i class="bi bi-heart" aria-hidden="true"></i><span>Wishlist</span>
</a>
<a href="../cart.php">
<i class="bi bi-bag" aria-hidden="true"></i><span>Cart</span>
</a>
<a class="active" href="profile.php" aria-current="page">
<i class="bi bi-person" aria-hidden="true"></i><span>Account</span>
</a>
</nav>

<script src="../address-selector.js?v=ph-address-dropdowns-2"></script>
<script>
document.getElementById('profileImageInput').addEventListener('change',function(){
    if(this.files.length){
        document.getElementById('avatarForm').submit();
    }
});

const tabs={
    profile:document.getElementById('profileSec'),
    orders:document.getElementById('ordersSec'),
    addresses:document.getElementById('addressesSec'),
    password:document.getElementById('passwordSec'),
    feedback:document.getElementById('feedbackSec'),
    deletion:document.getElementById('deletionSec')
};

function showTab(name){
    if(!Object.prototype.hasOwnProperty.call(tabs,name))return;
    Object.entries(tabs).forEach(([key,section])=>section.hidden=key!==name);
    document.getElementById('accountOverview').hidden=name!=='profile';
    document.querySelector('.pf-settings').open=false;
    const backLink=document.querySelector('.pf-back');
    const backToOverview=name!=='profile';
    backLink.href=backToOverview?'#profile':'../index.html';
    backLink.setAttribute('aria-label',backToOverview?'Back to account overview':'Back to store');
    backLink.querySelector('.pf-back-label').textContent=backToOverview?'Account overview':'Back to store';
    backLink.querySelector('.visually-hidden').textContent=backToOverview?'Back to account overview':'Back to store';
    backLink.dataset.backToOverview=String(backToOverview);
    document.querySelectorAll('[data-tab]').forEach(button=>{
        const active=button.dataset.tab===name;
        button.classList.toggle('active',active);
        if(active)button.setAttribute('aria-current','page');
        else button.removeAttribute('aria-current');
    });
}

function applyOrderFilter(filter){
    document.querySelectorAll('.order[data-order-category]').forEach(order=>{
        order.hidden=filter!=='all'&&order.dataset.orderCategory!==filter;
    });
    document.querySelectorAll('[data-order-filter]').forEach(button=>{
        const active=button.dataset.orderFilter===filter;
        button.classList.toggle('active',active);
        if(active)button.setAttribute('aria-pressed','true');
        else button.removeAttribute('aria-pressed');
    });
    let empty=document.getElementById('orderFilterEmpty');
    const visible=[...document.querySelectorAll('.order[data-order-category]')].some(order=>!order.hidden);
    if(!visible){
        if(!empty){
            empty=document.createElement('p');
            empty.id='orderFilterEmpty';
            empty.className='settings-note';
            document.querySelector('#ordersSec .orders')?.after(empty);
        }
        if(empty)empty.textContent='No orders in this category yet.';
    }else if(empty){
        empty.remove();
    }
}

document.addEventListener('click',event=>{
    const backLink=event.target.closest('.pf-back[data-back-to-overview="true"]');
    if(backLink){
        event.preventDefault();
        showTab('profile');
    }
    const tabButton=event.target.closest('[data-tab]');
    if(tabButton){
        event.preventDefault();
        showTab(tabButton.dataset.tab);
    }
    const purchaseButton=event.target.closest('[data-purchase-filter]');
    if(purchaseButton){
        showTab('orders');
        applyOrderFilter(purchaseButton.dataset.purchaseFilter);
    }
    const orderFilter=event.target.closest('[data-order-filter]');
    if(orderFilter)applyOrderFilter(orderFilter.dataset.orderFilter);
});
showTab(new URLSearchParams(location.search).get('tab')||'profile');

const profileForm=document.querySelector('#profileSec form');
const profileFields=profileForm.querySelectorAll('input[name="email"],input[name="full_name"],input[name="contact"]');
const editProfileButton=document.getElementById('editProfileButton');
const saveProfileButton=document.getElementById('saveProfileButton');
const cancelProfileEditButton=document.getElementById('cancelProfileEditButton');

function setProfileEditing(editing){
    profileFields.forEach(field=>field.readOnly=!editing);
    editProfileButton.hidden=editing;
    saveProfileButton.hidden=!editing;
    cancelProfileEditButton.hidden=!editing;
}

editProfileButton.addEventListener('click',()=>setProfileEditing(true));
cancelProfileEditButton.addEventListener('click',()=>{
    profileForm.reset();
    setProfileEditing(false);
});

document.querySelectorAll('.delete-address-form').forEach(form=>{
    form.addEventListener('submit',event=>{
        if(!confirm('Remove this saved delivery address?'))event.preventDefault();
    });
});

document.querySelectorAll('.account-cancel').forEach(details=>{
    const reasons=details.querySelectorAll('.cancel-reason');
    const more=details.querySelector('.cancel-details');
    const textarea=more.querySelector('textarea');
    const update=()=>{
        const isOther=details.querySelector('.cancel-reason:checked')?.value==='Other';
        more.hidden=!isOther;
        textarea.required=isOther;
        if(!isOther)textarea.value='';
    };
    reasons.forEach(reason=>reason.addEventListener('change',update));
    update();
});

document.querySelectorAll('[data-edit-address]').forEach(button=>{
    button.addEventListener('click',async()=>{
        const address=JSON.parse(button.dataset.editAddress);
        document.getElementById('addressId').value=address.id;
        document.getElementById('addressLabel').value=address.label;
        document.getElementById('recipientName').value=address.recipient_name;
        document.getElementById('addressPhone').value=address.phone;
        document.getElementById('addressLine').value=address.address_line;
        await window.setDeliveryAddressLocation(address);
        document.getElementById('postalCode').value=address.postal_code;
        document.querySelector('#addressForm [name="is_default"]').checked=Number(address.is_default)===1;
        document.getElementById('addressFormTitle').textContent='Edit delivery address';
        document.querySelector('#addressForm button[type="submit"]').textContent='Update address';
        document.getElementById('cancelAddressEdit').hidden=false;
        document.getElementById('addressForm').scrollIntoView({behavior:'smooth',block:'start'});
    });
});

document.getElementById('cancelAddressEdit').addEventListener('click',()=>{
    const form=document.getElementById('addressForm');
    form.reset();
    window.clearDeliveryAddressLocation();
    document.getElementById('addressId').value='';
    document.getElementById('addressFormTitle').textContent='Add a delivery address';
    form.querySelector('button[type="submit"]').textContent='Save address';
    document.getElementById('cancelAddressEdit').hidden=true;
});
</script>
<script src="../search.js?v=search-inline-4"></script>
<script src="../back-to-top.js"></script>

</body>
</html>