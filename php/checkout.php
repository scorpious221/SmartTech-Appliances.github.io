<?php
session_start();
require_once "database.php";
require_once "vouchers.php";

if($_SERVER["REQUEST_METHOD"]!=="POST"){
    header("Location: ../cart.php");
    exit;
}

if(!isset($_SESSION["user_id"])||($_SESSION["role"]??"")!=="customer"){
    header("Location: ../login.php?next=cart.php");
    exit;
}

$userId=(int)$_SESSION["user_id"];
$fullName=trim($_POST["full_name"]??"");
$phone=trim($_POST["phone"]??"");
$address=trim($_POST["address"]??"");
$paymentMethod=trim($_POST["payment_method"]??"");
$voucherCode=strtoupper(trim($_POST["voucher_code"]??""));
$rawBundleId=trim((string)($_POST["bundle_id"]??""));
$bundleId=$rawBundleId===""?0:filter_var($rawBundleId,FILTER_VALIDATE_INT);
$items=json_decode($_POST["items"]??"[]",true);

function failCheckout($message){
    $_SESSION["checkout_error"]=$message;
    header("Location: ../cart.php");
    exit;
}

if(!$fullName){
    failCheckout("Enter your full name.");
}

if(strlen(preg_replace('/\D/','',$phone))<10){
    failCheckout("Enter a valid phone number.");
}

if(!$address){
    failCheckout("Enter your delivery address.");
}

$allowedPayments=["COD","GCash","Maya","Card"];

if(!in_array($paymentMethod,$allowedPayments,true)){
    failCheckout("Invalid payment method.");
}

if($bundleId===false||$bundleId<0){
    failCheckout("Invalid bundle selection.");
}

if($bundleId>0&&$voucherCode!==""){
    failCheckout("Voucher codes cannot be combined with a bundle purchase.");
}

if(!is_array($items)||!count($items)){
    failCheckout("Your cart is empty.");
}

$cleanItems=[];

foreach($items as $item){
    $code=trim($item["id"]??"");
    $quantity=(int)($item["quantity"]??0);

    if(!$code||$quantity<=0){
        failCheckout("Invalid cart item.");
    }

    if(isset($cleanItems[$code])){
        $cleanItems[$code]+=$quantity;
    }else{
        $cleanItems[$code]=$quantity;
    }
}

