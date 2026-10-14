<?php
session_start();
require_once "php/database.php";

$stmt=$conn->query("SELECT product_code,name,price,image,stock FROM products ORDER BY id ASC");
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

$loggedIn=isset($_SESSION["user_id"])&&($_SESSION["role"]??"")==="customer";
$user=[];

if($loggedIn){
    $stmt=$conn->prepare("SELECT username,full_name,contact,address FROM users WHERE id=? LIMIT 1");
    $stmt->execute([$_SESSION["user_id"]]);
    $user=$stmt->fetch(PDO::FETCH_ASSOC)?:[];
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

<link href="styles.css" rel="stylesheet">
<link href="shop.css" rel="stylesheet">
<link href="pages.css" rel="stylesheet">

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
.ck-side{position:sticky;top:110px}
.ck-card input[type=text]{width:100%;border:1px solid var(--st-sand);border-radius:12px;padding:14px;font-size:15px;margin-bottom:12px}
#ckCard{display:none;margin-top:12px}

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
<section id="vCart"></section>
<section id="vCheck" hidden></section>
</main>

<form id="checkoutForm" action="php/checkout.php" method="POST" hidden>
<input type="hidden" name="full_name" id="postName">
<input type="hidden" name="phone" id="postPhone">
<input type="hidden" name="address" id="postAddress">
<input type="hidden" name="payment_method" id="postPayment">
<input type="hidden" name="items" id="postItems">
</form>

<script>
window.DB_PRODUCTS=<?php echo json_encode($dbProducts,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
window.LOGGED_IN=<?php echo $loggedIn?'true':'false'; ?>;
window.DB_USER=<?php echo json_encode($user,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="layout.js"></script>

<script>
(function(){
const $=s=>document.querySelector(s);
const esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const peso=n=>'₱'+Number(n||0).toLocaleString('en-PH');
const FREE=3000,SHIP=150;
const products=Array.isArray(window.DB_PRODUCTS)?window.DB_PRODUCTS:[];
const read=(k,f)=>{try{const v=JSON.parse(localStorage.getItem(k));return v==null?f:v}catch(e){return f}};
const find=id=>products.find(p=>p.id===id)||null;

let cart=read('st_cart',[]);

cart=cart.map(i=>{
    const p=find(i.id);
    if(!p||p.stock<=0)return null;

    return{
        id:p.id,
        name:p.name,
        price:p.price,
        img:p.img,
        qty:Math.min(Math.max(1,Number(i.qty||1)),p.stock)
    };
}).filter(Boolean);

let sel=new Set(cart.map(i=>i.id));
let buy=null;

try{
    buy=sessionStorage.getItem('st_buy');
    sessionStorage.removeItem('st_buy');
}catch(e){}

if(buy)sel=new Set([buy]);

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

        if(item.qty>p.stock){
            return{
                ok:false,
                reason:'Only '+p.stock+' stock available for '+p.name+'.'
            };
        }
    }

    return{ok:true};
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
    const s=sub(ch);
    const all=ch.length===cart.length&&cart.length>0;
    const need=FREE-s;

    v.innerHTML=`
    <div class="ct-card ct-head">
        <input type="checkbox" class="ck-all" ${all?'checked':''}>
        <span>Product</span>
        <span class="ct-unit">Unit price</span>
        <span>Quantity</span>
        <span>Total</span>
        <span>Action</span>
    </div>

    ${cart.map(i=>{
        const p=find(i.id);
        const stock=p?p.stock:0;

        return`
        <div class="ct-card ct-row" data-id="${esc(i.id)}">
            <input type="checkbox" class="ck-i" ${sel.has(i.id)?'checked':''}>

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
                <button type="button" data-act="plus" ${i.qty>=stock?'disabled':''}>+</button>
            </div>

            <span class="ct-total">${peso(i.price*i.qty)}</span>
            <button type="button" class="ct-del" data-act="del">Delete</button>
        </div>`;
    }).join('')}

    <div class="ct-card ct-bar">
        <label>
            <input type="checkbox" class="ck-all" ${all?'checked':''}>
            Select all (${cart.length})
        </label>

        <button type="button" class="btn-link" data-act="delsel">Delete selected</button>

        <span class="ct-grow"></span>

        <div class="ct-sum">
            Total (${count(ch)} ${count(ch)===1?'item':'items'})
            <strong>${peso(s)}</strong>
        </div>

        <button type="button" class="btn-clay" data-act="go" style="padding:0 32px">Checkout</button>
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

    const s=sub(ch);
    const sh=shipFor(s);
    const u=window.DB_USER||{};

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

                <label class="field">
                    Full name
                    <input id="fn" value="${esc(u.full_name||'')}" autocomplete="name">
                </label>

                <label class="field">
                    Phone number
                    <input id="ph" value="${esc(u.contact||'')}" type="tel" inputmode="tel" autocomplete="tel">
                </label>

                <label class="field">
                    Address
                    <textarea id="ad" rows="3" placeholder="House no., street, barangay, city">${esc(u.address||'')}</textarea>
                </label>
            </div>

            <div class="panel">
                <h2>Products ordered</h2>

                ${ch.map(i=>`
                <div class="ck-item">
                    <img src="${esc(i.img)}" alt="" onerror="this.style.visibility='hidden'">

                    <div>
                        <b>${esc(i.name)}</b>
                        <small>${i.qty} × ${peso(i.price)}</small>
                    </div>

                    <span>${peso(i.price*i.qty)}</span>
                </div>`).join('')}
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
                    <span>Subtotal (${count(ch)} items)</span>
                    <span>${peso(s)}</span>
                </div>

                <div class="pay-row">
                    <span>Shipping</span>
                    <span>${sh?peso(sh):'Free'}</span>
                </div>

                <div class="pay-row total">
                    <span>Total</span>
                    <span>${peso(s+sh)}</span>
                </div>

                <p id="err" class="msg err" role="alert"></p>

                <button type="button" class="btn-clay" id="place" style="width:100%">
                    Place order
                </button>
            </div>

        </aside>

    </div>`;

    scrollTo(0,0);
}

function place(){
    const ch=chosen();
    const check=canFulfill(ch);
    const err=m=>$('#err').textContent=m;

    if(!check.ok)return err(check.reason);

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

    $('#place').disabled=true;
    $('#place').textContent='Placing order...';

    $('#checkoutForm').submit();
}

document.addEventListener('click',e=>{
    const b=e.target.closest('[data-act],#back,#place');
    if(!b)return;

    if(b.id==='back'){
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

    const row=b.closest('.ct-row');
    const id=row?row.dataset.id:null;
    const item=cart.find(i=>i.id===id);
    const action=b.dataset.act;

    if(action==='plus'&&item){
        const p=find(id);
        const max=p?Number(p.stock):0;

        if(item.qty>=max){
            alert(`Only ${max} stock available for ${item.name}.`);
            return;
        }

        item.qty++;
    }else if(action==='minus'&&item){
        item.qty=Math.max(1,item.qty-1);
    }else if(action==='del'&&item){
        if(!confirm('Remove this item?'))return;

        cart=cart.filter(i=>i!==item);
        sel.delete(id);
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

        drawCheck();
        return;
    }

    save();
    drawCart();
});

document.addEventListener('change',e=>{
    const t=e.target;

    if(t.classList.contains('ck-all')){
        sel=t.checked?new Set(cart.map(i=>i.id)):new Set();
    }else if(t.classList.contains('ck-i')){
        const id=t.closest('.ct-row').dataset.id;

        if(t.checked)sel.add(id);
        else sel.delete(id);
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

drawCart();

if(buy&&chosen().length){
    drawCheck();
}

})();
</script>

<script src="search.js"></script>

</body>
</html>