<?php
session_start();

if(isset($_SESSION["user_id"])){
if(($_SESSION["role"]??"")==="admin"){
header("Location: admin.php");
exit;
}
if(($_SESSION["role"]??"")==="customer"){
header("Location: php/profile.php");
exit;
}
}

$message=$_SESSION["auth_message"]??"";
$isError=$_SESSION["auth_error"]??false;

unset($_SESSION["auth_message"],$_SESSION["auth_error"]);

$next=$_GET["next"]??"index.html";

if(!preg_match('/^[a-zA-Z0-9_.\/-]+\.(html|php)$/',$next)){
$next="index.html";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>Log in / Register — SmartTech Appliances</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<link href="styles.css" rel="stylesheet">
<link href="shop.css" rel="stylesheet">
<link href="pages.css" rel="stylesheet">

<style>
html,body{min-height:100%}
body.au-page{min-height:100vh;background:linear-gradient(90deg,rgba(10,31,26,.9) 0%,rgba(10,31,26,.45) 100%),url("images/Hero.png") center/cover no-repeat,var(--st-ink)}
#main.pg{min-height:calc(100vh - 132px);display:flex;align-items:center;justify-content:center;padding-top:46px!important;padding-bottom:46px!important}
.au{width:100%;max-width:480px;margin:0 auto;transition:max-width .25s ease}
.au.wide{max-width:720px}
.au-head{text-align:center;margin-bottom:24px}
.au-head h1{font-size:38px;margin:14px 0 8px;color:var(--st-cream)}
.au-head .sub{margin:0 auto;color:var(--st-sand)}
.au .panel{width:100%;padding:28px;background:#fff;box-shadow:0 24px 60px rgba(0,0,0,.35);border-color:transparent}
.au .seg{display:grid;grid-template-columns:1fr 1fr}
.au .seg button{text-align:center}
.g2{display:grid;grid-template-columns:1fr 1fr;gap:0 16px}
.g2 .full{grid-column:1/-1}
.field select{display:block;width:100%;margin-top:6px;border:1px solid var(--st-sand);border-radius:12px;padding:14px;font-size:15px;font-weight:400;background:#fff}
.field select:focus{outline:2px solid var(--st-clay);border-color:var(--st-clay)}
.au .btn-clay{width:100%;margin-top:4px}
.au-note{text-align:center;font-size:12px;color:var(--st-mist);margin:20px 0 0}

/* PASSWORD EYE */
.pw-wrap{position:relative;margin-top:6px}
.pw-wrap input{width:100%;margin-top:0!important;padding-right:48px!important}
.pw-eye{position:absolute;right:5px;top:50%;transform:translateY(-50%);width:40px;height:40px;border:0;background:transparent;color:var(--st-ink-2);display:grid;place-items:center;border-radius:50%;font-size:18px;cursor:pointer}
.pw-eye:hover{background:var(--st-cream);color:var(--st-clay)}
.pw-eye:focus{outline:2px solid var(--st-clay);outline-offset:1px}

/* TABLET */
@media(max-width:991.98px){
#main.pg{min-height:auto;padding:38px 18px 46px!important}
.au{width:min(480px,100%);max-width:480px}
.au.wide{width:min(620px,100%);max-width:620px}
.au-head{margin-bottom:20px}
.au-head h1{font-size:34px}
.au .panel{padding:26px}
}

/* SMALL WINDOW / PHONE */
@media(max-width:575.98px){
#main.pg{padding:28px 14px 36px!important}
.au,.au.wide{width:100%;max-width:440px}
.au-head{margin-bottom:18px}
.au-head h1{font-size:28px;margin:10px 0 6px}
.au-head .sub{font-size:12px;line-height:1.5;max-width:330px}
.au .panel{padding:20px 18px;border-radius:18px}
.g2{grid-template-columns:1fr}
.g2 .full{grid-column:auto}
.au .seg button{font-size:13px}
.au-note{font-size:10px;margin-top:14px}
}

/* VERY SMALL */
@media(max-width:380px){
#main.pg{padding-left:10px!important;padding-right:10px!important}
.au .panel{padding:18px 14px}
.au-head h1{font-size:25px}
}
</style>
</head>

<body data-page="login" class="au-page">

<main id="main" class="container-xxl px-lg-5 pg">

<div class="au" id="au">

<div class="au-head">
<span class="shop-pill">SMARTTECH ACCOUNT</span>
<h1 id="t">Welcome back</h1>
<p class="sub" id="s">Log in to view your profile, orders and wishlist.</p>
</div>

<div class="panel">

<div class="seg" role="tablist">
<button type="button" role="tab" id="tl" aria-selected="true">Log in</button>
<button type="button" role="tab" id="tr" aria-selected="false">Register</button>
</div>

<form id="fl" action="php/login.php" method="POST" novalidate>

<input type="hidden" name="next" value="<?php echo htmlspecialchars($next); ?>">

<label class="field">
Username
<input id="lu" name="username" autocomplete="username">
</label>

<label class="field">
Password

<div class="pw-wrap">

<input id="lp" name="password" type="password" autocomplete="current-password">

<button class="pw-eye" type="button" data-password="lp" aria-label="Show password">
<i class="bi bi-eye"></i>
</button>

</div>

</label>

<button class="btn-clay" type="submit">Log in</button>

</form>

<form id="fr" action="php/register.php" method="POST" novalidate hidden>

<div class="g2">

<label class="field">
Username
<input id="ru" name="username" autocomplete="username">
</label>

<label class="field">
Email
<input id="re" name="email" type="email" autocomplete="email">
</label>

<label class="field full">
Password (at least 6 characters)

<div class="pw-wrap">

<input id="rp" name="password" type="password" autocomplete="new-password">

<button class="pw-eye" type="button" data-password="rp" aria-label="Show password">
<i class="bi bi-eye"></i>
</button>

</div>

</label>

<label class="field full">
Full name
<input id="rn" name="full_name" autocomplete="name">
</label>

<label class="field">
Age
<input id="ra" name="age" type="number" min="1" max="120" inputmode="numeric">
</label>

<label class="field">
Sex

<select id="rs" name="sex">
<option value="" selected disabled>Select</option>
<option>Male</option>
<option>Female</option>
<option>Other</option>
</select>

</label>

<label class="field">
Birthday
<input id="rb" name="birthday" type="date">
</label>

<label class="field">
Contact number
<input id="rc" name="contact" type="tel" inputmode="numeric" placeholder="09123456789" autocomplete="tel">
</label>

<label class="field full">
Address
<input id="rd" name="address" autocomplete="street-address" placeholder="House no., street, barangay, city">
</label>

</div>

<button class="btn-clay" type="submit">Create account</button>

</form>

<p id="msg" class="msg <?php echo $message?($isError?'err':'ok'):''; ?>" role="status" aria-live="polite" style="text-align:center;margin-top:16px">
<?php echo htmlspecialchars($message); ?>
</p>

</div>

<p class="au-note">Your SmartTech account is securely stored in the website database.</p>

</div>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="layout.js"></script>

<script>
(function(){
const $=s=>document.querySelector(s);

function mode(register){
$('#fl').hidden=register;
$('#fr').hidden=!register;
$('#tl').setAttribute('aria-selected',!register);
$('#tr').setAttribute('aria-selected',register);
$('#au').classList.toggle('wide',register);
$('#t').textContent=register?'Create your account':'Welcome back';
$('#s').textContent=register?'One account for your orders, wishlist and faster checkout.':'Log in to view your profile, orders and wishlist.';
}

$('#tl').onclick=()=>mode(false);
$('#tr').onclick=()=>mode(true);

if(location.hash==='#register')mode(true);

$('#rc').addEventListener('input',function(){
this.value=this.value.replace(/\D/g,'').slice(0,11);
});

document.querySelectorAll('.pw-eye').forEach(button=>{
button.addEventListener('click',function(){
const input=document.getElementById(this.dataset.password);
const icon=this.querySelector('i');
const show=input.type==='password';

input.type=show?'text':'password';
icon.className=show?'bi bi-eye-slash':'bi bi-eye';
this.setAttribute('aria-label',show?'Hide password':'Show password');
});
});
})();
</script>

<script src="search.js"></script>

</body>
</html>