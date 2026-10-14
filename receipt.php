<?php
session_start();

if(!isset($_SESSION["user_id"])||($_SESSION["role"]??"")!=="customer"){
    header("Location: login.php");
    exit;
}

$order=$_SESSION["last_order"]??null;

if(!$order){
    header("Location: products.php");
    exit;
}

function e($value){
    return htmlspecialchars((string)$value,ENT_QUOTES,"UTF-8");
}

function peso($value){
    return "₱".number_format((float)$value,2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>Order Receipt — SmartTech Appliances</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="styles.css" rel="stylesheet">
<link href="shop.css" rel="stylesheet">
<link href="pages.css" rel="stylesheet">

<style>
.rc-wrap{max-width:780px;margin:0 auto}.rc-card{background:#fff;border:1px solid var(--st-sand);border-radius:18px;padding:28px}.rc-success{text-align:center;margin-bottom:28px}.rc-success i{display:inline-grid;place-items:center;width:64px;height:64px;border-radius:50%;background:#E4F3EB;color:#1B6B4A;font-size:32px}.rc-success h1{font-size:30px;margin:16px 0 6px}.rc-success p{color:var(--st-ink-2);margin:0}.rc-info{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px}.rc-info div{padding:14px;border:1px solid var(--st-sand);border-radius:12px}.rc-info small{display:block;color:var(--st-ink-2);margin-bottom:5px}.rc-item{display:flex;align-items:center;gap:14px;padding:14px 0;border-bottom:1px solid var(--st-sand)}.rc-item:last-child{border-bottom:0}.rc-item img{width:64px;height:64px;object-fit:contain;background:#F1F2F6;border-radius:10px;padding:5px}.rc-item div{flex:1}.rc-item small{display:block;color:var(--st-ink-2);margin-top:4px}.rc-item strong{white-space:nowrap}.rc-summary{margin-top:20px;padding-top:16px;border-top:1px solid var(--st-sand)}.rc-row{display:flex;justify-content:space-between;gap:20px;padding:5px 0}.rc-row.total{font-size:20px;font-weight:800;margin-top:6px}.rc-actions{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:24px}@media(max-width:575.98px){.rc-card{padding:20px}.rc-info{grid-template-columns:1fr}}
</style>
</head>

<body>

<main class="container-xxl px-lg-5 pg">
<div class="rc-wrap">
<div class="rc-card">

<div class="rc-success">
<i class="bi bi-check-lg"></i>
<h1>Order placed successfully!</h1>
<p>Thank you for shopping with SmartTech Appliances.</p>
</div>

<div class="rc-info">
<div>
<small>Order number</small>
<strong><?php echo e($order["orderNumber"]); ?></strong>
</div>

<div>
<small>Status</small>
<strong><?php echo e($order["status"]); ?></strong>
</div>

<div>
<small>Customer</small>
<strong><?php echo e($order["fullName"]); ?></strong>
</div>

<div>
<small>Payment method</small>
<strong><?php echo e($order["paymentMethod"]); ?></strong>
</div>

<div>
<small>Contact</small>
<strong><?php echo e($order["phone"]); ?></strong>
</div>

<div>
<small>Delivery address</small>
<strong><?php echo e($order["address"]); ?></strong>
</div>
</div>

<h2 class="fs-5 mb-2">Products ordered</h2>

<?php foreach($order["items"] as $item): ?>
<div class="rc-item">
<img src="<?php echo e($item["image"]); ?>" alt="">
<div>
<b><?php echo e($item["product_name"]); ?></b>
<small><?php echo (int)$item["quantity"]; ?> × <?php echo peso($item["price"]); ?></small>
</div>
<strong><?php echo peso($item["price"]*$item["quantity"]); ?></strong>
</div>
<?php endforeach; ?>

<div class="rc-summary">
<div class="rc-row">
<span>Subtotal</span>
<span><?php echo peso($order["subtotal"]); ?></span>
</div>

<div class="rc-row">
<span>Shipping</span>
<span><?php echo $order["shippingFee"]>0?peso($order["shippingFee"]):"Free"; ?></span>
</div>

<div class="rc-row total">
<span>Total</span>
<span><?php echo peso($order["grandTotal"]); ?></span>
</div>
</div>

<div class="rc-actions">
<a class="btn-clay text-decoration-none d-inline-grid align-items-center px-4" href="products.php">
Continue Shopping
</a>

<a class="btn-link text-decoration-none d-inline-grid align-items-center px-3" href="track.php?order=<?php echo urlencode($order["orderNumber"]); ?>">
Track Order
</a>
</div>

</div>
</div>
</main>

<script>
(function(){
const order=<?php echo json_encode($order,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;

let cart=[];

try{
    cart=JSON.parse(localStorage.getItem('st_cart'))||[];
}catch(e){}

const orderedIds=new Set((order.items||[]).map(i=>i.product_code));

cart=cart.filter(i=>!orderedIds.has(i.id));

localStorage.setItem('st_cart',JSON.stringify(cart));

const legacy={
    orderNumber:order.orderNumber,
    orderDate:new Date().toLocaleString(),
    orderTs:Date.now(),
    user:<?php echo json_encode($_SESSION["username"]??""); ?>,
    status:order.status,
    paymentMethod:order.paymentMethod,
    name:order.fullName,
    phone:order.phone,
    address:order.address,
    cartItems:(order.items||[]).map(i=>({
        id:i.product_code,
        name:i.product_name,
        price:i.price,
        quantity:i.quantity,
        img:i.image
    })),
    subtotal:order.subtotal,
    shippingFee:order.shippingFee,
    grandTotal:order.grandTotal
};

localStorage.setItem('lastOrder',JSON.stringify(legacy));

if(window.stCartCount)stCartCount();
})();
</script>

</body>
</html>