<?php
session_start();
require_once "php/database.php";
$checkoutError=$_SESSION["checkout_error"]??"";
unset($_SESSION["checkout_error"]);

$stmt=$conn->query("SELECT product_code,name,COALESCE(sale_price,price) AS price,image,stock FROM products ORDER BY id ASC");
$dbProducts=[];

while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
    $dbProducts[]=[
        "id"=>$row["product_code"],
        "name"=>$row["name"],
        "price"=>(float)$row["price"],
        "img"=>$row["image"],
        "stock"=>(int)$row["stock"]
    ];
}
$dbBundles=$conn->query("SELECT id,title,product_codes,price,compare_at_price FROM home_bundles WHERE active=1 ORDER BY sort_order,id")->fetchAll(PDO::FETCH_ASSOC);
foreach($dbBundles as &$bundle){
    $bundle["product_codes"]=json_decode((string)$bundle["product_codes"],true)?:[];
    $bundle["price"]=(float)$bundle["price"];
    $bundle["compare_at_price"]=(float)$bundle["compare_at_price"];
}
unset($bundle);

$loggedIn=isset($_SESSION["user_id"])&&($_SESSION["role"]??"")==="customer";
$user=[];
$savedAddresses=[];

if($loggedIn){
    $stmt=$conn->prepare("SELECT username,full_name,contact,address FROM users WHERE id=? LIMIT 1");
    $stmt->execute([$_SESSION["user_id"]]);
    $user=$stmt->fetch(PDO::FETCH_ASSOC)?:[];
    $stmt=$conn->prepare("SELECT id,label,recipient_name,phone,address_line,barangay,city,province,postal_code,is_default FROM user_addresses WHERE user_id=? ORDER BY is_default DESC,id DESC");
    $stmt->execute([$_SESSION["user_id"]]);
    $savedAddresses=$stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>Cart & Checkout — SmartTech Appliances</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<link href="styles.css?v=mobile-header-search-2" rel="stylesheet">
<link href="shop.css" rel="stylesheet">
<link href="pages.css?v=mobile-gutters-2" rel="stylesheet">

<style>
.ct-card{background:#fff;border:1px solid var(--st-sand);border-radius:16px}
.ct-head,.ct-row{display:grid;grid-template-columns:28px minmax(0,2.6fr) 1fr 140px 1fr 90px;gap:16px;align-items:center;padding:16px 20px;margin-bottom:12px}
.ct-head{font-size:13px;font-weight:700;color:var(--st-ink-2)}
.ct-row input[type=checkbox],.ct-head input[type=checkbox],.ct-bar input[type=checkbox]{width:20px;height:20px;accent-color:var(--st-clay)}
.ct-prod{display:flex;gap:14px;align-items:center;min-width:0}
.ct-prod img{width:80px;height:80px;object-fit:contain;background:#F1F2F6;border-radius:10px;padding:6px;flex:none}
.ct-prod b{font-size:15px;line-height:1.35}
.ct-prod small{display:block;margin-top:5px;color:var(--st-ink-2);font-size:11px}
.ct-total{font-weight:800;color:var(--st-clay)}
.ct-del{background:none;border:0;color:#A3321A;font-weight:600;min-height:44px;text-align:left}
.ct-bar{position:sticky;bottom:0;z-index:20;display:flex;align-items:center;gap:16px;flex-wrap:wrap;padding:14px 20px;margin-top:16px;box-shadow:0 -8px 24px rgba(15,42,36,.08)}
.ct-bar label{display:flex;align-items:center;gap:10px;font-weight:600;margin:0}
.ct-grow{flex:1}
.ct-sum{text-align:right;font-size:14px;color:var(--st-ink-2)}
.ct-sum strong{display:block;font-size:22px;color:var(--st-ink);letter-spacing:-.02em}
.ct-ship{font-size:13px;color:var(--st-ink-2);margin:0 0 16px}
.ct-ship b{color:#1B6B4A}
.ct-empty{text-align:center;padding:64px 24px}
.ct-empty i{font-size:40px;color:var(--st-clay)}
.ct-empty h2{font-size:28px;margin:16px 0 8px}
.ct-empty p{color:var(--st-ink-2);margin-bottom:24px}
.ck{display:grid;grid-template-columns:1fr 360px;gap:24px;align-items:start}
.ck .panel{margin-bottom:16px;padding:24px}
.ck h2{font-size:20px;margin:0 0 16px}
.ck-item{display:flex;gap:12px;align-items:center;padding:10px 0;border-bottom:1px solid var(--st-sand)}
.ck-item:last-child{border:0}
.ck-item img{width:56px;height:56px;object-fit:contain;background:#F1F2F6;border-radius:8px;padding:4px}
.ck-item div{flex:1;min-width:0;font-size:14px}
.ck-item small{display:block;color:var(--st-ink-2)}
.ck-item span{font-weight:700}
.ck-item-main{min-width:0}
.ck-qty{display:flex;align-items:center;gap:8px;margin-top:7px}
.ck-qty button{width:30px;height:30px;border:1px solid var(--st-sand);border-radius:8px;background:#fff;color:var(--st-ink);font-weight:700}
.ck-qty button:disabled{opacity:.45;cursor:not-allowed}
.ck-qty-input{width:58px;height:32px;border:1px solid var(--st-sand);border-radius:8px;background:#fff;color:var(--st-ink);font-size:13px;font-weight:700;text-align:center}
.ck-qty-input:focus{border-color:var(--st-clay);outline:2px solid rgba(163,50,26,.14)}
.ck-side{position:sticky;top:110px}
.ck-card input[type=text]{width:100%;border:1px solid var(--st-sand);border-radius:12px;padding:14px;font-size:15px;margin-bottom:12px}
#ckCard{display:none;margin-top:12px}
.voucher-form{display:flex;gap:8px;margin:12px 0}
.voucher-form input{min-width:0;flex:1;border:1px solid var(--st-sand);border-radius:12px;padding:11px 12px;text-transform:uppercase}
.voucher-form button{min-height:44px;padding:0 14px;border:0;border-radius:12px;background:var(--st-ink);color:#fff;font-weight:700}
.voucher-message{min-height:20px;margin:4px 0 12px;font-size:13px}
.voucher-message.error{color:#A3321A}.voucher-message.success{color:#1B6B4A}
#voucherDiscountRow[hidden]{display:none}
.checkout-vouchers{margin-top:18px;padding-top:16px;border-top:1px solid var(--st-sand)}
.checkout-vouchers h3{font-size:15px;font-weight:800;margin:0 0 4px}
.checkout-vouchers-intro{font-size:12px;color:var(--st-ink-2);margin:0 0 10px}
.checkout-voucher-list{display:grid;gap:8px;max-height:300px;overflow-y:auto}
.checkout-voucher{display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:center;gap:10px;padding:11px;border:1px solid var(--st-sand);border-radius:12px;background:#fff}
.checkout-voucher-copy{min-width:0}
.checkout-voucher-copy b{display:block;font-size:13px}
.checkout-voucher-copy small{display:block;color:var(--st-ink-2);font-size:11px;line-height:1.4;margin-top:3px}
.checkout-voucher-code{display:inline-block;margin-top:5px;padding:3px 7px;border-radius:5px;background:var(--st-cream);font-size:10px;font-weight:800;letter-spacing:.05em}
.checkout-voucher button{min-height:36px;padding:0 10px;border:0;border-radius:9px;background:var(--st-clay);color:#fff;font-size:12px;font-weight:700;white-space:nowrap}
.checkout-voucher button:disabled{background:#e7e5e1;color:#8c8c8c;cursor:not-allowed}
.checkout-voucher button.remove{border:1px solid var(--st-sand);background:#fff;color:var(--st-ink)}
.checkout-voucher.unavailable{background:#f5f4f1;border-color:#e7e5e1;color:#777}
.checkout-voucher.unavailable .checkout-voucher-copy small{color:#8a8a8a}
.checkout-voucher-status{font-size:11px;color:#8a8a8a;margin:8px 0 0}

@media(max-width:991.98px){
    .ct-head{display:none}
    .ct-row{grid-template-columns:24px 1fr;gap:10px 14px;padding:14px}
    .ct-row>*:nth-child(n+3){grid-column:2}
    .ct-unit{display:none}
    .ct-bar{bottom:76px;padding:12px 14px;gap:10px}
    .ct-bar .btn-clay{flex:1 1 100%}
    .ct-sum{text-align:left;flex:1}
    .ck{grid-template-columns:1fr}
    .ck-side{position:static}
}
</style>
</head>

<body data-page="cart">

<main id="main" class="container-xxl px-lg-5 pg">
<h1 id="ttl">Shopping cart</h1>
<?php if($checkoutError!==""): ?>
<p class="msg err" role="alert"><?php echo htmlspecialchars($checkoutError,ENT_QUOTES,"UTF-8"); ?></p>
<?php endif; ?>
<section id="vCart"></section>
<section id="vCheck" hidden></section>
</main>

<form id="checkoutForm" action="php/checkout.php" method="POST" hidden>
<input type="hidden" name="full_name" id="postName">
<input type="hidden" name="phone" id="postPhone">
<input type="hidden" name="address" id="postAddress">
<input type="hidden" name="payment_method" id="postPayment">
<input type="hidden" name="items" id="postItems">
<input type="hidden" name="voucher_code" id="postVoucher">
<input type="hidden" name="bundle_id" id="postBundleId">
</form>

<script>
window.DB_PRODUCTS=<?php echo json_encode($dbProducts,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
window.DB_BUNDLES=<?php echo json_encode($dbBundles,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
window.LOGGED_IN=<?php echo $loggedIn?'true':'false'; ?>;
window.DB_USER=<?php echo json_encode($user,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
window.DB_ADDRESSES=<?php echo json_encode($savedAddresses,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="layout.js?v=20261017"></script>

<script>
(function(){
const $=s=>document.querySelector(s);
const esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const peso=n=>'₱'+Number(n||0).toLocaleString('en-PH');
const FREE=3000,SHIP=150,MAX_ITEM_QUANTITY=100;
const products=Array.isArray(window.DB_PRODUCTS)?window.DB_PRODUCTS:[];
const bundles=Array.isArray(window.DB_BUNDLES)?window.DB_BUNDLES:[];
const addressBook=Array.isArray(window.DB_ADDRESSES)?window.DB_ADDRESSES:[];
const read=(k,f)=>{try{const v=JSON.parse(localStorage.getItem(k));return v==null?f:v}catch(e){return f}};
const find=id=>products.find(p=>p.id===id)||null;

let cart=read('st_cart',[]);
let voucherCode='';
let voucherDiscount=0;
let voucherMessage='';
let checkoutVouchers=[];
let bundlePurchase=null;

try{
    bundlePurchase=JSON.parse(sessionStorage.getItem('st_bundle_purchase')||'null');
}catch(e){}

cart=cart.map(i=>{
    const p=find(i.id);
    if(!p||p.stock<=0)return null;

    return{
        id:p.id,
        name:p.name,
        price:p.price,
        img:p.img,
        qty:Math.min(MAX_ITEM_QUANTITY,p.stock,Math.max(1,Number(i.qty||1)))
    };
}).filter(Boolean);

let activeBundle=bundlePurchase?bundles.find(bundle=>Number(bundle.id)===Number(bundlePurchase.id))||null:null;
if(bundlePurchase&&!activeBundle){
    sessionStorage.removeItem('st_bundle_purchase');
    bundlePurchase=null;
}
let sel=new Set(activeBundle?activeBundle.product_codes:cart.map(i=>i.id));
let buy=null;

try{
    buy=sessionStorage.getItem('st_buy');
    sessionStorage.removeItem('st_buy');
}catch(e){}

if(buy&&!activeBundle)sel=new Set([buy]);

const save=()=>{
    try{
        localStorage.setItem('st_cart',JSON.stringify(cart));
    }catch(e){}

    if(window.stCartCount)stCartCount();
};

const chosen=()=>cart.filter(i=>sel.has(i.id));
const sub=list=>list.reduce((t,i)=>t+i.price*i.qty,0);
const count=list=>list.reduce((t,i)=>t+i.qty,0);
const shipFor=s=>s>=FREE?0:SHIP;

save();

function bundleTotals(list){
    const regularSubtotal=sub(list);
    if(!activeBundle)return{valid:true,subtotal:regularSubtotal,savings:0,quantity:0,unitPrice:0};
    const codes=activeBundle.product_codes;
    const items=codes.map(code=>list.find(item=>item.id===code));
    if(items.some(item=>!item))return{valid:false,reason:'Keep every product in the bundle selected.'};
    const quantity=Math.min(...items.map(item=>item.qty));
    if(quantity<1)return{valid:false,reason:'The bundle needs at least one of each included product.'};
    const regularUnitPrice=items.reduce((total,item)=>total+item.price,0);
    const unitPrice=Math.min(Number(activeBundle.price),regularUnitPrice);
    const savings=Math.max(0,(regularUnitPrice-unitPrice)*quantity);
    return{valid:true,subtotal:regularSubtotal-savings,savings,quantity,unitPrice};
}

function canFulfill(list){
    if(!list.length)return{ok:false,reason:'Select at least one item.'};

    for(const item of list){
        const p=find(item.id);

        if(!p){
            return{ok:false,reason:item.name+' is no longer available.'};
        }

        if(p.stock<=0){
            return{ok:false,reason:item.name+' is out of stock.'};
        }

        if(item.qty>MAX_ITEM_QUANTITY){
            return{ok:false,reason:'You can checkout up to '+MAX_ITEM_QUANTITY+' units per product.'};
        }

        if(item.qty>p.stock){
            return{
                ok:false,
                reason:'Only '+p.stock+' stock available for '+p.name+'.'
            };
        }
    }

    return{ok:true};
}

function renderCheckoutVouchers(){
    const container=$('#checkoutVoucherList');
    if(!container)return;
    if(!checkoutVouchers.length){
        container.innerHTML='<p class="checkout-voucher-status">No vouchers are currently available.</p>';
        return;
    }
    container.innerHTML=checkoutVouchers.map(voucher=>{
        const applied=voucher.valid&&voucher.code===voucherCode;
        const offer=voucher.type==='percent'
            ?`${Number(voucher.value)}% off${Number(voucher.cap)>0?` up to ${peso(voucher.cap)}`:''}`
            :`${peso(voucher.value)} off`;
        const ends=voucher.ends_at?` · Ends ${esc(String(voucher.ends_at).slice(0,10))}`:'';
        const status=voucher.valid
            ?`Save ${peso(voucher.discount)} with this order${ends}`
            :`${esc(voucher.message||'Not applicable to this order')}${ends}`;
        return`<article class="checkout-voucher${voucher.valid?'':' unavailable'}">
            <div class="checkout-voucher-copy">
                <b>${esc(voucher.label)} · ${esc(offer)}</b>
                ${voucher.terms?`<small>${esc(voucher.terms)}</small>`:''}
                <span class="checkout-voucher-code">${esc(voucher.code)}</span>
                <small>${status}</small>
            </div>
            <button type="button" class="${applied?'remove':''}" ${applied?'data-remove-voucher':'data-voucher-code="'+esc(voucher.code)+'"'} ${voucher.valid?'':'disabled'}>${applied?'Remove voucher':voucher.valid?'Use voucher':'Unavailable'}</button>
        </article>`;
    }).join('');
}

async function loadCheckoutVouchers(){
    const container=$('#checkoutVoucherList');
    if(!container)return;
    container.innerHTML='<p class="checkout-voucher-status">Checking vouchers for this order…</p>';
    try{
        const response=await fetch('php/apply_voucher.php',{
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body:JSON.stringify({
                action:'list',
                bundle_purchase:Boolean(activeBundle),
                items:chosen().map(item=>({id:item.id,quantity:item.qty}))
            })
        });
        const result=await response.json();
        if(!response.ok||!Array.isArray(result.vouchers)){
            throw new Error(result.message||'Unable to load vouchers right now.');
        }
        checkoutVouchers=result.vouchers;
        renderCheckoutVouchers();
    }catch(error){
        container.innerHTML=`<p class="checkout-voucher-status">${esc(error.message||'Unable to load vouchers right now. Please try again.')}</p>`;
    }
}

function drawCart(){
    const v=$('#vCart');

    if(!cart.length){
        v.innerHTML=`
        <div class="ct-card ct-empty">
            <i class="bi bi-bag"></i>
            <h2>Your cart is empty</h2>
            <p>Add a product to check out.</p>
            <a class="btn-clay d-inline-grid align-items-center text-decoration-none" style="padding:0 28px" href="products.php">Go shopping</a>
        </div>`;
        return;
    }

    const ch=chosen();
    const totals=bundleTotals(ch);
    const s=totals.subtotal;
    const all=ch.length===cart.length&&cart.length>0;
    const need=FREE-s;

    v.innerHTML=`
    ${activeBundle?`
    <div class="ct-card p-3 mb-3">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div><b>${esc(activeBundle.title)} bundle</b><p class="mb-0 mt-1 text-secondary">All included products are selected together. Bundle price: ${peso(totals.unitPrice)} per set${totals.quantity>1?` · ${totals.quantity} sets`:''}${totals.savings>0?` · You save ${peso(totals.savings)}`:''}</p></div>
            <button type="button" class="btn-link" data-act="exit-bundle">Exit bundle checkout</button>
        </div>
        ${totals.valid?'':`<p class="msg err mb-0 mt-2">${esc(totals.reason)}</p>`}
    </div>`:''}
    <div class="ct-card ct-head">
        <input type="checkbox" class="ck-all" ${all?'checked':''} ${activeBundle?'disabled':''}>
        <span>Product</span>
        <span class="ct-unit">Unit price</span>
        <span>Quantity</span>
        <span>Total</span>
        <span>Action</span>
    </div>

    ${cart.map(i=>{
        const p=find(i.id);
        const stock=p?p.stock:0;
        const maxQty=Math.min(MAX_ITEM_QUANTITY,stock);

        return`
        <div class="ct-card ct-row" data-id="${esc(i.id)}">
            <input type="checkbox" class="ck-i" ${sel.has(i.id)?'checked':''} ${activeBundle?'disabled':''}>

            <div class="ct-prod">
                <img src="${esc(i.img)}" alt="" onerror="this.style.visibility='hidden'">
                <div>
                    <b>${esc(i.name)}</b>
                    <small>${stock} stock available</small>
                </div>
            </div>

            <span class="ct-unit">${peso(i.price)}</span>

            <div class="qty-controls" style="width:max-content">
                <button type="button" data-act="minus">−</button>
                <span style="min-width:28px;text-align:center">${i.qty}</span>
                <button type="button" data-act="plus" ${i.qty>=maxQty?'disabled':''}>+</button>
            </div>

            <span class="ct-total">${peso(i.price*i.qty)}</span>
            <button type="button" class="ct-del" data-act="del">Delete</button>
        </div>`;
    }).join('')}

    <div class="ct-card ct-bar">
        <label>
            <input type="checkbox" class="ck-all" ${all?'checked':''} ${activeBundle?'disabled':''}>
            ${activeBundle?`${count(ch)} bundle items`:`Select all (${cart.length})`}
        </label>

        ${activeBundle?'':`<button type="button" class="btn-link" data-act="delsel">Delete selected</button>`}

        <span class="ct-grow"></span>

        <div class="ct-sum">
            Total (${count(ch)} ${count(ch)===1?'item':'items'})
            <strong>${peso(s)}</strong>
        </div>

        <button type="button" class="btn-clay" data-act="go" style="padding:0 32px" ${totals.valid?'':'disabled'}>Checkout</button>
    </div>

    <p class="ct-ship" style="margin-top:12px">
        ${!ch.length
        ?'Select an item to check out.'
        :need>0
        ?`Add <b>${peso(need)}</b> more for free delivery.`
        :'<b>Free delivery</b> on this order.'}
    </p>`;
}

function drawCheck(){
    if(!window.LOGGED_IN){
        location.href='login.php?next=cart.php';
        return;
    }

    const ch=chosen();
    const check=canFulfill(ch);

    if(!check.ok){
        alert(check.reason);
        drawCart();
        return;
    }

    const totals=bundleTotals(ch);
    if(!totals.valid){
        alert(totals.reason);
        drawCart();
        return;
    }
    const s=totals.subtotal;
    const sh=shipFor(s);
    const u=window.DB_USER||{};
    const defaultAddress=addressBook.find(a=>Number(a.is_default)===1)||addressBook[0]||null;
    const initialAddress=defaultAddress
        ?`${defaultAddress.address_line}, Barangay ${defaultAddress.barangay}, ${defaultAddress.city}, ${defaultAddress.province} ${defaultAddress.postal_code}`
        :(u.address||'');

    $('#ttl').textContent='Checkout';
    $('#vCart').hidden=true;
    $('#vCheck').hidden=false;

    $('#vCheck').innerHTML=`
    <p style="margin:-8px 0 20px">
        <button type="button" class="btn-link" id="back" style="padding:0">
            <i class="bi bi-chevron-left"></i> Back to cart
        </button>
    </p>

    <div class="ck">

        <div>

            <div class="panel">
                <h2>Delivery address</h2>

                ${addressBook.length?`
                <label class="field">Use a saved address
                    <select id="savedAddress">
                        ${addressBook.map(a=>`<option value="${a.id}" ${defaultAddress&&Number(a.id)===Number(defaultAddress.id)?'selected':''}>${esc(a.label)}${Number(a.is_default)===1?' · Default':''} — ${esc(a.city)}, ${esc(a.province)}</option>`).join('')}
                        <option value="new">Enter a different address</option>
                    </select>
                </label>
                <p class="small text-secondary"><a href="php/profile.php">Manage saved addresses</a></p>
                `:''}

                <label class="field">
                    Full name
                    <input id="fn" value="${esc(defaultAddress?defaultAddress.recipient_name:(u.full_name||''))}" autocomplete="name">
                </label>

                <label class="field">
                    Phone number
                    <input id="ph" value="${esc(defaultAddress?defaultAddress.phone:(u.contact||''))}" type="tel" inputmode="tel" autocomplete="tel">
                </label>

                <label class="field">
                    Address
                    <textarea id="ad" rows="3" placeholder="House no., street, barangay, city">${esc(initialAddress)}</textarea>
                </label>
            </div>

            <div class="panel" id="checkoutItems">
                <h2>${activeBundle?`${esc(activeBundle.title)} bundle`:'' || 'Products ordered'}</h2>

                ${ch.map(i=>{
                    const product=find(i.id);
                    return`
                <div class="ck-item">
                    <img src="${esc(i.img)}" alt="" onerror="this.style.visibility='hidden'">

                    <div class="ck-item-main">
                        <b>${esc(i.name)}</b>
                        <small>${activeBundle?'Bundle item':`${peso(i.price)} each`}</small>
                        <div class="ck-qty" aria-label="Quantity for ${esc(i.name)}">
                            <button type="button" data-check-act="minus" data-id="${esc(i.id)}" aria-label="Decrease ${esc(i.name)} quantity">−</button>
                            <input class="ck-qty-input" type="number" min="1" max="${product?Math.min(MAX_ITEM_QUANTITY,product.stock):i.qty}" step="1" value="${i.qty}" data-id="${esc(i.id)}" aria-label="Quantity for ${esc(i.name)}">
                            <button type="button" data-check-act="plus" data-id="${esc(i.id)}" aria-label="Increase ${esc(i.name)} quantity" ${product&&i.qty>=Math.min(MAX_ITEM_QUANTITY,product.stock)?'disabled':''}>+</button>
                        </div>
                    </div>

                    <span>${activeBundle?'Included':peso(i.price*i.qty)}</span>
                </div>`}).join('')}
            </div>

            <div class="panel ck-card">
                <h2>Payment method</h2>

                <label class="pay-opt">
                    <input type="radio" name="pm" value="COD" checked>
                    Cash on Delivery
                </label>

                <label class="pay-opt">
                    <input type="radio" name="pm" value="GCash">
                    GCash
                </label>

                <label class="pay-opt">
                    <input type="radio" name="pm" value="Maya">
                    Maya
                </label>

                <label class="pay-opt">
                    <input type="radio" name="pm" value="Card">
                    Credit/Debit Card
                </label>

                <div id="ckCard">
                    <small style="display:block;margin-bottom:8px;color:var(--st-ink-2)">
                        Demo checkout: no card is processed.
                    </small>

                    <input type="text" id="cn" placeholder="Cardholder name">
                    <input type="text" id="cc" placeholder="Card number" inputmode="numeric">

                    <div class="d-flex gap-2">
                        <input type="text" id="ce" placeholder="MM/YY" inputmode="numeric">
                        <input type="text" id="cv" placeholder="CVV" inputmode="numeric">
                    </div>
                </div>
            </div>

        </div>

        <aside class="ck-side">

            <div class="panel">
                <h2>Order summary</h2>

                <div class="pay-row">
                    <span id="checkoutSubtotalLabel">${activeBundle?`Bundle total (${totals.quantity} ${totals.quantity===1?'set':'sets'})`:`Subtotal (${count(ch)} items)`}</span>
                    <span id="checkoutSubtotal">${peso(s)}</span>
                </div>
                ${activeBundle?`<p class="small text-success mb-2" id="bundleSavings"${totals.savings>0?'':' hidden'}>Bundle discount included — you save ${peso(totals.savings)}.</p>`:''}

                <div class="pay-row">
                    <span>Shipping</span>
                    <span id="checkoutShipping">${sh?peso(sh):'Free'}</span>
                </div>

                ${activeBundle?`<p class="small text-secondary mt-3 mb-0">The bundle price is already discounted. Voucher codes cannot be combined with bundle purchases.</p>`:`<form class="voucher-form" id="voucherForm">
                    <input id="voucherCode" type="text" maxlength="40" autocomplete="off" placeholder="Voucher code" aria-label="Voucher code" value="${esc(voucherCode)}">
                    <button type="submit" id="applyVoucher">Apply</button>
                </form>
                <p id="voucherMessage" class="voucher-message" role="status" aria-live="polite">${esc(voucherMessage)}</p>`}
                <section class="checkout-vouchers" aria-labelledby="checkoutVouchersTitle">
                    <h3 id="checkoutVouchersTitle">Your vouchers</h3>
                    <p class="checkout-vouchers-intro">Applicable vouchers can be selected. Others are shown disabled.</p>
                    <div class="checkout-voucher-list" id="checkoutVoucherList"><p class="checkout-voucher-status">Checking vouchers for this order…</p></div>
                </section>

                <div class="pay-row" id="voucherDiscountRow" ${voucherDiscount>0?'':'hidden'}>
                    <span>Voucher discount (${esc(voucherCode)})</span>
                    <span id="voucherDiscountValue">−${peso(voucherDiscount)}</span>
                    <button type="button" class="btn-link" id="removeVoucher">Remove</button>
                </div>

                <div class="pay-row total">
                    <span>Total</span>
                    <span id="checkoutTotal">${peso(s-voucherDiscount+sh)}</span>
                </div>

                <p id="err" class="msg err" role="alert"></p>

                <button type="button" class="btn-clay" id="place" style="width:100%">
                    Place order
                </button>
            </div>

        </aside>

    </div>`;

    scrollTo(0,0);
    loadCheckoutVouchers();
    if(voucherCode)validateVoucher(voucherCode);
}

async function validateVoucher(code){
    const input=$('#voucherCode');
    const button=$('#applyVoucher');
    const message=$('#voucherMessage');
    if(!input||!button||!message)return;
    button.disabled=true;
    button.textContent='Checking...';
    $('#place').disabled=true;
    message.className='voucher-message';
    message.textContent='';

    try{
        const response=await fetch('php/apply_voucher.php',{
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body:JSON.stringify({
                code,
                items:chosen().map(item=>({id:item.id,quantity:item.qty}))
            })
        });
        const result=await response.json();
        if(!response.ok||!result.valid){
            voucherCode='';
            voucherDiscount=0;
            voucherMessage=result.message||'Unable to apply that voucher.';
            message.className='voucher-message error';
            message.textContent=voucherMessage;
        }else{
            voucherCode=result.code;
            voucherDiscount=Number(result.discount);
            voucherMessage=voucherCode+' applied. You saved '+peso(voucherDiscount)+'.';
            message.className='voucher-message success';
            message.textContent=voucherMessage;
            input.value=voucherCode;
        }
        renderCheckoutVouchers();
        const discountRow=$('#voucherDiscountRow');
        discountRow.hidden=voucherDiscount<=0;
        if(voucherDiscount>0){
            discountRow.querySelector('span').textContent='Voucher discount ('+voucherCode+')';
            $('#voucherDiscountValue').textContent='−'+peso(voucherDiscount);
        }
        const subtotal=sub(chosen()),shipping=shipFor(subtotal);
        $('#checkoutTotal').textContent=peso(Math.max(0,subtotal-voucherDiscount)+shipping);
    }catch(error){
        voucherCode='';
        voucherDiscount=0;
        voucherMessage='Unable to check the voucher right now. Please try again.';
        message.className='voucher-message error';
        message.textContent=voucherMessage;
        renderCheckoutVouchers();
        $('#voucherDiscountRow').hidden=true;
        $('#checkoutTotal').textContent=peso(sub(chosen())+shipFor(sub(chosen())));
    }finally{
        button.disabled=false;
        button.textContent='Apply';
        $('#place').disabled=false;
    }
}

function place(){
    const ch=chosen();
    const check=canFulfill(ch);
    const err=m=>$('#err').textContent=m;
    const totals=bundleTotals(ch);

    if(!check.ok)return err(check.reason);
    if(!totals.valid)return err(totals.reason);
    if(!activeBundle&&$('#voucherCode').value.trim().toUpperCase()!==voucherCode){
        return err('Apply the voucher code before placing your order.');
    }

    const fn=$('#fn').value.trim();
    const ph=$('#ph').value.trim();
    const ad=$('#ad').value.trim();
    const pm=document.querySelector('input[name=pm]:checked').value;

    if(!fn)return err('Enter your full name.');
    if(ph.replace(/\D/g,'').length<10)return err('Enter a valid phone number.');
    if(!ad)return err('Please add your delivery address.');

    if(pm==='Card'){
        if(!$('#cn').value.trim()||!$('#ce').value.trim()||!$('#cv').value.trim()){
            return err('Please fill in your card details.');
        }

        if($('#cc').value.replace(/\D/g,'').length<13){
            return err('Enter a valid card number.');
        }
    }

    const items=ch.map(i=>({
        id:i.id,
        quantity:i.qty
    }));

    $('#postName').value=fn;
    $('#postPhone').value=ph;
    $('#postAddress').value=ad;
    $('#postPayment').value=pm;
    $('#postItems').value=JSON.stringify(items);
    $('#postVoucher').value=voucherCode;
    $('#postBundleId').value=activeBundle?String(activeBundle.id):'';

    $('#place').disabled=true;
    $('#place').textContent='Placing order...';

    $('#checkoutForm').submit();
}

document.addEventListener('click',e=>{
    const b=e.target.closest('[data-act],[data-check-act],#back,#place,#removeVoucher,[data-voucher-code],[data-remove-voucher]');
    if(!b)return;

    if(b.id==='removeVoucher'||b.hasAttribute('data-remove-voucher')){
        voucherCode='';
        voucherDiscount=0;
        voucherMessage='Voucher removed.';
        const input=$('#voucherCode');
        if(input)input.value='';
        const message=$('#voucherMessage');
        if(message){
            message.className='voucher-message';
            message.textContent=voucherMessage;
        }
        $('#voucherDiscountRow').hidden=true;
        $('#checkoutTotal').textContent=peso(sub(chosen())+shipFor(sub(chosen())));
        renderCheckoutVouchers();
        return;
    }

    if(b.hasAttribute('data-voucher-code')){
        const code=b.dataset.voucherCode||'';
        const input=$('#voucherCode');
        if(input)input.value=code;
        validateVoucher(code);
        return;
    }

    if(b.dataset.act==='exit-bundle'){
        activeBundle=null;
        bundlePurchase=null;
        sessionStorage.removeItem('st_bundle_purchase');
        sel=new Set(cart.map(i=>i.id));
        drawCart();
        return;
    }

    if(b.id==='back'){
        voucherCode='';
        voucherDiscount=0;
        voucherMessage='';
        $('#vCheck').hidden=true;
        $('#vCart').hidden=false;
        $('#ttl').textContent='Shopping cart';
        drawCart();
        return;
    }

    if(b.id==='place'){
        place();
        return;
    }

    if(b.hasAttribute('data-check-act')){
        const item=cart.find(cartItem=>cartItem.id===b.dataset.id);
        const product=item?find(item.id):null;
        if(!item||!product)return;
        if(b.dataset.checkAct==='plus'){
            if(item.qty>=Math.min(MAX_ITEM_QUANTITY,product.stock))return;
            item.qty++;
        }else if(b.dataset.checkAct==='minus'){
            item.qty=Math.max(1,item.qty-1);
        }else{
            return;
        }
        save();
        refreshCheckoutQuantities();
        return;
    }

    const row=b.closest('.ct-row');
    const id=row?row.dataset.id:null;
    const item=cart.find(i=>i.id===id);
    const action=b.dataset.act;

    if(action==='plus'&&item){
        const p=find(id);
        const max=p?Math.min(MAX_ITEM_QUANTITY,Number(p.stock)):0;

        if(item.qty>=max){
            alert(`Only ${max} stock available for ${item.name}.`);
            return;
        }

        item.qty++;
    }else if(action==='minus'&&item){
        item.qty=Math.max(1,item.qty-1);
    }else if(action==='del'&&item){
        if(!confirm('Remove this item?'))return;

        if(activeBundle){
            activeBundle=null;
            bundlePurchase=null;
            sessionStorage.removeItem('st_bundle_purchase');
        }
        cart=cart.filter(i=>i!==item);
        sel=new Set(cart.map(i=>i.id));
    }else if(action==='delsel'){
        if(!sel.size)return;
        if(!confirm('Delete selected items?'))return;

        cart=cart.filter(i=>!sel.has(i.id));
        sel.clear();
    }else if(action==='go'){
        if(!chosen().length){
            alert('Select at least one item.');
            return;
        }
        const totals=bundleTotals(chosen());
        if(!totals.valid){
            alert(totals.reason);
            return;
        }

        drawCheck();
        return;
    }

    save();
    drawCart();
});

document.addEventListener('change',e=>{
    const t=e.target;

    if(t.matches('.ck-qty-input')){
        const item=cart.find(cartItem=>cartItem.id===t.dataset.id);
        const product=item?find(item.id):null;
        if(!item||!product)return;
        item.qty=Math.min(MAX_ITEM_QUANTITY,product.stock,Math.max(1,Math.floor(Number(t.value)||1)));
        save();
        refreshCheckoutQuantities();
        return;
    }else if(t.classList.contains('ck-all')){
        sel=t.checked?new Set(cart.map(i=>i.id)):new Set();
    }else if(t.classList.contains('ck-i')){
        const id=t.closest('.ct-row').dataset.id;

        if(t.checked)sel.add(id);
        else sel.delete(id);
    }else if(t.id==='savedAddress'){
        const address=addressBook.find(a=>String(a.id)===t.value);

        if(address){
            $('#fn').value=address.recipient_name;
            $('#ph').value=address.phone;
            $('#ad').value=`${address.address_line}, Barangay ${address.barangay}, ${address.city}, ${address.province} ${address.postal_code}`;
        }else{
            $('#fn').value=window.DB_USER?.full_name||'';
            $('#ph').value='';
            $('#ad').value='';
        }
        return;
    }else if(t.name==='pm'){
        $('#ckCard').style.display=t.value==='Card'?'block':'none';
        return;
    }else{
        return;
    }

    drawCart();
});

document.addEventListener('input',e=>{
    const t=e.target;

    if(t.id==='cc'){
        t.value=t.value.replace(/\D/g,'').slice(0,19).replace(/(.{4})/g,'$1 ').trim();
    }

    if(t.id==='ce'){
        const v=t.value.replace(/\D/g,'').slice(0,4);
        t.value=v.length>2?v.slice(0,2)+'/'+v.slice(2):v;
    }

    if(t.id==='cv'){
        t.value=t.value.replace(/\D/g,'').slice(0,4);
    }
});

document.addEventListener('submit',e=>{
    if(e.target.id!=='voucherForm')return;
    e.preventDefault();
    const code=$('#voucherCode').value.trim();
    if(!code){
        $('#voucherMessage').className='voucher-message error';
        $('#voucherMessage').textContent='Enter a voucher code.';
        return;
    }
    validateVoucher(code);
});

drawCart();

if(buy&&chosen().length){
    drawCheck();
}

function refreshCheckoutQuantities(){
    const ch=chosen();
    const totals=bundleTotals(ch);
    const subtotal=totals.subtotal;
    const shipping=shipFor(subtotal);
    const list=$('#checkoutItems');
    if(list){
        list.innerHTML=`<h2>${activeBundle?`${esc(activeBundle.title)} bundle`:'Products ordered'}</h2>${ch.map(i=>{
            const product=find(i.id);
            return`<div class="ck-item">
                <img src="${esc(i.img)}" alt="" onerror="this.style.visibility='hidden'">
                <div class="ck-item-main">
                    <b>${esc(i.name)}</b>
                    <small>${activeBundle?'Bundle item':`${peso(i.price)} each`}</small>
                    <div class="ck-qty" aria-label="Quantity for ${esc(i.name)}">
                        <button type="button" data-check-act="minus" data-id="${esc(i.id)}" aria-label="Decrease ${esc(i.name)} quantity">−</button>
                        <input class="ck-qty-input" type="number" min="1" max="${product?Math.min(MAX_ITEM_QUANTITY,product.stock):i.qty}" step="1" value="${i.qty}" data-id="${esc(i.id)}" aria-label="Quantity for ${esc(i.name)}">
                        <button type="button" data-check-act="plus" data-id="${esc(i.id)}" aria-label="Increase ${esc(i.name)} quantity" ${product&&i.qty>=Math.min(MAX_ITEM_QUANTITY,product.stock)?'disabled':''}>+</button>
                    </div>
                </div>
                <span>${activeBundle?'Included':peso(i.price*i.qty)}</span>
            </div>`;
        }).join('')}`;
    }
    const subtotalLabel=$('#checkoutSubtotalLabel');
    if(subtotalLabel)subtotalLabel.textContent=activeBundle
        ?`Bundle total (${totals.quantity} ${totals.quantity===1?'set':'sets'})`
        :`Subtotal (${count(ch)} items)`;
    if($('#checkoutSubtotal'))$('#checkoutSubtotal').textContent=peso(subtotal);
    if($('#checkoutShipping'))$('#checkoutShipping').textContent=shipping?peso(shipping):'Free';
    if($('#checkoutTotal'))$('#checkoutTotal').textContent=peso(Math.max(0,subtotal-voucherDiscount)+shipping);
    const savings=$('#bundleSavings');
    if(savings){
        savings.hidden=totals.savings<=0;
        savings.textContent=`Bundle discount included — you save ${peso(totals.savings)}.`;
    }
    if(voucherCode&&!activeBundle)validateVoucher(voucherCode);
    else loadCheckoutVouchers();
}

})();
</script>

<script src="search.js?v=search-inline-4"></script>

</body>
</html>