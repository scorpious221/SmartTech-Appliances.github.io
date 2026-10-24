<?php
require_once "database.php";

$username="admin";
$email="admin@smarttech.local";
$password="admin123";
$fullName="SmartTech Administrator";

$check=$conn->prepare("SELECT id FROM users WHERE username=? OR email=?");
$check->execute([$username,$email]);

if($check->fetch()){
    die("Admin account already exists.");
}

$hashed=password_hash($password,PASSWORD_DEFAULT);

$stmt=$conn->prepare("
    INSERT INTO users(username,email,password,full_name,role)
    VALUES(?,?,?,?,?)
");

$stmt->execute([
    $username,
    $email,
    $hashed,
    $fullName,
    "admin"
]);

echo "Admin account created successfully!";
?>