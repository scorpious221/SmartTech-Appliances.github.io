<?php
session_start();
header("Content-Type: application/json; charset=utf-8");

function wishlistResponse($status,$payload){
    http_response_code($status);
    echo json_encode($payload,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    exit;
}

if(!isset($_SESSION["user_id"])||($_SESSION["role"]??"")!=="customer"){
    wishlistResponse(401,["message"=>"Log in to access your wishlist."]);
}

require_once "database.php";
$userId=(int)$_SESSION["user_id"];

try{
    if($_SERVER["REQUEST_METHOD"]==="GET"){
        $stmt=$conn->prepare("
            SELECT
                p.product_code AS id,
                p.name,
                COALESCE(p.sale_price,p.price) AS price,
                p.price AS regularPrice,
                (p.sale_price IS NOT NULL) AS isSale,
                p.is_bestseller AS isBestseller,
                p.image AS img,
                p.category,
                p.subcategory AS sub,
                COALESCE(r.average_rating,0) AS averageRating,
                COALESCE(r.review_count,0) AS reviewCount,
                p.stock
            FROM user_wishlist w
            JOIN products p ON p.id=w.product_id
            LEFT JOIN (
                SELECT product_id,AVG(rating) AS average_rating,COUNT(*) AS review_count
                FROM product_reviews
                GROUP BY product_id
            ) r ON r.product_id=p.id
            WHERE w.user_id=?
            ORDER BY w.created_at DESC
        ");
        $stmt->execute([$userId]);
        $products=$stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach($products as &$product){
            $product["price"]=(float)$product["price"];
            $product["regularPrice"]=(float)$product["regularPrice"];
            $product["isSale"]=(bool)$product["isSale"];
            $product["isBestseller"]=(bool)$product["isBestseller"];
            $product["averageRating"]=(float)$product["averageRating"];
            $product["reviewCount"]=(int)$product["reviewCount"];
            $product["stock"]=(int)$product["stock"];
        }
        unset($product);
        wishlistResponse(200,[
            "items"=>array_column($products,"id"),
            "products"=>$products
        ]);
    }

    if($_SERVER["REQUEST_METHOD"]!=="POST"){
        header("Allow: GET, POST");
        wishlistResponse(405,["message"=>"This wishlist request method is not supported."]);
    }

    $input=json_decode(file_get_contents("php://input"),true);
    if(!is_array($input)){
        wishlistResponse(400,["message"=>"A valid wishlist request is required."]);
    }

    $action=$input["action"]??"";
    $productCode=trim((string)($input["product_code"]??""));
    if(!in_array($action,["add","remove"],true)||$productCode===""||strlen($productCode)>100){
        wishlistResponse(400,["message"=>"A valid product and wishlist action are required."]);
    }

    $productStmt=$conn->prepare("SELECT id FROM products WHERE product_code=? LIMIT 1");
    $productStmt->execute([$productCode]);
    $productId=$productStmt->fetchColumn();
    if($productId===false){
        wishlistResponse(404,["message"=>"That product is no longer available."]);
    }

    if($action==="add"){
        $stmt=$conn->prepare("INSERT IGNORE INTO user_wishlist(user_id,product_id) VALUES(?,?)");
        $stmt->execute([$userId,(int)$productId]);
    }else{
        $stmt=$conn->prepare("DELETE FROM user_wishlist WHERE user_id=? AND product_id=?");
        $stmt->execute([$userId,(int)$productId]);
    }

    $stmt=$conn->prepare("
        SELECT p.product_code
        FROM user_wishlist w
        JOIN products p ON p.id=w.product_id
        WHERE w.user_id=?
        ORDER BY w.created_at DESC
    ");
    $stmt->execute([$userId]);
    wishlistResponse(200,["items"=>$stmt->fetchAll(PDO::FETCH_COLUMN)]);
}catch(PDOException $error){
    error_log("Wishlist database request failed: ".$error->getMessage());
    wishlistResponse(500,["message"=>"Your wishlist could not be loaded. Please try again."]);
}
