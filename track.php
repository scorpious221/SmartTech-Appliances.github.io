<?php
session_start();
require_once "php/database.php";

if(!isset($_SESSION["user_id"])||($_SESSION["role"]??"")!=="customer"){
header("Location: login.php?next=track.php");
exit;
}

$userId=(int)$_SESSION["user_id"];
$orderNumber=strtoupper(trim($_GET["order"]??""));
$order=null;
$orderItems=[];

if($orderNumber!==""){
$stmt=$conn->prepare("
SELECT id,order_number,full_name,phone,address,payment_method,
subtotal,shipping_fee,grand_total,status,created_at
FROM orders
WHERE order_number=? AND user_id=?
LIMIT 1
");
$stmt->execute([$orderNumber,$userId]);
$order=$stmt->fetch(PDO::FETCH_ASSOC)?:null;

if($order){
$stmt=$conn->prepare("
SELECT
oi.product_name,
oi.price,
oi.quantity,
p.image
FROM order_items oi
LEFT JOIN products p ON p.id=oi.product_id
WHERE oi.order_id=?
ORDER BY oi.id ASC
");
$stmt->execute([(int)$order["id"]]);
$orderItems=$stmt->fetchAll(PDO::FETCH_ASSOC);
}
}

$stmt=$conn->prepare("
SELECT order_number,status,grand_total,created_at
FROM orders
WHERE user_id=?
ORDER BY id DESC
");
$stmt->execute([$userId]);
$myOrders=$stmt->fetchAll(PDO::FETCH_ASSOC);

function e($value){return htmlspecialchars((string)$value,ENT_QUOTES,"UTF-8");}
function peso($value){return "₱".number_format((float)$value,2);}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Track my order — SmartTech Appliances</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<link href="styles.css" rel="stylesheet">
<link href="shop.css" rel="stylesheet">
<link href="pages.css" rel="stylesheet">

<style>
.tr-wrap{width:100%;max-width:760px;margin:0 auto}
.tr-orders{display:grid;gap:10px;margin-top:20px}
.tr-order{display:flex;justify-content:space-between;align-items:center;gap:16px;padding:14px 16px;border:1px solid var(--st-sand);border-radius:12px;background:#fff;text-decoration:none;color:var(--st-ink)}
.tr-order:hover{border-color:var(--st-clay)}
.tr-order small{display:block;color:var(--st-ink-2);margin-top:4px}
.tr-status{font-weight:700;color:var(--st-clay);white-space:nowrap}
.tr-top{display:flex;justify-content:space-between;gap:16px;align-items:flex-start}
.tr-top small{display:block;color:var(--st-ink-2);margin-top:5px}

.tr-items{margin-top:22px;padding-top:20px;border-top:1px solid var(--st-sand)}
.tr-items h3{font-size:16px;font-weight:800;margin:0 0 14px}
.tr-item-list{display:grid;gap:10px}
.tr-item{display:grid;grid-template-columns:76px minmax(0,1fr) auto;align-items:center;gap:14px;padding:12px;border:1px solid var(--st-sand);border-radius:14px;background:var(--st-cream)}
.tr-item-img{width:76px;height:76px;border-radius:10px;background:#fff;display:grid;place-items:center;overflow:hidden}
.tr-item-img img{width:100%;height:100%;object-fit:contain;padding:6px}
.tr-item-info{min-width:0}
.tr-item-info b{display:block;font-size:14px;margin-bottom:5px}
.tr-item-info small{display:block;color:var(--st-ink-2);font-size:12px}
.tr-item-total{text-align:right;font-weight:800;font-size:14px;white-space:nowrap}

.tr-summary{margin-top:14px;padding:14px;border:1px solid var(--st-sand);border-radius:12px;background:#fff}
.tr-summary-row{display:flex;justify-content:space-between;gap:16px;padding:4px 0;font-size:13px}
.tr-summary-row.total{font-size:15px;font-weight:800;border-top:1px solid var(--st-sand);margin-top:6px;padding-top:10px}

@media(max-width:575.98px){
.tr-wrap{max-width:100%}
.tr-order,.tr-top{align-items:flex-start;flex-direction:column}
.tr-item{grid-template-columns:64px minmax(0,1fr)}
.tr-item-img{width:64px;height:64px}
.tr-item-total{grid-column:2;text-align:left}
}
</style>
</head>

<body data-page="track">

<main id="main" class="container-xxl px-lg-5 pg">

<div class="tr-wrap">

<h1>Track my order</h1>
<p class="sub">Enter one of your SmartTech order numbers to check its latest status.</p>

<div class="panel">

<form method="GET" action="track.php">

<label class="field">
Order number
<input
name="order"
value="<?php echo e($orderNumber); ?>"
placeholder="ORD-20261014-ABC123"
autocomplete="off"
>
</label>

<?php if($orderNumber!==""&&!$order): ?>
<p class="msg err">We couldn't find this order under your account.</p>
<?php endif; ?>

<button class="btn-clay" type="submit">Track order</button>

</form>

</div>

<?php if($order): ?>

<?php
$steps=[
["Pending","Order placed","We have received your order."],
["Processing","Processing","Your items are being prepared."],
["Shipped","Out for delivery","Your order is on its way."],
["Delivered","Delivered","Your order has been delivered."]
];

$status=$order["status"]??"Pending";
$current=0;

foreach($steps as $index=>$step){
if($step[0]===$status){
$current=$index;
break;
}
}

$isCancelled=$status==="Cancelled";
?>

<div class="panel mt-4">

<div class="tr-top">

<div>
<b><?php echo e($order["order_number"]); ?></b>
<small><?php echo e($order["payment_method"]); ?> · <?php echo peso($order["grand_total"]); ?></small>
</div>

<span class="tr-status"><?php echo e($status); ?></span>

</div>

<?php if(count($orderItems)): ?>

<div class="tr-items">

<h3>Items in this order</h3>

<div class="tr-item-list">

<?php foreach($orderItems as $item): ?>

<?php
$image=trim((string)($item["image"]??""));
if($image==="")$image="images/smarttechappliances.svg";
$itemTotal=(float)$item["price"]*(int)$item["quantity"];
?>

<div class="tr-item">

<div class="tr-item-img">
<img src="<?php echo e($image); ?>" alt="<?php echo e($item["product_name"]); ?>" onerror="this.src='images/smarttechappliances.svg'">
</div>

<div class="tr-item-info">
<b><?php echo e($item["product_name"]); ?></b>
<small><?php echo peso($item["price"]); ?> × <?php echo (int)$item["quantity"]; ?></small>
</div>

<div class="tr-item-total">
<?php echo peso($itemTotal); ?>
</div>

</div>

<?php endforeach; ?>

</div>

<div class="tr-summary">

<div class="tr-summary-row">
<span>Subtotal</span>
<b><?php echo peso($order["subtotal"]); ?></b>
</div>

<div class="tr-summary-row">
<span>Shipping</span>
<b><?php echo (float)$order["shipping_fee"]<=0?"Free":peso($order["shipping_fee"]); ?></b>
</div>

<div class="tr-summary-row total">
<span>Total</span>
<span><?php echo peso($order["grand_total"]); ?></span>
</div>

</div>

</div>

<?php endif; ?>

<?php if($isCancelled): ?>

<p class="msg err mt-4 mb-0">This order has been cancelled.</p>

<?php else: ?>

<ol class="steps mt-4">

<?php foreach($steps as $index=>$step): ?>

<li class="<?php echo $index<=$current?"done":""; ?>">
<b><?php echo e($step[1]); ?></b>
<?php echo e($step[2]); ?>
</li>

<?php endforeach; ?>

</ol>

<?php endif; ?>

<small style="color:var(--st-ink-2)">
Delivering to: <?php echo e($order["address"]); ?>
</small>

</div>

<?php endif; ?>

<?php if(count($myOrders)): ?>

<h2 class="fs-5 mt-4">My orders</h2>

<div class="tr-orders">

<?php foreach($myOrders as $item): ?>

<a class="tr-order" href="track.php?order=<?php echo urlencode($item["order_number"]); ?>">

<div>
<b><?php echo e($item["order_number"]); ?></b>
<small><?php echo e($item["created_at"]); ?> · <?php echo peso($item["grand_total"]); ?></small>
</div>

<span class="tr-status"><?php echo e($item["status"]); ?></span>

</a>

<?php endforeach; ?>

</div>

<?php else: ?>

<div class="panel mt-4 text-center">
<p class="mb-3">You don't have any orders yet.</p>
<a class="btn-clay d-inline-grid align-items-center text-decoration-none px-4" href="products.php">Shop now</a>
</div>

<?php endif; ?>

</div>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="layout.js"></script>
<script src="search.js"></script>

</body>
</html>