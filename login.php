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

<link href="styles.css?v=mobile-header-search-2" rel="stylesheet">
<link href="shop.css" rel="stylesheet">
<link href="pages.css?v=mobile-gutters-2" rel="stylesheet">

<style>
.au{max-width:1040px;margin:0 auto}
.au .panel{display:grid;grid-template-columns:minmax(0, .92fr) minmax(0, 1.08fr);padding:0;overflow:hidden}
.au-story{display:flex;flex-direction:column;justify-content:space-between;min-height:540px;padding:48px;background:linear-gradient(180deg,rgba(10,31,26,.28),rgba(10,31,26,.88)),url("images/Hero.png") center/cover no-repeat;color:var(--st-cream)}
.au-brand{display:inline-flex;align-items:center;gap:10px;color:var(--st-cream);font-weight:800;text-decoration:none}
.au-brand img{width:40px;height:40px;object-fit:contain}
.au-head{max-width:400px}
.au-head h1{font-size:42px;line-height:1.1;margin:0 0 12px;color:var(--st-cream)}
.au-head .sub{margin:0;color:var(--st-sand)}
.au-story-note{color:var(--st-cream);font-size:13px;margin:28px 0 0}
.au-form{align-self:center;width:100%;max-width:520px;padding:42px 48px}
.au-form-head{margin-bottom:24px}
.au-form-head h2{font-size:26px;margin:0 0 6px;color:var(--st-ink)}
.au-form-head p{color:var(--st-ink-2);font-size:14px;margin:0}
.au .seg{display:grid;grid-template-columns:1fr 1fr}
.au .seg button{text-align:center}
.g2{display:grid;grid-template-columns:1fr 1fr;gap:0 16px}
.g2 .full{grid-column:1/-1}
.field select{display:block;width:100%;margin-top:6px;border:1px solid var(--st-sand);border-radius:12px;padding:14px;font-size:15px;font-weight:400;background:#fff}
.field select:focus{outline:2px solid var(--st-clay);border-color:var(--st-clay)}
.au .btn-clay{width:100%;margin-top:4px}
.au-note{text-align:center;font-size:12px;color:var(--st-ink-2);margin:18px 0 0}

@media(max-width:575.98px){
    .g2{grid-template-columns:1fr}
    .au .panel{grid-template-columns:1fr}
    .au-story{min-height:210px;padding:24px}
    .au-brand img{width:32px;height:32px}
    .au-head h1{font-size:30px}
    .au-head .sub{font-size:14px}
    .au-story-note{display:none}
    .au-form{padding:26px 22px}
    .au-form-head{margin-bottom:18px}
}

body.au-page{
    background:var(--st-cream);
}

.au-page .panel{box-shadow:0 24px 60px rgba(0,0,0,.16);border-color:var(--st-sand)}
@media(max-height:760px) and (min-width:576px){
    .au-page .pg{padding-top:8px;padding-bottom:24px}
    .au-story{min-height:420px;padding:28px}
    .au-form{padding:18px 28px}
    .au-form-head{margin-bottom:12px}
    .au-page .seg{margin-bottom:10px}
    .au-page .seg button{padding:8px 12px}
    .au-page .field{margin-bottom:6px}
    .au-page .field input{padding:8px 12px}
    .au-page #msg{margin:8px 0 0!important;min-height:0}
    .au-note{margin-top:10px}
}
</style>
</head>

<body data-page="login" class="au-page">

<main id="main" class="container-xxl px-lg-5 pg">
<div class="au" id="au">
<div class="panel">
<section class="au-story" aria-label="SmartTech account introduction">
<a class="au-brand" href="index.html">
<img src="images/smarttechappliances.svg" alt="">
<span>SmartTech Appliances</span>
</a>
<div class="au-head">
<span class="shop-pill">SMARTTECH ACCOUNT</span>
<h1 id="t">Welcome back</h1>
<p class="sub" id="s">Log in to view your profile, orders and wishlist.</p>
</div>
<p class="au-story-note">Smarter essentials for Filipino homes.</p>
</section>
<section class="au-form" aria-label="Log in or register">
<div class="au-form-head">
<h2 id="form-title">Sign in to your account</h2>
<p id="form-subtitle">Enter your details to continue.</p>
</div>
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
<input id="lp" name="password" type="password" autocomplete="current-password">
</label>

<button class="btn-clay" type="submit">Log in</button>
</form>

<form id="fr" action="php/register.php" method="POST" novalidate hidden>

<div class="g2">

<label class="field">
Username
<input id="ru" name="username" autocomplete="username" required>
</label>

<label class="field">
Email
<input id="re" name="email" type="email" autocomplete="email" required>
</label>

<label class="field full">
Password (at least 8 characters)
<input id="rp" name="password" type="password" minlength="8" autocomplete="new-password" required>
</label>

<label class="field full">
Confirm password
<input id="rpc" name="confirm_password" type="password" minlength="8" autocomplete="new-password" required>
</label>

<label class="field full">
Full name
<input id="rn" name="full_name" autocomplete="name" required>
</label>

</div>

<button class="btn-clay" type="submit">Create account</button>
</form>

<p id="msg" class="msg <?php echo $message?($isError?'err':'ok'):''; ?>" role="status" aria-live="polite" style="text-align:center;margin-top:16px">
<?php echo htmlspecialchars($message); ?>
</p>
<p class="au-note">Your SmartTech account is securely stored in the website database.</p>
</section>
</div>
</div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="layout.js?v=20261017"></script>

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
        $('#form-title').textContent=register?'Create your account':'Sign in to your account';
        $('#form-subtitle').textContent=register
            ?'Fill in your details to get started.'
            :'Enter your details to continue.';
        $('#s').textContent=register
            ?'One account for your orders, wishlist and faster checkout.'
            :'Log in to view your profile, orders and wishlist.';
    }

    $('#tl').onclick=()=>mode(false);
    $('#tr').onclick=()=>mode(true);

    if(location.hash==='#register')mode(true);

    $('#fr').addEventListener('submit',function(e){
        const password=$('#rp').value;
        const confirmation=$('#rpc').value;

        if(password!==confirmation){
            e.preventDefault();
            $('#msg').className='msg err';
            $('#msg').textContent='Passwords do not match.';
            $('#rpc').focus();
        }
    });
})();
</script>

<script src="search.js?v=search-inline-4"></script>
</body>
</html>