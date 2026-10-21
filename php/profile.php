<?php
session_start();
require_once "database.php";

if(!isset($_SESSION["user_id"])||($_SESSION["role"]??"")!=="customer"){
header("Location: ../login.php?next=php/profile.php");
exit;
}

$userId=(int)$_SESSION["user_id"];
$message="";
$error=false;

if($_SERVER["REQUEST_METHOD"]==="POST"&&($_POST["action"]??"")==="profile"){
$email=trim($_POST["email"]??"");
$name=trim($_POST["full_name"]??"");
$age=(int)($_POST["age"]??0);
$sex=trim($_POST["sex"]??"");
$birthday=trim($_POST["birthday"]??"");
$contact=preg_replace("/\D/","",$_POST["contact"]??"");
$address=trim($_POST["address"]??"");

if(!$email||!$name||$age<=0||!$sex||!$birthday||strlen($contact)<10||!$address){
$message="Please complete all fields.";
$error=true;
}elseif(!filter_var($email,FILTER_VALIDATE_EMAIL)){
$message="Enter a valid email address.";
$error=true;
}else{
try{
$stmt=$conn->prepare("UPDATE users SET email=?,full_name=?,age=?,sex=?,birthday=?,contact=?,address=? WHERE id=?");
$stmt->execute([$email,$name,$age,$sex,$birthday,$contact,$address,$userId]);
$_SESSION["full_name"]=$name;
$message="Profile updated successfully.";
}catch(PDOException $e){
$message=$e->getCode()==="23000"?"That email is already being used.":"Unable to update profile.";
$error=true;
}
}
}

if($_SERVER["REQUEST_METHOD"]==="POST"&&($_POST["action"]??"")==="password"){
$current=$_POST["current_password"]??"";
$new=$_POST["new_password"]??"";
$confirm=$_POST["confirm_password"]??"";

$stmt=$conn->prepare("SELECT password FROM users WHERE id=?");
$stmt->execute([$userId]);
$hash=$stmt->fetchColumn();

if(!$hash||!password_verify($current,$hash)){
$message="Current password is incorrect.";
$error=true;
}elseif(strlen($new)<6){
$message="New password must be at least 6 characters.";
$error=true;
}elseif($new!==$confirm){
$message="Passwords do not match.";
$error=true;
}else{
$stmt=$conn->prepare("UPDATE users SET password=? WHERE id=?");
$stmt->execute([password_hash($new,PASSWORD_DEFAULT),$userId]);
$message="Password updated successfully.";
}
}

$stmt=$conn->prepare("SELECT id,username,email,full_name,age,sex,birthday,contact,address FROM users WHERE id=? AND role='customer' LIMIT 1");
$stmt->execute([$userId]);
$user=$stmt->fetch(PDO::FETCH_ASSOC);

if(!$user){
session_destroy();
header("Location: ../login.php");
exit;
}

$stmt=$conn->prepare("SELECT order_number,status,grand_total,created_at FROM orders WHERE user_id=? ORDER BY id DESC");
$stmt->execute([$userId]);
$orders=$stmt->fetchAll(PDO::FETCH_ASSOC);

function e($v){return htmlspecialchars((string)$v,ENT_QUOTES,"UTF-8");}
function peso($v){return "₱".number_format((float)$v,2);}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>My Profile — SmartTech Appliances</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<link href="../styles.css" rel="stylesheet">
<link href="../shop.css" rel="stylesheet">
<link href="../pages.css" rel="stylesheet">

