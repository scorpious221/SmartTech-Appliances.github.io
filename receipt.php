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
<title>Order Confirmation — SmartTech Appliances</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="styles.css" rel="stylesheet">
<link href="shop.css" rel="stylesheet">
<link href="pages.css?v=mobile-gutters-2" rel="stylesheet">
<style>
.oc-wrap{max-width:640px;margin:0 auto}.oc-card{padding:22px 24px;border:1px solid var(--st-sand);border-radius:16px;background:#fff}.oc-success{text-align:center;margin-bottom:17px}.oc-success i{display:inline-grid;width:44px;height:44px;place-items:center;border-radius:50%;background:#e4f3eb;color:#1b6b4a;font-size:23px}.oc-success h1{margin:10px 0 4px;font-size:23px}.oc-success p{margin:0;color:var(--st-ink-2);font-size:13px}.oc-order{margin:-3px 0 15px;text-align:center;color:var(--st-ink-2);font-size:12px}.oc-title{margin:0 0 2px;font-size:15px;font-weight:800}.oc-item{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--st-sand)}.oc-item:last-child{border-bottom:0}.oc-item img{width:52px;height:52px;flex:none;object-fit:contain;border-radius:9px;background:#f1f2f6;padding:4px}.oc-item-copy{flex:1;min-width:0}.oc-item-copy b{display:block;font-size:14px}.oc-item-copy small{display:block;margin-top:3px;color:var(--st-ink-2);font-size:12px}.oc-item>strong{white-space:nowrap;font-size:14px}.oc-summary{margin-top:5px;padding-top:10px;border-top:1px solid var(--st-sand);font-size:14px}.oc-row{display:flex;justify-content:space-between;gap:16px;padding:4px 0}.oc-row span:last-child{text-align:right;white-space:nowrap}.oc-row.total{margin-top:4px;padding-top:9px;border-top:1px solid var(--st-sand);font-size:17px;font-weight:800}.oc-actions{display:flex;justify-content:center;align-items:center;gap:8px;flex-wrap:wrap;margin-top:17px}.oc-view{display:inline-flex;min-height:40px;align-items:center;justify-content:center;gap:7px;padding:0 16px;border:0;border-radius:999px;background:var(--st-ink);color:#fff;font-size:13px;font-weight:800;text-decoration:none}.oc-view:hover{background:var(--st-clay);color:#fff}.oc-actions .btn-link,.oc-actions .btn-clay{min-height:40px;font-size:13px}.oc-actions .btn-clay{padding:0 16px!important}.oc-pending-note{display:flex;align-items:flex-start;gap:9px;margin:0 0 12px;padding:10px 12px;border:1px solid #f0d8ac;border-radius:10px;background:#fff8e9;color:#805600;font-size:12px;line-height:1.45}.oc-pending-note i{margin-top:1px}.oc-wait-screen{position:fixed;inset:0;z-index:10000;display:grid;place-items:center;padding:20px;background:rgba(12,35,30,.94);color:#fff;text-align:center}.oc-wait-screen[hidden]{display:none!important}.oc-wait-card{max-width:400px}.oc-wait-icon{display:grid;width:58px;height:58px;place-items:center;margin:0 auto 16px;border:2px solid rgba(255,255,255,.45);border-radius:50%;font-size:26px}.oc-wait-icon i{animation:oc-pulse 1s ease-in-out infinite alternate}.oc-wait-screen h2{margin:0 0 8px;font-size:22px}.oc-wait-screen p{margin:0;color:rgba(255,255,255,.82);font-size:14px;line-height:1.5}.oc-wait-screen small{display:block;margin-top:12px;color:rgba(255,255,255,.68);font-size:11px}@keyframes oc-pulse{to{transform:scale(1.12);opacity:.65}}@media(max-width:575.98px){.oc-card{padding:18px 16px}.oc-item{gap:9px}.oc-item img{width:46px;height:46px}.oc-item>strong{font-size:13px}.oc-actions{gap:6px}.oc-actions>*{flex:0 1 auto}.oc-actions .btn-link{padding-left:8px!important;padding-right:8px!important}.oc-actions .btn-clay{padding:0 12px!important}}
@media screen and (max-height:900px){.oc-wrap{max-width:580px}.oc-card{padding:18px 20px}.oc-success{margin-bottom:12px}.oc-success i{width:40px;height:40px;font-size:21px}.oc-success h1{margin-top:8px;font-size:21px}.oc-order{margin-bottom:10px}.oc-item{gap:10px;padding:7px 0}.oc-item img{width:44px;height:44px}.oc-summary{margin-top:3px;padding-top:7px}.oc-row{padding:3px 0}.oc-row.total{padding-top:6px}.oc-actions{gap:6px;margin-top:12px}.oc-view,.oc-actions .btn-link,.oc-actions .btn-clay{min-height:36px;font-size:12px}.oc-view{padding:0 12px}.oc-actions .btn-clay{padding:0 12px!important}}
@media screen and (max-height:900px){.oc-actions .btn-link,.oc-actions .btn-clay{height:36px!important;min-height:36px!important}}
@media screen and (max-height:650px){.oc-card{transform:scale(.9);transform-origin:top center;margin-bottom:-10%}}
</style>
</head>
<body>
<?php if(($order["status"]??"Pending")==="Pending"): ?>
<div class="oc-wait-screen" id="sellerWaitScreen" role="status" aria-live="polite" hidden>
<div class="oc-wait-card">
<span class="oc-wait-icon"><i class="bi bi-hourglass-split" aria-hidden="true"></i></span>
<h2>Waiting for seller confirmation</h2>
<p>Your order has been received, but it is not confirmed yet. The seller will review and process it shortly.</p>
<small>Showing your order summary…</small>
</div>
</div>
<?php endif; ?>
<main class="container-xxl px-lg-5 pg">
<div class="oc-wrap">
<section class="oc-card">
<header class="oc-success">
<i class="bi bi-check-lg" aria-hidden="true"></i>
<h1>Order received</h1>
<p>Thank you for shopping with SmartTech Appliances.</p>
</header>
<p class="oc-order">Order <strong><?php echo e($order["orderNumber"]); ?></strong></p>
<?php if(($order["status"]??"Pending")==="Pending"): ?>
<p class="oc-pending-note"><i class="bi bi-hourglass-split" aria-hidden="true"></i><span><strong>Waiting for seller confirmation.</strong> Your order is not confirmed until the seller starts processing it.</span></p>
<?php elseif(($order["status"]??"")==="Processing"): ?>
<p class="oc-pending-note"><i class="bi bi-check-circle" aria-hidden="true"></i><span><strong>Order confirmed.</strong> The seller is preparing your items.</span></p>
<?php endif; ?>
<h2 class="oc-title">Products ordered</h2>
<?php foreach($order["items"] as $item): ?>
<div class="oc-item">
<img src="<?php echo e($item["image"]); ?>" alt="" onerror="this.style.visibility='hidden'">
<div class="oc-item-copy">
<b><?php echo e($item["product_name"]); ?></b>
<small><?php echo (int)$item["quantity"]; ?> × <?php echo peso($item["price"]); ?></small>
</div>
<strong><?php echo peso($item["price"]*$item["quantity"]); ?></strong>
</div>
<?php endforeach; ?>
<div class="oc-summary">
<div class="oc-row"><span>Subtotal</span><span><?php echo peso($order["subtotal"]); ?></span></div>
<div class="oc-row"><span>Shipping</span><span><?php echo (float)$order["shippingFee"]>0?peso($order["shippingFee"]):"Free"; ?></span></div>
<?php if((float)($order["discountAmount"]??0)>0): ?>
<div class="oc-row"><span>Voucher<?php echo !empty($order["voucherCode"])?" (".e($order["voucherCode"]).")":""; ?></span><span>−<?php echo peso($order["discountAmount"]); ?></span></div>
<?php endif; ?>
<div class="oc-row total"><span>Total</span><span><?php echo peso($order["grandTotal"]); ?></span></div>
</div>
<nav class="oc-actions" aria-label="Order actions">
<a class="oc-view" href="receipt-print.php"><i class="bi bi-receipt" aria-hidden="true"></i>View Receipt</a>
<a class="btn-link text-decoration-none d-inline-grid align-items-center px-3" href="track.php?order=<?php echo urlencode($order["orderNumber"]); ?>">Track Order</a>
<a class="btn-clay text-decoration-none d-inline-grid align-items-center px-4" href="products.php">Continue Shopping</a>
</nav>
</section>
</div>
</main>
<script>
(function(){
const order=<?php echo json_encode($order,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
const waitScreen=document.getElementById('sellerWaitScreen');
if(waitScreen){
    const shownKey='st_pending_notice_'+order.orderNumber;
    let alreadyShown=false;
    try{
        alreadyShown=sessionStorage.getItem(shownKey)==='1';
        sessionStorage.setItem(shownKey,'1');
    }catch(error){
        console.error('Unable to save order confirmation notice state.',error);
    }
    if(alreadyShown){
        waitScreen.hidden=true;
    }else{
        waitScreen.hidden=false;
        window.setTimeout(()=>{waitScreen.hidden=true},3000);
    }
}
sessionStorage.removeItem('st_bundle_purchase');
let cart=[];
try{cart=JSON.parse(localStorage.getItem('st_cart'))||[]}catch(error){console.error('Unable to read cart after checkout.',error)}
const orderedIds=new Set((order.items||[]).map(item=>item.product_code));
cart=cart.filter(item=>!orderedIds.has(item.id));
try{localStorage.setItem('st_cart',JSON.stringify(cart))}catch(error){console.error('Unable to update cart after checkout.',error)}
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
    cartItems:(order.items||[]).map(item=>({id:item.product_code,name:item.product_name,price:item.price,quantity:item.quantity,img:item.image})),
    subtotal:order.subtotal,
    shippingFee:order.shippingFee,
    grandTotal:order.grandTotal
};
try{localStorage.setItem('lastOrder',JSON.stringify(legacy))}catch(error){console.error('Unable to save the legacy order summary.',error)}
if(window.stCartCount)stCartCount();
})();
</script>
<script src="back-to-top.js"></script>
</body>
</html>
