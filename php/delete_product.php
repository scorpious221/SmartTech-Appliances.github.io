<?php

require_once "database.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../admin.php");
    exit;
}

$id = (int) ($_POST["id"] ?? 0);

if ($id <= 0) {
    die("Invalid product.");
}

$stmt = $conn->prepare("
    DELETE FROM products
    WHERE id = ?
");

$stmt->execute([$id]);

header("Location: ../admin.php?page=products");
exit;
?>