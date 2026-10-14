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
$fullName=trim($_POST["full_name"]??"");
$age=(int)($_POST["age"]??0);
$sex=trim($_POST["sex"]??"");
$birthday=trim($_POST["birthday"]??"");
$contact=trim($_POST["contact"]??"");
$address=trim($_POST["address"]??"");

if(!$username||!$email||!$password||!$fullName||!$age||!$sex||!$birthday||!$contact||!$address){
    back("Please fill in all fields.");
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

if(strlen($password)<6){
    back("Password must be at least 6 characters.");
}

if($age<1||$age>120){
    back("Enter a valid age.");
}

if(!preg_match('/^[0-9]{10,11}$/',$contact)){
    back("Enter a valid contact number.");
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
        age,
        sex,
        birthday,
        contact,
        address,
        role
    )
    VALUES(?,?,?,?,?,?,?,?,?,'customer')
");

$stmt->execute([
    $username,
    $email,
    $hashed,
    $fullName,
    $age,
    $sex,
    $birthday,
    $contact,
    $address
]);

$_SESSION["auth_message"]="Account created! You can now log in.";
$_SESSION["auth_error"]=false;

header("Location: ../login.php");
exit;
?>