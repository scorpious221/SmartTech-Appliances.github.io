<?php

require_once "database.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../admin.php");
    exit;
}

$id = (int) ($_POST["id"] ?? 0);
$amount = (int) ($_POST["amount"] ?? 0);

if ($id <= 0) {
    die("Invalid product.");
}

$stmt = $conn->prepare("
    UPDATE products
    SET stock = GREATEST(0, stock + :amount)
    WHERE id = :id
");

$stmt->execute([
    ":amount" => $amount,
    ":id" => $id
]);

header("Location: ../admin.php?page=products");
exit;
?>