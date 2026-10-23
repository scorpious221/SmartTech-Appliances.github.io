<?php
session_start();
header("Content-Type: application/json; charset=utf-8");

function voucherResponse(int $status,array $body):void{
    http_response_code($status);
    echo json_encode($body,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    exit;
}

if($_SERVER["REQUEST_METHOD"]!=="POST"){
    voucherResponse(405,["valid"=>false,"message"=>"Use POST to check a voucher."]);
}

if(!isset($_SESSION["user_id"])||($_SESSION["role"]??"")!=="customer"){
    voucherResponse(401,["valid"=>false,"message"=>"Sign in to use a voucher."]);
}

$payload=json_decode(file_get_contents("php://input"),true);
$action=is_array($payload)?(string)($payload["action"]??"apply"):"apply";
$code=is_array($payload)?trim((string)($payload["code"]??"")):"";
$requestedItems=is_array($payload)?($payload["items"]??null):null;

if(!in_array($action,["apply","list"],true)){
    voucherResponse(400,["valid"=>false,"message"=>"Unsupported voucher action."]);
}
if(($action==="apply"&&($code===""||strlen($code)>40))||!is_array($requestedItems)||!count($requestedItems)||count($requestedItems)>100){
    voucherResponse(400,["valid"=>false,"message"=>"Enter a voucher code and select cart items."]);
}

require_once "database.php";
require_once "vouchers.php";

try{
    $cleanItems=[];
    foreach($requestedItems as $item){
        if(!is_array($item)){
            voucherResponse(400,["valid"=>false,"message"=>"The selected cart items are invalid."]);
        }
        $productCode=trim((string)($item["id"]??""));
        $quantity=filter_var($item["quantity"]??null,FILTER_VALIDATE_INT);
        if($productCode===""||strlen($productCode)>100||$quantity===false||$quantity<1){
            voucherResponse(400,["valid"=>false,"message"=>"The selected cart items are invalid."]);
        }
        $cleanItems[$productCode]=($cleanItems[$productCode]??0)+$quantity;
    }

    $stmt=$conn->prepare("SELECT id,product_code,name,COALESCE(sale_price,price) AS price,category,stock FROM products WHERE product_code=?");
    $items=[];
    $subtotal=0.0;
    foreach($cleanItems as $productCode=>$quantity){
        $stmt->execute([$productCode]);
        $product=$stmt->fetch(PDO::FETCH_ASSOC);
        if(!$product||(int)$product["stock"]<$quantity){
            voucherResponse(400,["valid"=>false,"message"=>"A selected product is unavailable or has insufficient stock."]);
        }
        $price=(float)$product["price"];
        $subtotal+=$price*$quantity;
        $items[]=["product_code"=>$product["product_code"],"price"=>$price,"category"=>$product["category"],"quantity"=>$quantity];
    }

    $stmt=$conn->prepare("SELECT COUNT(*) FROM orders WHERE user_id=?");
    $stmt->execute([(int)$_SESSION["user_id"]]);
    $firstOrder=(int)$stmt->fetchColumn()===0;
    if($action==="list"){
        $offers=voucherOffers($conn);
        $voucherList=[];
        $bundlePurchase=!empty($payload["bundle_purchase"]);
        foreach($offers as $offerCode=>$offer){
            $result=$bundlePurchase
                ?["valid"=>false,"message"=>"Vouchers cannot be combined with bundle purchases."]
                :calculateVoucherDiscount($conn,$offerCode,$subtotal,$items,$firstOrder,(int)$_SESSION["user_id"]);
            $voucherList[]=[
                "code"=>$offerCode,
                "label"=>$offer["label"],
                "terms"=>$offer["terms"],
                "type"=>$offer["type"],
                "value"=>$offer["value"],
                "cap"=>$offer["cap"],
                "minimum"=>$offer["minimum"],
                "ends_at"=>$offer["ends_at"],
                "valid"=>(bool)$result["valid"],
                "discount"=>(float)($result["discount"]??0),
                "message"=>(string)($result["message"]??"")
            ];
        }
        voucherResponse(200,["vouchers"=>$voucherList]);
    }

    $result=calculateVoucherDiscount($conn,$code,$subtotal,$items,$firstOrder,(int)$_SESSION["user_id"]);

    if(!$result["valid"]){
        voucherResponse(400,$result);
    }
    voucherResponse(200,$result+["subtotal"=>round($subtotal,2)]);
}catch(PDOException $e){
    error_log("Voucher validation failed: ".$e->getMessage());
    voucherResponse(500,["valid"=>false,"message"=>"Unable to check the voucher right now. Please try again."]);
}
?>
