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

$merchandiseAfterDiscount=max(
    0,
    (float)($order["subtotal"]??0)-(float)($order["discountAmount"]??0)
);
$estimatedVatableSales=round($merchandiseAfterDiscount/1.12,2);
$estimatedVat=max(0,round($merchandiseAfterDiscount-$estimatedVatableSales,2));
$createdAt=strtotime((string)($order["createdAt"]??""));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>Printable Receipt — SmartTech Appliances</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="styles.css" rel="stylesheet">
<link href="shop.css" rel="stylesheet">
<link href="pages.css?v=mobile-gutters-2" rel="stylesheet">

<style>
.rc-wrap{max-width:780px;margin:0 auto}.rc-card{background:#fff;border:1px solid var(--st-sand);border-radius:18px;padding:28px}.rc-success{text-align:center;margin-bottom:28px}.rc-success i{display:inline-grid;place-items:center;width:64px;height:64px;border-radius:50%;background:#E4F3EB;color:#1B6B4A;font-size:32px}.rc-success h1{font-size:30px;margin:16px 0 6px}.rc-success p{color:var(--st-ink-2);margin:0}.rc-brand{display:flex;align-items:center;gap:14px;padding-bottom:20px;margin-bottom:20px;border-bottom:2px solid var(--st-ink)}.rc-brand-logo{display:grid;width:96px;height:72px;place-items:center;flex:none;border-radius:10px;background:var(--st-ink);padding:8px}.rc-brand-logo img{width:100%;height:100%;object-fit:contain}.rc-brand h2{margin:0;font-size:21px;font-weight:800}.rc-brand p{margin:4px 0 0;color:var(--st-ink-2);font-size:12px}.rc-receipt-title{margin:0 0 16px;font-size:19px;font-weight:800;letter-spacing:.08em}.rc-info{display:grid;grid-template-columns:1fr 1fr;gap:10px 20px;margin-bottom:24px}.rc-info div{min-width:0;padding:10px 12px;border:1px solid var(--st-sand);border-radius:10px;overflow-wrap:anywhere}.rc-info small{display:block;color:var(--st-ink-2);margin-bottom:5px}.rc-items-title{font-size:16px;font-weight:800;margin:0 0 4px}.rc-item{display:flex;align-items:center;gap:14px;padding:14px 0;border-bottom:1px solid var(--st-sand)}.rc-item:last-child{border-bottom:0}.rc-item img{width:58px;height:58px;object-fit:contain;background:#F1F2F6;border-radius:10px;padding:5px}.rc-item div{flex:1;min-width:0}.rc-item small{display:block;color:var(--st-ink-2);margin-top:4px}.rc-item strong{white-space:nowrap}.rc-summary{margin-top:14px;padding-top:12px;border-top:2px solid var(--st-ink)}.rc-row{display:flex;justify-content:space-between;gap:20px;padding:5px 0}.rc-row span:last-child{text-align:right;white-space:nowrap}.rc-row.total{font-size:20px;font-weight:800;margin-top:6px;padding-top:12px;border-top:1px solid var(--st-sand)}.rc-tax-note{margin:10px 0 0;color:var(--st-ink-2);font-size:11px;line-height:1.5}.rc-thanks{margin:20px 0 0;padding-top:14px;border-top:1px dashed var(--st-sand);text-align:center;color:var(--st-ink-2);font-size:12px}.rc-actions{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:24px}.rc-print{min-height:38px;padding:0 14px;border:0;border-radius:999px;background:var(--st-ink);color:#fff;font-size:13px;font-weight:700}.rc-print:hover{background:var(--st-clay)}@media(max-width:575.98px){.rc-card{padding:16px}.rc-info{grid-template-columns:1fr 1fr}.rc-brand-logo{width:58px;height:44px}.rc-item{gap:8px}.rc-item img{width:40px;height:40px}.rc-row{gap:12px}}
@media(max-width:380px){.rc-info{grid-template-columns:1fr}.rc-info div{align-items:flex-start}.rc-info strong{max-width:60%}}
.rc-actions .rc-download,.rc-actions .rc-print{height:38px!important;min-height:38px!important;padding:0 14px;line-height:1.2;font-size:13px}.rc-download{border:0;border-radius:999px;background:var(--st-clay);color:#fff;font-weight:700}.rc-download:hover{background:var(--st-clay-2)}.rc-download:disabled{opacity:.65;cursor:wait}.rc-download-status{width:100%;margin:0;text-align:center;color:var(--st-ink-2);font-size:12px}
@media screen and (max-height:900px){
    .rc-wrap{max-width:700px}
    .rc-card{padding:18px 22px}
    .rc-brand{gap:10px;padding-bottom:10px;margin-bottom:10px}
    .rc-brand-logo{width:64px;height:48px;padding:5px}
    .rc-brand h2{font-size:18px}
    .rc-receipt-title{margin-bottom:8px;font-size:17px}
    .rc-info{gap:5px 10px;margin-bottom:10px}
    .rc-info div{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:5px 8px}
    .rc-info small{flex:none;margin:0;font-size:11px}
    .rc-info strong{font-size:12px;text-align:right}
    .rc-items-title{font-size:14px}
    .rc-item{gap:10px;padding:7px 0}
    .rc-item img{width:42px;height:42px;padding:3px}
    .rc-item small{margin-top:2px}
    .rc-summary{margin-top:8px;padding-top:7px}
    .rc-row{padding:3px 0;font-size:13px}
    .rc-row.total{margin-top:4px;padding-top:7px;font-size:17px}
    .rc-tax-note{margin-top:6px;font-size:10px}
    .rc-thanks{margin-top:10px;padding-top:8px}
    .rc-actions{gap:8px;margin-top:12px}
}
@media screen and (max-height:760px){
    .rc-card{transform:scale(.82);transform-origin:top center;margin-bottom:-18%}
}
@media screen and (max-height:650px){
    .rc-card{transform:scale(.72);margin-bottom:-28%}
}
@media screen and (min-height:575px) and (max-height:650px){
    html,body{overflow:hidden}
}
@media print{
    @page{size:auto;margin:14mm}
    body{background:#fff!important;color:#111!important;font-family:Arial,sans-serif!important}
    .rc-wrap{max-width:none;margin:0}
    .rc-card{border:0;border-radius:0;padding:0;box-shadow:none}
    .rc-brand{break-inside:avoid}
    .rc-info div{border-color:#ccc}
    .rc-item{break-inside:avoid;border-color:#ccc}
    .rc-item img{print-color-adjust:exact;-webkit-print-color-adjust:exact}
    .rc-summary,.rc-thanks{break-inside:avoid;border-color:#777}
    .rc-actions,.rc-success,.back-to-top,.st-bottomnav{display:none!important}
    a{color:#111!important;text-decoration:none!important}
}
</style>
</head>

<body>

<main class="container-xxl px-lg-5 pg">
<div class="rc-wrap">
<div class="rc-card">

<header class="rc-brand">
<div class="rc-brand-logo"><img src="images/smarttechappliances.svg" alt="SmartTech Appliances"></div>
<div>
<h2>SmartTech Appliances</h2>
<p>Online order receipt</p>
</div>
</header>

<h1 class="rc-receipt-title">ORDER RECEIPT</h1>
<div class="rc-info">
<div>
<small>Order / receipt reference</small>
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
<div>
<small>Order date</small>
<strong><?php echo $createdAt?date("M j, Y g:i A",$createdAt):"—"; ?></strong>
</div>
</div>

<h2 class="rc-items-title">Items purchased</h2>

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

<?php if((float)($order["discountAmount"]??0)>0): ?>
<div class="rc-row">
<span>Voucher<?php echo !empty($order["voucherCode"])?" (".e($order["voucherCode"]).")":""; ?></span>
<span>−<?php echo peso($order["discountAmount"]); ?></span>
</div>
<?php endif; ?>

<?php if((float)($order["bundleDiscount"]??0)>0): ?>
<div class="rc-row text-success">
<span><?php echo e($order["bundleTitle"]??"Bundle"); ?> savings (already applied)</span>
<span>−<?php echo peso($order["bundleDiscount"]); ?></span>
</div>
<?php endif; ?>

<div class="rc-row">
<span>VATable sales (estimated)</span>
<span><?php echo peso($estimatedVatableSales); ?></span>
</div>
<div class="rc-row">
<span>VAT (12% included, estimated)</span>
<span><?php echo peso($estimatedVat); ?></span>
</div>

<div class="rc-row total">
<span>Total</span>
<span><?php echo peso($order["grandTotal"]); ?></span>
</div>
<p class="rc-tax-note">VAT figures are an estimate based on a 12% VAT-inclusive rate applied to merchandise after discounts; shipping is excluded. Seller tax-registration details and tax treatment are not configured. This is an order receipt, not an official BIR-registered invoice.</p>
</div>

<p class="rc-thanks">Thank you for your purchase. Please keep this receipt for your order records.</p>

<div class="rc-actions">
<button class="rc-download" id="downloadReceipt" type="button"><i class="bi bi-download me-2" aria-hidden="true"></i>Download Receipt Image</button>
<button class="rc-print" type="button" onclick="window.print()"><i class="bi bi-printer me-2" aria-hidden="true"></i>Print / Save as PDF</button>
<a class="btn-link text-decoration-none d-inline-grid align-items-center px-3" href="receipt.php">Back to order summary</a>
<p class="rc-download-status" id="receiptDownloadStatus" role="status" aria-live="polite"></p>
</div>

</div>
</div>
</main>

<script>
(function(){
const receipt=<?php echo json_encode([
    "orderNumber"=>$order["orderNumber"],
    "status"=>$order["status"],
    "fullName"=>$order["fullName"],
    "paymentMethod"=>$order["paymentMethod"],
    "phone"=>$order["phone"],
    "address"=>$order["address"],
    "createdAt"=>$createdAt?date("M j, Y g:i A",$createdAt):"—",
    "items"=>$order["items"],
    "subtotal"=>(float)$order["subtotal"],
    "shippingFee"=>(float)$order["shippingFee"],
    "voucherCode"=>(string)($order["voucherCode"]??""),
    "discountAmount"=>(float)($order["discountAmount"]??0),
    "bundleTitle"=>(string)($order["bundleTitle"]??""),
    "bundleDiscount"=>(float)($order["bundleDiscount"]??0),
    "grandTotal"=>(float)$order["grandTotal"],
    "estimatedVatableSales"=>$estimatedVatableSales,
    "estimatedVat"=>$estimatedVat
],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
const button=document.getElementById('downloadReceipt');
const status=document.getElementById('receiptDownloadStatus');
const currency=value=>'₱'+Number(value||0).toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2});
const loadImage=src=>new Promise((resolve,reject)=>{
    const image=new Image();
    image.onload=()=>resolve(image);
    image.onerror=()=>reject(new Error('Could not load an image for the receipt.'));
    image.src=src;
});
const roundedRect=(context,x,y,width,height,radius)=>{
    context.beginPath();
    context.roundRect(x,y,width,height,radius);
};
const wrapText=(context,text,x,y,maxWidth,lineHeight)=>{
    const words=String(text||'').split(/\s+/);
    let line='',lines=0;
    for(const word of words){
        const next=line?line+' '+word:word;
        if(line&&context.measureText(next).width>maxWidth){
            context.fillText(line,x,y+lines*lineHeight);
            line=word;
            lines++;
        }else{
            line=next;
        }
    }
    if(line)context.fillText(line,x,y+lines*lineHeight);
    return Math.max(1,lines+1)*lineHeight;
};
const drawImage=async()=>{
    button.disabled=true;
    status.textContent='Preparing your receipt image…';
    try{
        if(document.fonts&&document.fonts.ready)await document.fonts.ready;
        const width=1000,pad=72,inner=width-pad*2;
        const canvas=document.createElement('canvas');
        const context=canvas.getContext('2d');
        if(!context)throw new Error('Your browser cannot create a receipt image.');
        let y=62;
        const logo=await loadImage('images/smarttechappliances.svg').catch(()=>null);
        const productImages=await Promise.all(receipt.items.map(item=>
            loadImage(item.image).catch(()=>null)
        ));
        const line=(color='#e4ded3',thickness=2)=>{
            context.strokeStyle=color;
            context.lineWidth=thickness;
            context.beginPath();
            context.moveTo(pad,y);
            context.lineTo(width-pad,y);
            context.stroke();
        };
        const row=(label,value,bold=false)=>{
            context.font=bold?'700 26px Arial':'22px Arial';
            context.fillStyle='#182d29';
            context.textAlign='left';
            context.fillText(label,pad,y);
            context.textAlign='right';
            context.fillText(value,width-pad,y);
            context.textAlign='left';
            y+=bold?48:38;
        };
        context.font='22px Arial';
        const infoHeight=Math.max(
            wrapText(context,'Customer: '+receipt.fullName,pad,y,inner/2-20,30),
            wrapText(context,'Order / receipt reference: '+receipt.orderNumber,pad+inner/2+20,y,inner/2-20,30)
        );
        const infoRows=[
            ['Status: '+receipt.status,'Payment: '+receipt.paymentMethod],
            ['Contact: '+receipt.phone,'Order date: '+receipt.createdAt]
        ];
        const measureInfo=(value,max)=>wrapText(context,value,0,0,max,30);
        for(const pair of infoRows){
            y+=Math.max(measureInfo(pair[0],inner/2-20),measureInfo(pair[1],inner/2-20))+8;
        }
        context.font='22px Arial';
        const addressHeight=measureInfo('Delivery address: '+receipt.address,inner);
        y+=addressHeight+30;
        const itemHeights=receipt.items.map(item=>{
            context.font='bold 23px Arial';
            const nameHeight=wrapText(context,item.product_name,0,0,inner-210,30);
            return Math.max(92,nameHeight+55);
        });
        y+=58+itemHeights.reduce((sum,height)=>sum+height,0);
        const summaryRows=4+(receipt.discountAmount>0?1:0)+(receipt.bundleDiscount>0?1:0);
        y+=summaryRows*42+430;
        canvas.width=width;
        canvas.height=Math.ceil(y+42);
        context.fillStyle='#ffffff';
        context.fillRect(0,0,canvas.width,canvas.height);
        y=62;
        context.fillStyle='#102d27';
        roundedRect(context,pad,y,inner,116,18);
        context.fill();
        if(logo)context.drawImage(logo,pad+24,y+16,104,84);
        context.fillStyle='#ffffff';
        context.font='bold 32px Arial';
        context.fillText('SmartTech Appliances',pad+156,y+51);
        context.font='20px Arial';
        context.fillText('ORDER RECEIPT',pad+156,y+83);
        y+=154;
        context.fillStyle='#182d29';
        context.font='bold 30px Arial';
        context.fillText('Order / receipt reference',pad,y);
        context.textAlign='right';
        context.fillText(receipt.orderNumber,width-pad,y);
        context.textAlign='left';
        y+=42;
        context.font='21px Arial';
        const col=inner/2;
        const info=[
            ['Status: '+receipt.status,'Payment: '+receipt.paymentMethod],
            ['Customer: '+receipt.fullName,'Contact: '+receipt.phone],
            ['Order date: '+receipt.createdAt,'Delivery address: '+receipt.address]
        ];
        for(const pair of info){
            const leftHeight=wrapText(context,pair[0],pad,y,col-24,29);
            const rightHeight=wrapText(context,pair[1],pad+col+16,y,col-16,29);
            y+=Math.max(leftHeight,rightHeight)+10;
        }
        y+=20;
        line('#102d27',3);
        y+=42;
        context.font='bold 25px Arial';
        context.fillText('Items purchased',pad,y);
        y+=24;
        for(let index=0;index<receipt.items.length;index++){
            const item=receipt.items[index];
            const image=productImages[index];
            const height=itemHeights[index];
            const top=y;
            context.fillStyle='#f1f2f6';
            roundedRect(context,pad,top+10,72,72,10);
            context.fill();
            if(image)context.drawImage(image,pad+6,top+16,60,60);
            context.fillStyle='#182d29';
            context.font='bold 23px Arial';
            const itemNameHeight=wrapText(context,item.product_name,pad+96,top+37,inner-270,29);
            context.font='20px Arial';
            context.fillStyle='#686e6b';
            context.fillText(item.quantity+' × '+currency(item.price),pad+96,top+37+itemNameHeight+1);
            context.font='bold 23px Arial';
            context.fillStyle='#182d29';
            context.textAlign='right';
            context.fillText(currency(item.price*item.quantity),width-pad,top+49);
            context.textAlign='left';
            y=top+height;
            line();
            y+=20;
        }
        y+=12;
        line('#102d27',3);
        y+=42;
        row('Subtotal',currency(receipt.subtotal));
        row('Shipping',receipt.shippingFee>0?currency(receipt.shippingFee):'Free');
        if(receipt.discountAmount>0)row('Voucher'+(receipt.voucherCode?' ('+receipt.voucherCode+')':''),'−'+currency(receipt.discountAmount));
        if(receipt.bundleDiscount>0)row((receipt.bundleTitle||'Bundle')+' savings','−'+currency(receipt.bundleDiscount));
        row('VATable sales (estimated)',currency(receipt.estimatedVatableSales));
        row('VAT (12% included, estimated)',currency(receipt.estimatedVat));
        line('#e4ded3',2);
        y+=38;
        row('TOTAL',currency(receipt.grandTotal),true);
        context.fillStyle='#686e6b';
        context.font='16px Arial';
        y+=8;
        const noteHeight=wrapText(context,'Estimated VAT only. This is an order receipt, not an official BIR-registered invoice.',pad,y,inner,22);
        y+=noteHeight+26;
        context.strokeStyle='#d8d2c8';
        context.setLineDash([7,7]);
        context.beginPath();
        context.moveTo(pad,y);
        context.lineTo(width-pad,y);
        context.stroke();
        context.setLineDash([]);
        y+=40;
        context.textAlign='center';
        context.font='18px Arial';
        context.fillText('Thank you for your purchase.',width/2,y);
        context.textAlign='left';
        canvas.toBlob(blob=>{
            if(!blob){
                status.textContent='The receipt image could not be created. Please try again.';
                button.disabled=false;
                return;
            }
            const url=URL.createObjectURL(blob);
            const link=document.createElement('a');
            link.href=url;
            link.download='SmartTech-Receipt-'+receipt.orderNumber+'.png';
            document.body.appendChild(link);
            link.click();
            link.remove();
            setTimeout(()=>URL.revokeObjectURL(url),1000);
            status.textContent='Receipt image downloaded.';
            button.disabled=false;
        },'image/png');
    }catch(error){
        console.error('Unable to generate receipt image.',error);
        status.textContent=error.message||'Unable to generate receipt image. Please try again.';
        button.disabled=false;
    }
};
button.addEventListener('click',drawImage);
})();
</script>
<script src="back-to-top.js"></script>

</body>
</html>