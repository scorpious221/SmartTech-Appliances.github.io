<?php
require_once "database.php";
require_once "admin_media.php";
requireAdminPost();

$id=filter_var($_POST["id"]??null,FILTER_VALIDATE_INT);
$name=trim($_POST["name"]??"");
$price=filter_var($_POST["price"]??null,FILTER_VALIDATE_FLOAT);
$onSale=($_POST["on_sale"]??"") === "1";
$salePrice=$onSale?filter_var($_POST["sale_price"]??null,FILTER_VALIDATE_FLOAT):null;
$isBestseller=($_POST["is_bestseller"]??"") === "1";
$category=trim($_POST["category"]??"");
$subcategory=trim($_POST["subcategory"]??"");
$description=trim($_POST["description"]??"");
$stock=filter_var($_POST["stock"]??null,FILTER_VALIDATE_INT);

if($id===false||$id<=0||$name===""||$price===false||$price<=0||($onSale&&($salePrice===false||$salePrice<=0||$salePrice>=$price))||$category===""||$subcategory===""||$stock===false||$stock<0){
    http_response_code(400);
    exit("Invalid product information.");
}

$stmt=$conn->prepare("SELECT image FROM products WHERE id=?");
$stmt->execute([$id]);
$oldImage=$stmt->fetchColumn();
if($oldImage===false){
    http_response_code(404);
    exit("Product not found.");
}

$primary=saveAdminUpload($_FILES["primary_image"]??[]);
if($primary&&$primary["type"]!=="image"){
    deleteAdminUpload($primary["path"]);
    http_response_code(400);
    exit("The primary product image must be a supported image file.");
}
$gallery=[];
try{
    $gallery=saveAdminUploads($_FILES["product_gallery"]??[]);
    if(($primary["size"]??0)+array_sum(array_column($gallery,"size"))>35*1024*1024){
        throw new RuntimeException("All product media uploads must total 35 MB or less per save.");
    }
}catch(Throwable $error){
    if($primary)deleteAdminUpload($primary["path"]);
    throw$error;
}
$removeIds=array_values(array_unique(array_filter(array_map("intval",$_POST["remove_media"]??[]),fn($mediaId)=>$mediaId>0)));
$removedPaths=[];

try{
    $conn->beginTransaction();
    $image=$primary["path"]??$oldImage;
    $update=$conn->prepare("
        UPDATE products
        SET name=?,price=?,sale_price=?,is_bestseller=?,image=?,category=?,subcategory=?,description=?,stock=?
        WHERE id=?
    ");
    $update->execute([$name,$price,$salePrice,$isBestseller?1:0,$image,$category,$subcategory,$description,$stock,$id]);

    if($primary&&$oldImage!==""&&$oldImage!==$primary["path"]){
        $orderStmt=$conn->prepare("SELECT COALESCE(MAX(sort_order),0)+1 FROM product_media WHERE product_id=?");
        $orderStmt->execute([$id]);
        $oldOrder=(int)$orderStmt->fetchColumn();
        $oldMedia=$conn->prepare("INSERT INTO product_media(product_id,media_type,file_path,sort_order) VALUES(?,?,?,?)");
        $oldMedia->execute([$id,"image",$oldImage,$oldOrder]);
    }

    if($removeIds){
        $placeholders=implode(",",array_fill(0,count($removeIds),"?"));
        $selectRemoved=$conn->prepare("SELECT file_path FROM product_media WHERE product_id=? AND id IN ($placeholders)");
        $selectRemoved->execute(array_merge([$id],$removeIds));
        $removedPaths=$selectRemoved->fetchAll(PDO::FETCH_COLUMN);
        $delete=$conn->prepare("DELETE FROM product_media WHERE product_id=? AND id IN ($placeholders)");
        $delete->execute(array_merge([$id],$removeIds));
    }

    $nextOrderStmt=$conn->prepare("SELECT COALESCE(MAX(sort_order),0)+1 FROM product_media WHERE product_id=?");
    $nextOrderStmt->execute([$id]);
    $nextOrder=(int)$nextOrderStmt->fetchColumn();
    $insert=$conn->prepare("INSERT INTO product_media(product_id,media_type,file_path,sort_order) VALUES(?,?,?,?)");
    foreach($gallery as $media){
        $insert->execute([$id,$media["type"],$media["path"],$nextOrder++]);
    }
    $conn->commit();
}catch(Throwable $error){
    if($conn->inTransaction())$conn->rollBack();
    if($primary)deleteAdminUpload($primary["path"]);
    foreach($gallery as $media)deleteAdminUpload($media["path"]);
    throw$error;
}

foreach($removedPaths as $path)deleteAdminUpload((string)$path);
header("Location: ../admin.php?page=products");
exit;
?>
