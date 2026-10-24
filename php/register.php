<?php
session_start();
require_once "database.php";

function back($message){
    $_SESSION["auth_message"]=$message;
    $_SESSION["auth_error"]=true;
    header("Location: ../login.php#register");
    exit;
}

if($_SERVER["REQUEST_METHOD"]!=="POST"){
    header("Location: ../login.php");
    exit;
}

$username=strtolower(trim($_POST["username"]??""));
$email=trim($_POST["email"]??"");
$password=$_POST["password"]??"";
$confirmPassword=$_POST["confirm_password"]??"";
$fullName=trim($_POST["full_name"]??"");

if(!$username||!$email||!$password||!$confirmPassword||!$fullName){
    back("Please fill in all fields.");
}

if($password!==$confirmPassword){
    back("Passwords do not match.");
}

if($username==="admin"){
    back("This username is reserved.");
}

if(!preg_match('/^[a-z0-9_.-]{3,20}$/',$username)){
    back("Username: 3–20 characters using letters, numbers, _ . -");
}

if(!filter_var($email,FILTER_VALIDATE_EMAIL)){
    back("Enter a valid email address.");
}

if(strlen($password)<8){
    back("Password must be at least 8 characters.");
}

$check=$conn->prepare("SELECT id FROM users WHERE username=?");
$check->execute([$username]);

if($check->fetch()){
    back("Username already exists.");
}

$check=$conn->prepare("SELECT id FROM users WHERE email=?");
$check->execute([$email]);

if($check->fetch()){
    back("Email is already registered.");
}

$hashed=password_hash($password,PASSWORD_DEFAULT);

$stmt=$conn->prepare("
    INSERT INTO users(
        username,
        email,
        password,
        full_name,
        role
    )
    VALUES(?,?,?,?,'customer')
");

$stmt->execute([
    $username,
    $email,
    $hashed,
    $fullName
]);

$_SESSION["auth_message"]="Account created! You can now log in.";
$_SESSION["auth_error"]=false;

header("Location: ../login.php");
exit;
?>