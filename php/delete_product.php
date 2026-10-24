<?php
require_once "database.php";
require_once "admin_media.php";
requireAdminPost();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../admin.php");
    exit;
}

$id = filter_var($_POST["id"] ?? null,FILTER_VALIDATE_INT);

if ($id === false || $id <= 0) {
    die("Invalid product.");
}

$stmt=$conn->prepare("SELECT image FROM products WHERE id=?");
$stmt->execute([$id]);
$primaryImage=$stmt->fetchColumn();
if($primaryImage===false){
    http_response_code(404);
    exit("Product not found.");
}
$stmt=$conn->prepare("SELECT file_path FROM product_media WHERE product_id=?");
$stmt->execute([$id]);
$mediaPaths=$stmt->fetchAll(PDO::FETCH_COLUMN);
$delete=$conn->prepare("DELETE FROM products WHERE id=?");
$delete->execute([$id]);
foreach(array_merge([$primaryImage],$mediaPaths) as $path)deleteAdminUpload((string)$path);

header("Location: ../admin.php?page=products");
exit;
?>