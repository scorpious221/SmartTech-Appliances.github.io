<?php
require_once "database.php";
require_once "admin_media.php";
requireAdminPost();

$id = filter_var($_POST["id"] ?? null, FILTER_VALIDATE_INT);
$amount = filter_var($_POST["amount"] ?? null, FILTER_VALIDATE_INT);

if ($id === false || $id <= 0 || $amount === false || !in_array($amount, [-1, 1], true)) {
    http_response_code(400);
    exit("Invalid stock update.");
}

$stmt = $conn->prepare("
    UPDATE products
    SET stock = LEAST(100, GREATEST(0, stock + :amount))
    WHERE id = :id
");

$stmt->execute([
    ":amount" => $amount,
    ":id" => $id
]);

header("Location: ../admin.php?page=products");
exit;
?>