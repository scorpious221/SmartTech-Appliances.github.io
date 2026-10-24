<?php
session_start();
require_once "database.php";

function back($message){
    $_SESSION["auth_message"]=$message;
    $_SESSION["auth_error"]=true;
    header("Location: ../login.php");
    exit;
}

if($_SERVER["REQUEST_METHOD"]!=="POST"){
    header("Location: ../login.php");
    exit;
}

$username=strtolower(trim($_POST["username"]??""));
$password=$_POST["password"]??"";
$next=$_POST["next"]??"index.html";

if(!$username||!$password){
    back("Enter your username and password.");
}

$stmt=$conn->prepare("
    SELECT id,username,email,password,full_name,age,sex,birthday,contact,address,role
    FROM users
    WHERE username=?
    LIMIT 1
");

$stmt->execute([$username]);
$user=$stmt->fetch(PDO::FETCH_ASSOC);

if(!$user){
    back("Username not found.");
}

if(!password_verify($password,$user["password"])){
    back("Incorrect password.");
}

session_regenerate_id(true);

$_SESSION["user_id"]=(int)$user["id"];
$_SESSION["username"]=$user["username"];
$_SESSION["full_name"]=$user["full_name"];
$_SESSION["role"]=$user["role"];

if($user["role"]==="admin"){
?>
<!DOCTYPE html>
<html>
<body>
<script>
sessionStorage.setItem('st_admin_session','1');
location.replace('../admin.php');
</script>
</body>
</html>
<?php
exit;
}

if(!preg_match('/^[a-zA-Z0-9_.\/-]+\.(html|php)$/',$next)){
    $next="index.html";
}

$publicUser=[
    "username"=>$user["username"],
    "gmail"=>$user["email"],
    "name"=>$user["full_name"],
    "age"=>$user["age"],
    "sex"=>$user["sex"],
    "birthday"=>$user["birthday"],
    "contact"=>$user["contact"],
    "address"=>$user["address"]
];

$json=json_encode(
    $publicUser,
    JSON_HEX_TAG|
    JSON_HEX_AMP|
    JSON_HEX_APOS|
    JSON_HEX_QUOT
);
?>
<!DOCTYPE html>
<html>
<body>
<script>
const user=<?php echo $json; ?>;
localStorage.setItem('st_session',user.username);
localStorage.setItem('st_user:'+user.username,JSON.stringify(user));
location.replace(<?php echo json_encode("../".$next); ?>);
</script>
</body>
</html>