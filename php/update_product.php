<?php
session_start();
require_once "database.php";

if(!isset($_SESSION["user_id"])||($_SESSION["role"]??"")!=="admin"){
header("Location: ../login.php");
exit;
}

if($_SERVER["REQUEST_METHOD"]!=="POST"){
header("Location: ../admin.php?page=products");
exit;
}

$id=(int)($_POST["id"]??0);
$name=trim($_POST["name"]??"");
$price=(float)($_POST["price"]??0);
$stock=(int)($_POST["stock"]??0);
$category=strtolower(trim($_POST["category"]??""));
$subcategory=trim($_POST["subcategory"]??"");
$description=trim($_POST["description"]??"");
$existingImage=trim($_POST["existing_image"]??"");

$allowedCategories=["household","entertainment","kitchen"];

if(
$id<=0||
$name===""||
$price<=0||
$stock<0||
$stock>100||
!in_array($category,$allowedCategories,true)||
$subcategory===""
){
header("Location: ../admin.php?page=products");
exit;
}

$imagePath=$existingImage;

if(isset($_FILES["image"])&&$_FILES["image"]["error"]!==UPLOAD_ERR_NO_FILE){

if($_FILES["image"]["error"]!==UPLOAD_ERR_OK){
header("Location: ../admin.php?page=products");
exit;
}

if($_FILES["image"]["size"]>5*1024*1024){
header("Location: ../admin.php?page=products");
exit;
}

$finfo=new finfo(FILEINFO_MIME_TYPE);
$mime=$finfo->file($_FILES["image"]["tmp_name"]);

$allowedTypes=[
"image/jpeg"=>"jpg",
"image/png"=>"png",
"image/webp"=>"webp",
"image/gif"=>"gif"
];

if(!isset($allowedTypes[$mime])){
header("Location: ../admin.php?page=products");
exit;
}

$extension=$allowedTypes[$mime];

$baseName=preg_replace("/[^a-zA-Z0-9_-]/","-",pathinfo($_FILES["image"]["name"],PATHINFO_FILENAME));
$baseName=trim($baseName,"-");

if($baseName===""){
$baseName="product";
}

$fileName=$baseName."-".time().".".$extension;

$uploadFolder=__DIR__."/../images/";

if(!is_dir($uploadFolder)){
mkdir($uploadFolder,0777,true);
}

$destination=$uploadFolder.$fileName;

if(!move_uploaded_file($_FILES["image"]["tmp_name"],$destination)){
header("Location: ../admin.php?page=products");
exit;
}

$imagePath="images/".$fileName;
}

if($imagePath===""){
header("Location: ../admin.php?page=products");
exit;
}

$stmt=$conn->prepare("
UPDATE products
SET name=?,price=?,image=?,category=?,subcategory=?,description=?,stock=?
WHERE id=?
");

$stmt->execute([
$name,
$price,
$imagePath,
$category,
$subcategory,
$description,
$stock,
$id
]);

header("Location: ../admin.php?page=products");
exit;
?>