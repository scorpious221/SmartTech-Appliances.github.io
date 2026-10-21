$(function(){
const PAGE_SIZE=6;
let productPage=1,editingProduct=null;
const products=Array.isArray(window.DB_PRODUCTS)?window.DB_PRODUCTS:[];
const customers=Array.isArray(window.DB_CUSTOMERS)?window.DB_CUSTOMERS:[];
const orders=Array.isArray(window.DB_ORDERS)?window.DB_ORDERS:[];

const SUBCATEGORIES={
household:['Security Devices','Utilities','Home Office'],
entertainment:['Televisions & Displays','Audio & Music','Gaming Gadgets'],
kitchen:['Refrigeration','Cooking Appliances','Food Preparation']
};

const titles={
dashboard:['Dashboard','Quick summary of your SmartTech store.'],
products:['Products & Stock','Add, edit, delete and manage product inventory.'],
orders:['Orders','Review orders and update their delivery status.'],
customers:['Customers','Registered SmartTech customer accounts.']
};

function esc(s){return String(s==null?'':s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));}
function peso(n){return'₱'+Number(n||0).toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2});}
function stockClass(n){n=Number(n);if(n<=0)return'out';if(n<=9)return'low';return'in';}
function stockText(n){n=Number(n);if(n<=0)return'Out of Stock';if(n<=9)return'Low Stock';return'In Stock';}
function statusClass(s){return String(s||'').toLowerCase().replace(/\s+/g,'-');}
function orderBadge(s){return`<span class="ad-status ${statusClass(s)}">${esc(s)}</span>`;}
function parseDate(v){if(!v)return null;const d=new Date(String(v).replace(' ','T'));return Number.isNaN(d.getTime())?null:d;}
function parseInputDate(v){if(!v)return null;const p=v.split('-');if(p.length!==3)return null;return new Date(Number(p[0]),Number(p[1])-1,Number(p[2]));}
function dateKey(d){return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0');}
function monthKey(d){return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0');}
function formatDate(v){const d=parseDate(v);if(!d)return v||'';return d.toLocaleString('en-PH',{year:'numeric',month:'short',day:'numeric',hour:'numeric',minute:'2-digit'});}
function formatShortDate(d){return d.toLocaleDateString('en-PH',{month:'short',day:'numeric',year:'numeric'});}
function makeProductCode(name){let base=String(name).toLowerCase().trim().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'')||'product',code=base,n=2;while(products.some(p=>p.id===code))code=base+'-'+n++;return code;}
function phpPost(action,values){const form=$('<form>',{method:'POST',action});$.each(values,(name,value)=>$('<input>',{type:'hidden',name,value}).appendTo(form));form.appendTo('body').trigger('submit');}

function prepareResponsiveTables(){
[
['#recentOrders','ad-recent-table'],
['#productRows','ad-products-table'],
['#customerRows','ad-customers-table']
].forEach(item=>{
const table=$(item[0]).closest('table');
table.addClass('ad-mobile-table '+item[1]);
table.closest('.ad-table-wrap').addClass('ad-mobile-table-wrap');
});
}

function setupProductSubcategory(){
const $old=$('#pSub');
if(!$old.length)return;
if(!$old.is('select'))$old.replaceWith('<select class="form-select" id="pSub" name="subcategory" required></select>');
populateSubcategories($('#pCategory').val()||'household','');
}

function populateSubcategories(category,selected){
category=String(category||'household').toLowerCase();
const list=SUBCATEGORIES[category]||[],$sub=$('#pSub');
$sub.html(list.map(sub=>`<option value="${esc(sub)}">${esc(sub)}</option>`).join(''));
if(selected&&list.includes(selected))$sub.val(selected);else if(list.length)$sub.val(list[0]);
}

function go(page){
if(!titles[page])page='dashboard';
$('.ad-nav [data-page]').removeClass('active');
$(`.ad-nav [data-page="${page}"]`).addClass('active');
$('.ad-sec').prop('hidden',true);
$('#'+page+'Sec').prop('hidden',false);
$('#pageTitle').text(titles[page][0]);
$('#pageSub').text(titles[page][1]);
if(page==='dashboard')renderDashboard();
if(page==='products')renderProducts();
if(page==='orders'){setupOrderFilters();renderOrders();}
if(page==='customers')renderCustomers();
$('html,body').scrollTop(0);
}

function getOrderDateBounds(){
const dates=orders.map(o=>parseDate(o.createdAt)).filter(Boolean).sort((a,b)=>a-b);
if(!dates.length){const d=new Date(),k=dateKey(d);return{first:k,last:k};}
return{first:dateKey(dates[0]),last:dateKey(dates[dates.length-1])};
}

function setupSalesFilter(){
if($('#salesRangeFilter').length)return;
const b=getOrderDateBounds(),$head=$('#salesChart').closest('.ad-panel').find('.ad-panel-head').first();
$head.append(`<div id="salesRangeFilter" class="sales-range"><label><span>From Date</span><input type="date" id="salesFromDate" value="${b.first}"></label><label><span>To Date</span><input type="date" id="salesToDate" value="${b.last}"></label><button type="button" class="ad-btn sm" id="applySalesRange">Filter</button></div>`);
}

function renderDashboard(){
const delivered=orders.filter(o=>String(o.status).toLowerCase()==='delivered');
const revenue=delivered.reduce((t,o)=>t+Number(o.grandTotal||0),0);
const pending=orders.filter(o=>['pending','processing'].includes(String(o.status||'Pending').toLowerCase()));
const low=products.filter(p=>Number(p.stock)<=9);
$('#mRevenue').text(peso(revenue));
$('#mOrders').text(orders.length);
$('#mProducts').text(products.length);
$('#mCustomers').text(customers.length);
$('#mPending').text(pending.length);
$('#mLow').text(low.length);
setupSalesFilter();
renderSalesChart();
renderLowStock();
renderRecentOrders();
}

function renderSalesChart(){
const from=parseInputDate($('#salesFromDate').val()),to=parseInputDate($('#salesToDate').val());
if(!from||!to){$('#salesChart').html('<div class="ad-empty">Choose a start and end date.</div>');return;}
if(from>to){$('#salesChart').html('<div class="ad-empty">From Date cannot be later than To Date.</div>');return;}

const delivered=orders.filter(o=>String(o.status||'').toLowerCase()==='delivered');
const diffDays=Math.floor((to-from)/86400000)+1;
let buckets=[],mode='day';

if(diffDays<=31){
let current=new Date(from);
while(current<=to){
buckets.push({key:dateKey(current),label:current.toLocaleDateString('en-PH',{month:'short',day:'numeric'}),total:0});
current.setDate(current.getDate()+1);
}
}else{
mode='month';
let current=new Date(from.getFullYear(),from.getMonth(),1);
const end=new Date(to.getFullYear(),to.getMonth(),1);
while(current<=end){
buckets.push({key:monthKey(current),label:current.toLocaleDateString('en-PH',{month:'short',year:'2-digit'}),total:0});
current.setMonth(current.getMonth()+1);
}
}

delivered.forEach(o=>{
const d=parseDate(o.createdAt);
if(!d)return;
const day=new Date(d.getFullYear(),d.getMonth(),d.getDate());
if(day<from||day>to)return;
const key=mode==='day'?dateKey(day):monthKey(day),bucket=buckets.find(b=>b.key===key);
if(bucket)bucket.total+=Number(o.grandTotal||0);
});

const max=Math.max(1,...buckets.map(b=>b.total));
const total=buckets.reduce((s,b)=>s+b.total,0);

$('#salesChart').closest('.ad-panel').find('.ad-muted').first().text(formatShortDate(from)+' to '+formatShortDate(to)+' · '+peso(total));

$('#salesChart').html(buckets.map(b=>{
const h=Math.max(4,Math.round(b.total/max*170));
return`<div class="ad-bar-wrap"><div class="ad-bar-value">${b.total>0?peso(b.total):'₱0'}</div><div class="ad-bar" title="${esc(b.label)}: ${peso(b.total)}" style="height:${h}px"></div><small>${esc(b.label)}</small></div>`;
}).join(''));
}

function renderLowStock(){
const list=products.filter(p=>Number(p.stock)<=9).sort((a,b)=>Number(a.stock)-Number(b.stock)).slice(0,6);
if(!list.length){$('#lowStockList').html('<div class="ad-empty">No low-stock products.</div>');return;}
$('#lowStockList').html(list.map(p=>`<div class="ad-list-row"><div><b>${esc(p.name)}</b><small>${esc(p.category)} · ${esc(p.sub)}</small></div><span class="ad-stock-num">${p.stock}</span></div>`).join(''));
}

function renderRecentOrders(){
const recent=orders.slice(0,6);
if(!recent.length){$('#recentOrders').html('<tr><td colspan="5" class="ad-empty">No orders yet.</td></tr>');return;}
$('#recentOrders').html(recent.map(o=>`<tr><td><b>${esc(o.orderNumber)}</b></td><td>${esc(o.name||'Customer')}</td><td>${esc(formatDate(o.createdAt))}</td><td><b>${peso(o.grandTotal)}</b></td><td>${orderBadge(o.status)}</td></tr>`).join(''));
}

function getFilteredProducts(){
const q=$.trim($('#productSearch').val()).toLowerCase();
return products.filter(p=>!q||(p.name+' '+p.id+' '+p.category+' '+p.sub).toLowerCase().includes(q));
}

function renderProducts(){
const filtered=getFilteredProducts(),total=filtered.length,totalPages=Math.max(1,Math.ceil(total/PAGE_SIZE));
if(productPage>totalPages)productPage=totalPages;
if(productPage<1)productPage=1;

const start=(productPage-1)*PAGE_SIZE,end=start+PAGE_SIZE,list=filtered.slice(start,end);

$('#productCount').text(total?`Showing ${start+1}–${Math.min(end,total)} of ${total} products`:'0 products found');

if(!list.length){
$('#productRows').html('<tr><td colspan="6" class="ad-empty">No products found.</td></tr>');
}else{
$('#productRows').html(list.map(p=>{
const stock=Math.min(100,Math.max(0,Number(p.stock)||0));
return`<tr data-db-id="${p.dbId}">
<td><div class="ad-prod"><img src="${esc(p.img)}" alt="" onerror="this.style.visibility='hidden'"><div><b>${esc(p.name)}</b><small>${esc(p.id)}</small></div></div></td>
<td>${esc(p.category)}<br><small class="ad-muted">${esc(p.sub)}</small></td>
<td><b>${peso(p.price)}</b></td>
<td><div class="ad-stock-control"><button type="button" data-stock="-1" ${stock<=0?'disabled':''}>−</button><span>${stock}</span><button type="button" data-stock="1" ${stock>=100?'disabled':''}>+</button></div></td>
<td><span class="ad-status ${stockClass(stock)}">${stockText(stock)}</span></td>
<td><div class="ad-actions"><button class="ad-btn outline sm" data-edit type="button"><i class="bi bi-pencil"></i>Edit</button><button class="ad-btn danger sm" data-delete type="button"><i class="bi bi-trash"></i>Delete</button></div></td>
</tr>`;
}).join(''));
}

renderPager(totalPages);
}

function renderPager(totalPages){
if(totalPages<=1){$('#productPager').empty();return;}

let html=`<li class="page-item ${productPage===1?'disabled':''}"><button class="page-link" data-page-number="${productPage-1}">Previous</button></li>`;

for(let i=1;i<=totalPages;i++){
if(totalPages>7&&i!==1&&i!==totalPages&&Math.abs(i-productPage)>1){
if(i===2&&productPage>3)html+='<li class="page-item disabled"><span class="page-link">...</span></li>';
if(i===totalPages-1&&productPage<totalPages-2)html+='<li class="page-item disabled"><span class="page-link">...</span></li>';
continue;
}
html+=`<li class="page-item ${i===productPage?'active':''}"><button class="page-link" data-page-number="${i}">${i}</button></li>`;
}

html+=`<li class="page-item ${productPage===totalPages?'disabled':''}"><button class="page-link" data-page-number="${productPage+1}">Next</button></li>`;
$('#productPager').html(html);
}

function openProduct(product){
editingProduct=product||null;
$('#formTitle').text(product?'Edit Product':'Add Product');
$('#pId').val(product?product.dbId:'');
$('#pCode').val(product?product.id:'');
$('#pName').val(product?product.name:'');
$('#pPrice').val(product?product.price:'');
$('#pStock').val(product?Math.min(100,Math.max(0,Number(product.stock)||0)):10);
$('#pImage').val('');
$('#pExistingImage').val(product?product.img:'');

if(product&&product.img){
$('#pImagePreviewWrap').show();
$('#pImagePreview').attr('src','../'+product.img);
if(!$('#pImagePreview')[0].complete)$('#pImagePreview').attr('src',product.img);
$('#pImageName').text(product.img);
}else{
$('#pImagePreviewWrap').hide();
$('#pImagePreview').attr('src','');
$('#pImageName').text('');
}

const category=product?String(product.category||'household').toLowerCase():'household';
$('#pCategory').val(category);
populateSubcategories(category,product?product.sub:'');
$('#pDesc').val(product?product.desc:'');
$('#formMsg').removeClass('err ok').text('');
$('#productDialog')[0].showModal();
}

function closeProduct(){
$('#productDialog')[0].close();
}

function setupOrderFilters(){
if($('#orderFilterBar').length)return;

$('#ordersSec').prepend(`<div class="ad-toolbar" id="orderFilterBar">
<div class="flex-grow-1"><input class="form-control ad-search" id="orderSearch" type="search" placeholder="Search order number, customer, phone or item"><div id="orderCount" class="ad-muted mt-2"></div></div>
<select class="form-select ad-select" id="orderStatusFilter" style="min-width:180px;height:46px">
<option value="all">All Statuses</option>
<option value="Pending">Pending</option>
<option value="Processing">Processing</option>
<option value="Shipped">Shipped</option>
<option value="Delivered">Delivered</option>
<option value="Cancelled">Cancelled</option>
</select>
<button class="ad-btn outline" id="clearOrderFilters" type="button"><i class="bi bi-x-circle"></i>Clear</button>
</div>`);
}

function getFilteredOrders(){
const q=$.trim($('#orderSearch').val()).toLowerCase(),status=$('#orderStatusFilter').val()||'all';

return orders.filter(o=>{
const itemNames=Array.isArray(o.items)?o.items.map(i=>i.name).join(' '):'';
const text=(String(o.orderNumber||'')+' '+String(o.name||'')+' '+String(o.phone||'')+' '+String(o.paymentMethod||'')+' '+itemNames).toLowerCase();
return(!q||text.includes(q))&&(status==='all'||String(o.status||'').toLowerCase()===String(status).toLowerCase());
});
}

function renderOrders(){
setupOrderFilters();

const list=getFilteredOrders();

$('#orderCount').text(list.length===orders.length?orders.length+' orders':list.length+' of '+orders.length+' orders');

if(!$('#mobileOrderCards').length)$('#ordersSec .ad-panel').prepend('<div id="mobileOrderCards" class="ad-mobile-orders"></div>');

if(!list.length){
$('#orderRows').html('<tr><td colspan="8" class="ad-empty">No orders match your filters.</td></tr>');
$('#mobileOrderCards').html('<div class="ad-empty">No orders match your filters.</div>');
return;
}

const statuses=['Pending','Processing','Shipped','Delivered','Cancelled'];

$('#orderRows').html(list.map(o=>{
const items=Array.isArray(o.items)?o.items:[];
const names=items.length?items.map(i=>`<div class="ad-order-name">${esc(i.name)}</div>`).join(''):'<span class="ad-muted">No item details</span>';
const qty=items.length?items.map(i=>`<div class="ad-order-qty">x${Number(i.quantity||1)}</div>`).join(''):'<span class="ad-muted">-</span>';

return`<tr data-order-id="${o.id}">
<td><b>${esc(o.orderNumber)}</b><br><small class="ad-muted">${o.totalItems} ${o.totalItems===1?'item':'items'}</small></td>
<td><div class="ad-order-names">${names}</div></td>
<td><div class="ad-order-quantities">${qty}</div></td>
<td>${esc(o.name||'Customer')}<br><small class="ad-muted">${esc(o.phone||'')}</small></td>
<td>${esc(o.paymentMethod||'')}</td>
<td><b>${peso(o.grandTotal)}</b></td>
<td>${esc(formatDate(o.createdAt))}</td>
<td><select class="form-select ad-select order-status">${statuses.map(s=>`<option value="${s}" ${o.status===s?'selected':''}>${s}</option>`).join('')}</select></td>
</tr>`;
}).join(''));

$('#mobileOrderCards').html(list.map(o=>{
const items=Array.isArray(o.items)?o.items:[];
const mobileItems=items.length?items.map(i=>`<div class="ad-mo-item"><span>${esc(i.name)}</span><b>x${Number(i.quantity||1)}</b></div>`).join(''):'<div class="ad-muted">No item details</div>';

return`<article class="ad-mo-card" data-order-id="${o.id}">
<div class="ad-mo-head"><div><b>${esc(o.orderNumber)}</b><small>${o.totalItems} ${o.totalItems===1?'item':'items'}</small></div></div>
<div class="ad-mo-section"><div class="ad-mo-label">Items</div><div class="ad-mo-items">${mobileItems}</div></div>
<div class="ad-mo-details">
<div class="ad-mo-row"><span>Customer</span><div><b>${esc(o.name||'Customer')}</b><small>${esc(o.phone||'')}</small></div></div>
<div class="ad-mo-row"><span>Payment</span><b>${esc(o.paymentMethod||'')}</b></div>
<div class="ad-mo-row"><span>Total</span><b>${peso(o.grandTotal)}</b></div>
<div class="ad-mo-row"><span>Date</span><b>${esc(formatDate(o.createdAt))}</b></div>
<div class="ad-mo-row"><span>Status</span><select class="form-select ad-select order-status-mobile">${statuses.map(s=>`<option value="${s}" ${o.status===s?'selected':''}>${s}</option>`).join('')}</select></div>
</div>
</article>`;
}).join(''));
}

function renderCustomers(){
if(!customers.length){$('#customerRows').html('<tr><td colspan="5" class="ad-empty">No registered customers yet.</td></tr>');return;}

$('#customerRows').html(customers.map(u=>`<tr>
<td><b>${esc(u.name||u.username)}</b></td>
<td>@${esc(u.username||'')}</td>
<td>${esc(u.email||'')}</td>
<td>${esc(u.contact||'')}</td>
<td>${esc(u.address||'')}</td>
</tr>`).join(''));
}

/* IMAGE PREVIEW */
$('#pImage').on('change',function(){
const file=this.files&&this.files[0];

if(!file){
if(editingProduct&&editingProduct.img){
$('#pImagePreviewWrap').show();
$('#pImagePreview').attr('src',editingProduct.img);
$('#pImageName').text(editingProduct.img);
}else{
$('#pImagePreviewWrap').hide();
$('#pImagePreview').attr('src','');
$('#pImageName').text('');
}
return;
}

const allowed=['image/jpeg','image/png','image/webp','image/gif'];

if(!allowed.includes(file.type)){
this.value='';
$('#pImagePreviewWrap').hide();
$('#formMsg').removeClass('ok').addClass('err').text('Please choose a JPG, PNG, WEBP or GIF image.');
return;
}

if(file.size>5*1024*1024){
this.value='';
$('#pImagePreviewWrap').hide();
$('#formMsg').removeClass('ok').addClass('err').text('Image must be 5MB or smaller.');
return;
}

const reader=new FileReader();

reader.onload=e=>{
$('#pImagePreviewWrap').show();
$('#pImagePreview').attr('src',e.target.result);
$('#pImageName').text(file.name);
$('#formMsg').removeClass('err ok').text('');
};

reader.readAsDataURL(file);
});

/* EVENTS */
$('#pCategory').on('change',function(){populateSubcategories($(this).val(),'');});

$('.ad-nav [data-page]').on('click',function(){go($(this).data('page'));});
$('[data-jump]').on('click',function(){go($(this).data('jump'));});

$(document).on('click','#applySalesRange',renderSalesChart);

$('#productSearch').on('input',function(){productPage=1;renderProducts();});

$(document).on('input','#orderSearch',renderOrders);
$(document).on('change','#orderStatusFilter',renderOrders);

$(document).on('click','#clearOrderFilters',function(){
$('#orderSearch').val('');
$('#orderStatusFilter').val('all');
renderOrders();
});

$('#productPager').on('click','.page-link',function(){
const item=$(this).closest('.page-item');
if(item.hasClass('disabled'))return;
productPage=Number($(this).data('page-number'));
renderProducts();
});

$('#addProduct').on('click',()=>openProduct(null));
$('#closeDialog,#cancelDialog').on('click',closeProduct);

$('#productRows').on('click','[data-stock]',function(){
if($(this).prop('disabled'))return;

const dbId=Number($(this).closest('tr').data('db-id'));
const amount=Number($(this).data('stock'));
const product=products.find(p=>Number(p.dbId)===dbId);

if(!product)return;

const current=Math.min(100,Math.max(0,Number(product.stock)||0));

if(amount>0&&current>=100)return;
if(amount<0&&current<=0)return;

phpPost('php/update_stock.php',{id:dbId,amount});
});

$('#productRows').on('click','[data-edit]',function(){
const dbId=Number($(this).closest('tr').data('db-id'));
const product=products.find(p=>Number(p.dbId)===dbId);
if(product)openProduct(product);
});

$('#productRows').on('click','[data-delete]',function(){
const dbId=Number($(this).closest('tr').data('db-id'));
const product=products.find(p=>Number(p.dbId)===dbId);
if(!product||!confirm('Delete '+product.name+'?'))return;
phpPost('php/delete_product.php',{id:dbId});
});

/* SAVE PRODUCT */
$('#productForm').on('submit',function(e){
const name=$.trim($('#pName').val());
const price=Number($('#pPrice').val());
const stock=Number($('#pStock').val());
const sub=$.trim($('#pSub').val());
const file=$('#pImage')[0].files[0];

if(!name||!sub||!Number.isFinite(price)||price<=0||!Number.isInteger(stock)||stock<0||stock>100){
e.preventDefault();
$('#formMsg').removeClass('ok').addClass('err').text('Please complete all fields. Stock must be from 0 to 100 only.');
return;
}

if(!editingProduct&&!file){
e.preventDefault();
$('#formMsg').removeClass('ok').addClass('err').text('Please choose a product image.');
return;
}

if(file&&file.size>5*1024*1024){
e.preventDefault();
$('#formMsg').removeClass('ok').addClass('err').text('Image must be 5MB or smaller.');
return;
}

if(editingProduct){
this.action='php/update_product.php';
$('#pId').val(editingProduct.dbId);
$('#pExistingImage').val(editingProduct.img||'');
}else{
this.action='php/add_product.php';
$('#pCode').val(makeProductCode(name));
$('#pExistingImage').val('');
}
});

$('#orderRows').on('change','.order-status',function(){
phpPost('php/update_order_status.php',{id:$(this).closest('tr').data('order-id'),status:$(this).val()});
});

$(document).on('change','#mobileOrderCards .order-status-mobile',function(){
phpPost('php/update_order_status.php',{id:$(this).closest('.ad-mo-card').data('order-id'),status:$(this).val()});
});

/* START */
setupProductSubcategory();
prepareResponsiveTables();

const requestedPage=new URLSearchParams(location.search).get('page');
go(titles[requestedPage]?requestedPage:'dashboard');
});