<?php

require_once "database.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../admin.php");
    exit;
}

$id = (int) ($_POST["id"] ?? 0);
$name = trim($_POST["name"] ?? "");
$price = (float) ($_POST["price"] ?? 0);
$image = trim($_POST["image"] ?? "");
$category = trim($_POST["category"] ?? "");
$subcategory = trim($_POST["subcategory"] ?? "");
$description = trim($_POST["description"] ?? "");
$stock = max(0, (int) ($_POST["stock"] ?? 0));

if (
    $id <= 0 ||
    $name === "" ||
    $price <= 0 ||
    $category === ""
) {
    die("Invalid product information.");
}

$stmt = $conn->prepare("
    UPDATE products
    SET
        name = :name,
        price = :price,
        image = :image,
        category = :category,
        subcategory = :subcategory,
        description = :description,
        stock = :stock
    WHERE id = :id
");

$stmt->execute([
    ":name" => $name,
    ":price" => $price,
    ":image" => $image,
    ":category" => $category,
    ":subcategory" => $subcategory,
    ":description" => $description,
    ":stock" => $stock,
    ":id" => $id
]);

header("Location: ../admin.php?page=products");
exit;
?>