<style>
body{background:var(--st-cream)}
.pf-wrap{max-width:1120px;margin:0 auto;padding:36px 20px 70px}
.pf-back{display:inline-flex;align-items:center;gap:8px;color:var(--st-ink);font-size:13px;font-weight:700;text-decoration:none;margin-bottom:28px}
.pf-back:hover{color:var(--st-clay)}
.pf-top{display:flex;align-items:center;gap:20px;margin-bottom:24px}
.pf-av{width:72px;height:72px;border-radius:50%;background:var(--st-ink);color:#fff;display:grid;place-items:center;font-size:28px;font-weight:800;flex:none}
.pf-top h1{font-size:30px;margin:0}
.pf-top small{color:var(--st-ink-2)}
.pf{display:grid;grid-template-columns:230px minmax(0,1fr);gap:24px}
.pf-nav{padding:12px;display:flex;flex-direction:column;gap:4px;align-self:start}
.pf-nav button,.pf-nav a{display:flex;align-items:center;gap:12px;min-height:48px;padding:0 14px;border:0;border-radius:12px;background:none;color:var(--st-ink);font-size:14px;font-weight:600;text-decoration:none;text-align:left;white-space:nowrap}
.pf-nav button:hover,.pf-nav a:hover{background:var(--st-cream)}
.pf-nav button.active{background:var(--st-cream);color:var(--st-clay);font-weight:800}
.pf-nav .out{color:#A3321A}
.pf-content{min-width:0}
.g2{display:grid;grid-template-columns:1fr 1fr;gap:0 16px}
.g2 .full{grid-column:1/-1}
.field select{display:block;width:100%;margin-top:6px;border:1px solid var(--st-sand);border-radius:12px;padding:14px;background:#fff}
.field input[readonly]{background:var(--st-cream)}
.orders{display:grid;gap:12px}
.order{padding:16px;border:1px solid var(--st-sand);border-radius:14px;background:#fff;display:flex;justify-content:space-between;align-items:center;gap:16px;text-decoration:none;color:var(--st-ink)}
.order:hover{border-color:var(--st-clay)}
.order small{display:block;color:var(--st-ink-2);margin-top:4px}
.status{font-weight:700;color:var(--st-clay);white-space:nowrap}

@media(max-width:991.98px){
body{padding-bottom:76px}
.pf-wrap{padding:28px 16px 50px}
.pf{grid-template-columns:170px minmax(0,1fr);gap:16px}
.g2{grid-template-columns:1fr}
.g2 .full{grid-column:auto}
}

@media(max-width:650px){
.pf-wrap{padding:22px 8px 40px}
.pf-back{margin-bottom:22px}
.pf-top{gap:14px;margin-bottom:18px}
.pf-av{width:56px;height:56px;font-size:20px}
.pf-top h1{font-size:22px}
.pf-top small{font-size:10px}
.pf{grid-template-columns:100px minmax(0,1fr);gap:10px}
.pf-nav{padding:7px}
.pf-nav button,.pf-nav a{min-height:43px;padding:0 7px;gap:7px;font-size:9px;border-radius:10px}
.pf-nav i{font-size:11px}
.pf-content{padding:13px!important}
.pf-content h2{font-size:20px!important;margin-bottom:18px!important}
.field{font-size:10px}
.field input,.field select{font-size:12px;padding:12px}
.btn-clay{font-size:11px;padding-left:18px;padding-right:18px}
.order{padding:12px;align-items:flex-start;flex-direction:column;font-size:10px}
}

@media(max-width:480px){
.pf-wrap{padding-left:4px;padding-right:8px}
.pf{grid-template-columns:100px minmax(0,1fr);gap:9px}
.pf-nav{padding:6px}
.pf-nav button,.pf-nav a{padding:0 6px;font-size:9px;gap:6px}
.pf-content{padding:12px!important}
}
</style>
</head>

<body>

<main class="pf-wrap">

<a class="pf-back" href="../index.html">
<i class="bi bi-arrow-left"></i>
Back to Home
</a>

<div class="pf-top">
<span class="pf-av"><?php echo e(strtoupper(substr($user["full_name"]?:$user["username"],0,1))); ?></span>
<div>
<h1><?php echo e($user["full_name"]); ?></h1>
<small>@<?php echo e($user["username"]); ?> · <?php echo e($user["email"]); ?></small>
</div>
</div>

<?php if($message): ?>
<p class="msg <?php echo $error?"err":"ok"; ?>"><?php echo e($message); ?></p>
<?php endif; ?>

<div class="pf">

<nav class="panel pf-nav" aria-label="Account navigation">
<button class="active" type="button" data-tab="profile"><i class="bi bi-person"></i>My Profile</button>
<button type="button" data-tab="orders"><i class="bi bi-receipt"></i>My Orders</button>
<button type="button" data-tab="password"><i class="bi bi-shield-lock"></i>Password</button>
<a class="out" href="logout.php"><i class="bi bi-box-arrow-right"></i>Log out</a>
</nav>

<div class="panel pf-content">

<section id="profileSec">
<h2 class="fs-4 mb-4">My Profile</h2>

<form method="POST">
<input type="hidden" name="action" value="profile">

<div class="g2">

<label class="field">
Username
<input value="<?php echo e($user["username"]); ?>" readonly>
</label>

<label class="field">
Email
<input name="email" type="email" value="<?php echo e($user["email"]); ?>" required>
</label>

<label class="field full">
Full name
<input name="full_name" value="<?php echo e($user["full_name"]); ?>" required>
</label>

<label class="field">
Age
<input name="age" type="number" min="1" max="120" value="<?php echo e($user["age"]); ?>" required>
</label>

<label class="field">
Sex
<select name="sex" required>
<option value="">Select</option>
<option value="Male" <?php echo $user["sex"]==="Male"?"selected":""; ?>>Male</option>
<option value="Female" <?php echo $user["sex"]==="Female"?"selected":""; ?>>Female</option>
<option value="Other" <?php echo $user["sex"]==="Other"?"selected":""; ?>>Other</option>
</select>
</label>

<label class="field">
Birthday
<input name="birthday" type="date" value="<?php echo e($user["birthday"]); ?>" required>
</label>

<label class="field">
Contact number
<input name="contact" type="tel" inputmode="numeric" value="<?php echo e($user["contact"]); ?>" required>
</label>

<label class="field full">
Address
<input name="address" value="<?php echo e($user["address"]); ?>" required>
</label>

</div>

<button class="btn-clay" type="submit">Save Changes</button>
</form>
</section>

<section id="ordersSec" hidden>
<h2 class="fs-4 mb-4">My Orders</h2>

<?php if($orders): ?>

<div class="orders">

<?php foreach($orders as $order): ?>

<a class="order" href="../track.php?order=<?php echo urlencode($order["order_number"]); ?>">

<div>
<b><?php echo e($order["order_number"]); ?></b>
<small><?php echo e($order["created_at"]); ?> · <?php echo peso($order["grand_total"]); ?></small>
</div>

<span class="status"><?php echo e($order["status"]); ?></span>

</a>

<?php endforeach; ?>

</div>

<?php else: ?>

<div class="text-center py-5">
<i class="bi bi-receipt fs-1"></i>
<p class="mt-3">You have no orders yet.</p>
<a class="btn-clay d-inline-grid align-items-center text-decoration-none px-4" href="../products.php">Go Shopping</a>
</div>

<?php endif; ?>

</section>

<section id="passwordSec" hidden>
<h2 class="fs-4 mb-4">Change Password</h2>

<form method="POST" style="max-width:440px">
<input type="hidden" name="action" value="password">

<label class="field">
Current password
<input name="current_password" type="password" autocomplete="current-password" required>
</label>

<label class="field">
New password
<input name="new_password" type="password" minlength="6" autocomplete="new-password" required>
</label>

<label class="field">
Confirm new password
<input name="confirm_password" type="password" minlength="6" autocomplete="new-password" required>
</label>

<button class="btn-clay" type="submit">Update Password</button>
</form>
</section>

</div>

</div>

</main>

<nav class="st-bottomnav d-lg-none" aria-label="Quick navigation">

<a href="../index.html">
<i class="bi bi-house"></i>
<span>Home</span>
</a>

<a href="../products.php">
<i class="bi bi-shop"></i>
<span>Shop</span>
</a>

<a href="../cart.php">
<i class="bi bi-bag"></i>
<span>Cart</span>
</a>

<a href="profile.php" class="active" aria-current="page">
<i class="bi bi-person"></i>
<span>Account</span>
</a>

</nav>

<script>
document.querySelectorAll('[data-tab]').forEach(b=>{
b.onclick=()=>{
document.querySelectorAll('[data-tab]').forEach(x=>x.classList.remove('active'));
b.classList.add('active');
document.getElementById('profileSec').hidden=b.dataset.tab!=='profile';
document.getElementById('ordersSec').hidden=b.dataset.tab!=='orders';
document.getElementById('passwordSec').hidden=b.dataset.tab!=='password';
};
});
</script>

</body>
</html>