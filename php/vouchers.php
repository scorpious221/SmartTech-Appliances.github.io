<?php
function voucherOffers(PDO $conn):array{
    $stmt=$conn->query("
        SELECT * FROM vouchers
        WHERE active=1
          AND (starts_at IS NULL OR starts_at<=NOW())
          AND (ends_at IS NULL OR ends_at>=NOW())
        ORDER BY id DESC
    ");
    $offers=[];
    foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row){
        $code=(string)$row["code"];
        $sets=json_decode((string)($row["required_product_sets"]??"[]"),true);
        $offers[$code]=[
            "label"=>$row["label"],
            "terms"=>$row["description"],
            "minimum"=>(float)$row["minimum_spend"],
            "first_order"=>(bool)$row["first_order_only"],
            "type"=>$row["discount_type"],
            "value"=>(float)$row["discount_value"],
            "cap"=>$row["discount_cap"]===null?null:(float)$row["discount_cap"],
            "eligible_products"=>json_decode((string)($row["eligible_products"]??"[]"),true)?:[],
            "eligible_categories"=>json_decode((string)($row["eligible_categories"]??"[]"),true)?:[],
            "sets"=>is_array($sets)?$sets:[],
            "starts_at"=>$row["starts_at"],
            "ends_at"=>$row["ends_at"]
        ];
    }
    return$offers;
}

function calculateVoucherDiscount(
    PDO $conn,
    string $code,
    float $subtotal,
    array $items,
    bool $firstOrder,
    int $userId,
    bool $lock=false
):array{
    $code=strtoupper(trim($code));
    $stmt=$conn->prepare("
        SELECT * FROM vouchers
        WHERE code=? AND active=1
          AND (starts_at IS NULL OR starts_at<=NOW())
          AND (ends_at IS NULL OR ends_at>=NOW())
        LIMIT 1".($lock?" FOR UPDATE":"")
    );
    $stmt->execute([$code]);
    $offer=$stmt->fetch(PDO::FETCH_ASSOC);
    if(!$offer)return["valid"=>false,"message"=>"That voucher code is not available."];

    if((bool)$offer["first_order_only"]&&!$firstOrder){
        return["valid"=>false,"message"=>"This voucher is only valid on your first order."];
    }
    if($subtotal<(float)$offer["minimum_spend"]){
        return["valid"=>false,"message"=>"This voucher requires a minimum order of ₱".number_format((float)$offer["minimum_spend"],2)."."];
    }

    $codeUseStmt=$conn->prepare("SELECT COUNT(*) FROM orders WHERE voucher_code=?");
    $codeUseStmt->execute([$code]);
    $totalUses=(int)$codeUseStmt->fetchColumn();
    if($offer["total_usage_limit"]!==null&&$totalUses>=(int)$offer["total_usage_limit"]){
        return["valid"=>false,"message"=>"This voucher has reached its usage limit."];
    }
    if($offer["per_customer_limit"]!==null){
        $customerUseStmt=$conn->prepare("SELECT COUNT(*) FROM orders WHERE voucher_code=? AND user_id=?");
        $customerUseStmt->execute([$code,$userId]);
        if((int)$customerUseStmt->fetchColumn()>=(int)$offer["per_customer_limit"]){
            return["valid"=>false,"message"=>"You have already used this voucher the maximum number of times."];
        }
    }

    $products=json_decode((string)($offer["eligible_products"]??"[]"),true)?:[];
    $categories=json_decode((string)($offer["eligible_categories"]??"[]"),true)?:[];
    $sets=json_decode((string)($offer["required_product_sets"]??"[]"),true)?:[];
    $itemsByCode=[];
    foreach($items as $item){
        $productCode=(string)($item["product_code"]??"");
        if($productCode!==""){
            $itemsByCode[$productCode]=[
                "price"=>(float)$item["price"],
                "quantity"=>(int)$item["quantity"],
                "category"=>(string)($item["category"]??"")
            ];
        }
    }

    $eligibleSubtotal=0.0;
    if(is_array($sets)&&$sets){
        $eligibleCodes=[];
        foreach($sets as $set){
            if(!is_array($set)||!$set)continue;
            $bundleCount=min(array_map(
                fn($productCode)=>(int)($itemsByCode[(string)$productCode]["quantity"]??0),
                $set
            ));
            if($bundleCount<1)continue;
            foreach(array_unique($set) as $productCode){
                $productCode=(string)$productCode;
                if(isset($itemsByCode[$productCode])){
                    $eligibleCodes[$productCode]=($eligibleCodes[$productCode]??0)+$bundleCount;
                }
            }
        }
        foreach($eligibleCodes as $productCode=>$quantity){
            $eligibleSubtotal+=$itemsByCode[$productCode]["price"]*min($quantity,$itemsByCode[$productCode]["quantity"]);
        }
        if($eligibleSubtotal<=0){
            return["valid"=>false,"message"=>"Add every required product from a qualifying bundle to use this voucher."];
        }
    }else{
        foreach($itemsByCode as $productCode=>$item){
            if($products&&!in_array($productCode,$products,true))continue;
            if($categories&&!in_array($item["category"],$categories,true))continue;
            $eligibleSubtotal+=$item["price"]*$item["quantity"];
        }
        if($eligibleSubtotal<=0){
            return["valid"=>false,"message"=>"Your selected products are not eligible for this voucher."];
        }
    }

    if($offer["discount_type"]==="fixed"){
        $discount=(float)$offer["discount_value"];
    }else{
        $discount=$eligibleSubtotal*(float)$offer["discount_value"]/100;
    }
    if($offer["discount_cap"]!==null){
        $discount=min($discount,(float)$offer["discount_cap"]);
    }
    $discount=round(min($discount,$eligibleSubtotal,$subtotal),2);
    return["valid"=>true,"code"=>$code,"discount"=>$discount];
}
?>