try{
    $conn->beginTransaction();

    $userStmt=$conn->prepare("SELECT id FROM users WHERE id=? AND role='customer' FOR UPDATE");
    $userStmt->execute([$userId]);
    if(!$userStmt->fetchColumn()){
        throw new Exception("Your account could not be verified. Please sign in again.");
    }
    $bundle=null;
    $bundleCodes=[];
    if($bundleId>0){
        $bundleStmt=$conn->prepare("SELECT title,product_codes,price FROM home_bundles WHERE id=? AND active=1 FOR UPDATE");
        $bundleStmt->execute([$bundleId]);
        $bundle=$bundleStmt->fetch(PDO::FETCH_ASSOC);
        if(!$bundle){
            throw new Exception("This bundle is no longer available. Please refresh the shop and try again.");
        }
        $decodedCodes=json_decode((string)$bundle["product_codes"],true);
        if(!is_array($decodedCodes)||!$decodedCodes){
            throw new Exception("This bundle does not have a valid product list.");
        }
        $bundleCodes=array_values(array_unique(array_map("strval",$decodedCodes)));
        $submittedCodes=array_keys($cleanItems);
        sort($bundleCodes);
        sort($submittedCodes);
        if($bundleCodes!==$submittedCodes){
            throw new Exception("A bundle purchase must include every product in the bundle and no unrelated items.");
        }
    }
    $orderCountStmt=$conn->prepare("SELECT COUNT(*) FROM orders WHERE user_id=?");
    $orderCountStmt->execute([$userId]);
    $firstOrder=(int)$orderCountStmt->fetchColumn()===0;

    $productStmt=$conn->prepare("
        SELECT id,product_code,name,COALESCE(sale_price,price) AS price,image,category,stock
        FROM products
        WHERE product_code=?
        FOR UPDATE
    ");

    $orderItems=[];
    $subtotal=0;

    foreach($cleanItems as $code=>$quantity){
        $productStmt->execute([$code]);
        $product=$productStmt->fetch(PDO::FETCH_ASSOC);

        if(!$product){
            throw new Exception("A product in your cart is no longer available.");
        }

        $stock=(int)$product["stock"];

        if($stock<=0){
            throw new Exception($product["name"]." is out of stock.");
        }

        if($quantity>$stock){
            throw new Exception("Only ".$stock." stock available for ".$product["name"].".");
        }

        $price=(float)$product["price"];
        $subtotal+=$price*$quantity;

        $orderItems[]=[
            "product_id"=>(int)$product["id"],
            "product_code"=>$product["product_code"],
            "product_name"=>$product["name"],
            "price"=>$price,
            "category"=>$product["category"],
            "quantity"=>$quantity,
            "image"=>$product["image"]
        ];
    }

    $bundleDiscount=0.0;
    if($bundle){
        $bundleQuantity=min(array_map(static function(string $code)use($cleanItems):int{
            return(int)$cleanItems[$code];
        },$bundleCodes));
        if($bundleQuantity<1){
            throw new Exception("Add at least one of every product in the bundle.");
        }
        $regularSubtotal=$subtotal;
        $regularUnitCents=array_map(static function(array $item):int{
            return(int)round((float)$item["price"]*100);
        },$orderItems);
        $regularUnitTotalCents=array_sum($regularUnitCents);
        if($regularUnitTotalCents<=0){
            throw new Exception("The products in this bundle do not have valid prices.");
        }
        $bundleUnitCents=min((int)round((float)$bundle["price"]*100),$regularUnitTotalCents);
        $allocatedCents=0;
        $pricedBundleItems=[];
        foreach($orderItems as $index=>$item){
            $componentCents=$index===count($orderItems)-1
                ?$bundleUnitCents-$allocatedCents
                :(int)floor($bundleUnitCents*$regularUnitCents[$index]/$regularUnitTotalCents);
            $allocatedCents+=$componentCents;
            $includedQuantity=min((int)$item["quantity"],$bundleQuantity);
            $pricedBundleItems[]=[
                "product_id"=>$item["product_id"],
                "product_code"=>$item["product_code"],
                "product_name"=>$item["product_name"],
                "price"=>$componentCents/100,
                "category"=>$item["category"],
                "quantity"=>$includedQuantity,
                "image"=>$item["image"]
            ];
            $extraQuantity=(int)$item["quantity"]-$includedQuantity;
            if($extraQuantity>0){
                $item["quantity"]=$extraQuantity;
                $pricedBundleItems[]=$item;
            }
        }
        $orderItems=$pricedBundleItems;
        $subtotal=0.0;
        foreach($orderItems as $item){
            $subtotal+=(float)$item["price"]*(int)$item["quantity"];
        }
        $bundleDiscount=round(max(0,$regularSubtotal-$subtotal),2);
    }

    $voucherDiscount=0.0;
    if($voucherCode!==""){
        $voucherResult=calculateVoucherDiscount($conn,$voucherCode,$subtotal,$orderItems,$firstOrder,$userId,true);
        if(!$voucherResult["valid"]){
            throw new Exception($voucherResult["message"]);
        }
        $voucherDiscount=(float)$voucherResult["discount"];
    }else{
        $voucherCode=null;
    }

    $shippingFee=$subtotal>=3000?0:150;
    $grandTotal=max(0,$subtotal-$voucherDiscount)+$shippingFee;

    do{
        $orderNumber="ORD-".date("Ymd")."-".strtoupper(bin2hex(random_bytes(3)));

        $check=$conn->prepare("SELECT id FROM orders WHERE order_number=?");
        $check->execute([$orderNumber]);

        $exists=$check->fetch();
    }while($exists);

    $stmt=$conn->prepare("
        INSERT INTO orders(
            order_number,
            user_id,
            full_name,
            phone,
            address,
            payment_method,
            subtotal,
            shipping_fee,
            voucher_code,
            discount_amount,
            grand_total,
            status
        )
        VALUES(?,?,?,?,?,?,?,?,?,?,?,'Pending')
    ");

    $stmt->execute([
        $orderNumber,
        $userId,
        $fullName,
        $phone,
        $address,
        $paymentMethod,
        $subtotal,
        $shippingFee,
        $voucherCode,
        $voucherDiscount,
        $grandTotal
    ]);

    $orderId=(int)$conn->lastInsertId();

    $itemStmt=$conn->prepare("
        INSERT INTO order_items(
            order_id,
            product_id,
            product_name,
            price,
            quantity
        )
        VALUES(?,?,?,?,?)
    ");

    $stockStmt=$conn->prepare("
        UPDATE products
        SET stock=stock-?
        WHERE id=?
    ");

    foreach($orderItems as $item){
        $itemStmt->execute([
            $orderId,
            $item["product_id"],
            $item["product_name"],
            $item["price"],
            $item["quantity"]
        ]);

        $stockStmt->execute([
            $item["quantity"],
            $item["product_id"]
        ]);
    }

    $conn->commit();

    $_SESSION["last_order"]=[
        "id"=>$orderId,
        "orderNumber"=>$orderNumber,
        "fullName"=>$fullName,
        "phone"=>$phone,
        "address"=>$address,
        "paymentMethod"=>$paymentMethod,
        "subtotal"=>$subtotal,
        "shippingFee"=>$shippingFee,
        "voucherCode"=>$voucherCode,
        "discountAmount"=>$voucherDiscount,
        "bundleTitle"=>$bundle?(string)$bundle["title"]:"",
        "bundleDiscount"=>$bundleDiscount,
        "grandTotal"=>$grandTotal,
        "status"=>"Pending",
        "createdAt"=>date("Y-m-d H:i:s"),
        "items"=>$orderItems
    ];

    header("Location: ../receipt.php");
    exit;

}catch(Throwable $e){
    if($conn->inTransaction()){
        $conn->rollBack();
    }

    $_SESSION["checkout_error"]=$e->getMessage();
    header("Location: ../cart.php");
    exit;
}
?>