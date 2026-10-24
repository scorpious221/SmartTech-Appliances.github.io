<?php
require_once "database.php";
require_once "admin_media.php";
requireAdminPost();

$productCode=trim($_POST["product_code"]??"");
$name=trim($_POST["name"]??"");
$price=filter_var($_POST["price"]??null,FILTER_VALIDATE_FLOAT);
$onSale=($_POST["on_sale"]??"") === "1";
$salePrice=$onSale?filter_var($_POST["sale_price"]??null,FILTER_VALIDATE_FLOAT):null;
$isBestseller=($_POST["is_bestseller"]??"") === "1";
$category=trim($_POST["category"]??"");
$subcategory=trim($_POST["subcategory"]??"");
$description=trim($_POST["description"]??"");
$stock=filter_var($_POST["stock"]??null,FILTER_VALIDATE_INT);

if($productCode===""||strlen($productCode)>100||$name===""||$price===false||$price<=0||$price>999999||($onSale&&($salePrice===false||$salePrice<=0||$salePrice>999999||$salePrice>=$price))||$category===""||$subcategory===""||$stock===false||$stock<0||$stock>100){
    http_response_code(400);
    exit("Please provide valid product details. Product prices must not exceed 999,999, and stock must be between 0 and 100.");
}

$primary=saveAdminUpload($_FILES["primary_image"]??[]);
if(!$primary||$primary["type"]!=="image"){
    if($primary)deleteAdminUpload($primary["path"]);
    http_response_code(400);
    exit("Upload a product image to create the product.");
}
$gallery=[];
try{
    $gallery=saveAdminUploads($_FILES["product_gallery"]??[]);
    if($primary["size"]+array_sum(array_column($gallery,"size"))>35*1024*1024){
        throw new RuntimeException("All product media uploads must total 35 MB or less per save.");
    }
}catch(Throwable $error){
    deleteAdminUpload($primary["path"]);
    throw$error;
}
foreach($gallery as $media){
    if($media["type"]!=="image"&&$media["type"]!=="video"){
        http_response_code(400);
        exit("Unsupported product media.");
    }
}

try{
    $conn->beginTransaction();
    $stmt=$conn->prepare("
        INSERT INTO products(product_code,name,price,sale_price,is_bestseller,image,category,subcategory,description,stock)
        VALUES(?,?,?,?,?,?,?,?,?,?)
    ");
    $stmt->execute([$productCode,$name,$price,$salePrice,$isBestseller?1:0,$primary["path"],$category,$subcategory,$description,$stock]);
    $productId=(int)$conn->lastInsertId();
    $mediaStmt=$conn->prepare("INSERT INTO product_media(product_id,media_type,file_path,sort_order) VALUES(?,?,?,?)");
    foreach($gallery as $index=>$media){
        $mediaStmt->execute([$productId,$media["type"],$media["path"],$index+1]);
    }
    $conn->commit();
}catch(Throwable $error){
    if($conn->inTransaction())$conn->rollBack();
    deleteAdminUpload($primary["path"]);
    foreach($gallery as $media)deleteAdminUpload($media["path"]);
    throw$error;
}

header("Location: ../admin.php?page=products");
exit;
?>
