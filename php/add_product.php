<?php

require_once "database.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../admin.php");
    exit;
}

$productCode = trim($_POST["product_code"] ?? "");
$name = trim($_POST["name"] ?? "");
$price = (float) ($_POST["price"] ?? 0);
$image = trim($_POST["image"] ?? "");
$category = trim($_POST["category"] ?? "");
$subcategory = trim($_POST["subcategory"] ?? "");
$description = trim($_POST["description"] ?? "");
$stock = max(0, (int) ($_POST["stock"] ?? 0));

if (
    $productCode === "" ||
    $name === "" ||
    $price <= 0 ||
    $category === ""
) {
    die("Please fill in all required product fields.");
}

$sql = "
    INSERT INTO products
    (
        product_code,
        name,
        price,
        image,
        category,
        subcategory,
        description,
        stock
    )
    VALUES
    (
        :product_code,
        :name,
        :price,
        :image,
        :category,
        :subcategory,
        :description,
        :stock
    )
";

$stmt = $conn->prepare($sql);

$stmt->execute([
    ":product_code" => $productCode,
    ":name" => $name,
    ":price" => $price,
    ":image" => $image,
    ":category" => $category,
    ":subcategory" => $subcategory,
    ":description" => $description,
    ":stock" => $stock
]);

header("Location: ../admin.php?page=products");
exit;
?>