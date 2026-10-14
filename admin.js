$(function(){

if(sessionStorage.getItem('st_admin_session')!=='1'){
location.replace('login.html');
return;
}

const PAGE_SIZE=6;
let productPage=1;
let editingId=null;

const titles={
dashboard:['Dashboard','Quick summary of your SmartTech store.'],
products:['Products & Stock','Add, edit, delete and manage product inventory.'],
orders:['Orders','Review orders and update their delivery status.'],
customers:['Customers','Registered SmartTech customer accounts.']
};

function esc(s){
return String(s==null?'':s).replace(/[&<>"']/g,function(c){
return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
});
}

function peso(n){
return '₱'+Number(n||0).toLocaleString('en-PH',{maximumFractionDigits:2});
}

function read(key,fallback){
try{
const value=JSON.parse(localStorage.getItem(key));
return value==null?fallback:value;
}catch(e){
return fallback;
}
}

function write(key,value){
localStorage.setItem(key,JSON.stringify(value));
}

function getOrders(){
let list=read('st_orders',[]);
const last=read('lastOrder',null);

if(last&&last.orderNumber&&!list.some(function(o){
return o.orderNumber===last.orderNumber;
})){
list.push({
...last,
status:last.status||'Pending',
orderTs:last.orderTs||Date.now()
});

write('st_orders',list);
}

return list;
}

function getCustomers(){
const list=[];

for(let i=0;i<localStorage.length;i++){

const key=localStorage.key(i);

if(!key||!key.startsWith('st_user:'))continue;

const user=read(key,null);

if(user)list.push(user);
}

return list.sort(function(a,b){
return String(a.name||a.username).localeCompare(String(b.name||b.username));
});
}

function statusClass(status){
return String(status||'').toLowerCase().replace(/\s+/g,'-');
}

function stockClass(stock){
if(stock<=0)return 'out';
if(stock<=9)return 'low';
return 'in';
}

function orderBadge(status){
return '<span class="ad-status '+statusClass(status)+'">'+esc(status)+'</span>';
}

function makeId(name){

const base=String(name)
.toLowerCase()
.trim()
.replace(/[^a-z0-9]+/g,'-')
.replace(/^-|-$/g,'')||'product';

let id=base;
let number=2;

while(STCatalog.find(id)){
id=base+'-'+number++;
}

return id;
}

function go(page){

$('.ad-nav [data-page]').removeClass('active');
$('.ad-nav [data-page="'+page+'"]').addClass('active');

$('.ad-sec').prop('hidden',true);
$('#'+page+'Sec').prop('hidden',false);

$('#pageTitle').text(titles[page][0]);
$('#pageSub').text(titles[page][1]);

if(page==='dashboard')renderDashboard();
if(page==='products')renderProducts();
if(page==='orders')renderOrders();
if(page==='customers')renderCustomers();

$('html,body').scrollTop(0);
}

function renderDashboard(){

const os=getOrders();
const ps=STCatalog.all();
const cs=getCustomers();

const delivered=os.filter(function(order){
return String(order.status||'').toLowerCase()==='delivered';
});

const revenue=delivered.reduce(function(total,order){
return total+Number(order.grandTotal||0);
},0);

$('#mRevenue').text(peso(revenue));
$('#mOrders').text(os.length);
$('#mProducts').text(ps.length);
$('#mCustomers').text(cs.length);

const pending=os.filter(function(order){
return ['pending','processing'].includes(
String(order.status||'Pending').toLowerCase()
);
});

$('#mPending').text(pending.length);

const low=ps.filter(function(product){
return Number(product.stock)<=9;
});

$('#mLow').text(low.length);

const days=[];

for(let i=6;i>=0;i--){

const start=new Date();

start.setHours(0,0,0,0);
start.setDate(start.getDate()-i);

const end=new Date(start);
end.setHours(23,59,59,999);

const total=delivered
.filter(function(order){

const time=Number(order.orderTs||0);

return time>=start.getTime()&&time<=end.getTime();

})
.reduce(function(sum,order){
return sum+Number(order.grandTotal||0);
},0);

days.push({
label:start.toLocaleDateString('en-PH',{weekday:'short'}),
total:total
});
}

const max=Math.max(1,...days.map(function(day){
return day.total;
}));

$('#salesChart').html(

days.map(function(day){

const height=Math.max(
4,
Math.round(day.total/max*180)
);

return `
<div class="ad-bar-wrap">
<div class="ad-bar"
title="${esc(day.label)}: ${peso(day.total)}"
style="height:${height}px"></div>
<small>${esc(day.label)}</small>
</div>`;

}).join('')

);

const lowProducts=ps
.filter(function(product){
return Number(product.stock)<=9;
})
.sort(function(a,b){
return a.stock-b.stock;
})
.slice(0,6);

if(!lowProducts.length){

$('#lowStockList').html(
'<div class="ad-empty">No low-stock products.</div>'
);

}else{

$('#lowStockList').html(

lowProducts.map(function(product){

return `
<div class="ad-list-row">
<div>
<b>${esc(product.name)}</b>
<small>${esc(product.category)} · ${esc(product.sub)}</small>
</div>
<span class="ad-stock-num">${product.stock}</span>
</div>`;

}).join('')

);

}

const recent=[...os]
.sort(function(a,b){
return Number(b.orderTs||0)-Number(a.orderTs||0);
})
.slice(0,6);

if(!recent.length){

$('#recentOrders').html(
'<tr><td colspan="5" class="ad-empty">No orders yet.</td></tr>'
);

}else{

$('#recentOrders').html(

recent.map(function(order){

return `
<tr>
<td><b>${esc(order.orderNumber)}</b></td>
<td>${esc(order.name||order.user||'Customer')}</td>
<td>${esc(order.orderDate||'')}</td>
<td><b>${peso(order.grandTotal)}</b></td>
<td>${orderBadge(order.status||'Pending')}</td>
</tr>`;

}).join('')

);

}
}

function getFilteredProducts(){

const search=$.trim($('#productSearch').val()).toLowerCase();

return STCatalog.all().filter(function(product){

const text=(
product.name+' '+
product.id+' '+
product.category+' '+
product.sub
).toLowerCase();

return !search||text.includes(search);
});
}

function renderProducts(){

const products=getFilteredProducts();

const total=products.length;
const totalPages=Math.max(1,Math.ceil(total/PAGE_SIZE));

if(productPage>totalPages)productPage=totalPages;
if(productPage<1)productPage=1;

const start=(productPage-1)*PAGE_SIZE;
const end=start+PAGE_SIZE;

const list=products.slice(start,end);

if(total){

$('#productCount').text(
'Showing '+(start+1)+'–'+Math.min(end,total)+
' of '+total+' products'
);

}else{

$('#productCount').text('0 products found');
}

if(!list.length){

$('#productRows').html(
'<tr><td colspan="6" class="ad-empty">No products found.</td></tr>'
);

}else{

$('#productRows').html(

list.map(function(product){

return `
<tr data-id="${esc(product.id)}">

<td>
<div class="ad-prod">
<img src="${esc(product.img)}" alt=""
onerror="this.style.visibility='hidden'">
<div>
<b>${esc(product.name)}</b>
<small>${esc(product.id)}</small>
</div>
</div>
</td>

<td>
${esc(product.category)}
<br>
<small class="ad-muted">${esc(product.sub)}</small>
</td>

<td><b>${peso(product.price)}</b></td>

<td>
<div class="ad-stock-control">
<button type="button" data-stock="-1">−</button>
<span>${product.stock}</span>
<button type="button" data-stock="1">+</button>
</div>
</td>

<td>
<span class="ad-status ${stockClass(product.stock)}">
${esc(STCatalog.status(product.stock))}
</span>
</td>

<td>
<div class="ad-actions">

<button class="ad-btn outline sm" data-edit type="button">
<i class="bi bi-pencil"></i>Edit
</button>

<button class="ad-btn danger sm" data-delete type="button">
<i class="bi bi-trash"></i>Delete
</button>

</div>
</td>

</tr>`;

}).join('')

);

}

renderPager(totalPages);
}

function renderPager(totalPages){

if(totalPages<=1){
$('#productPager').empty();
return;
}

let html='';

html+=`
<li class="page-item ${productPage===1?'disabled':''}">
<button class="page-link" data-page-number="${productPage-1}">
Previous
</button>
</li>`;

for(let i=1;i<=totalPages;i++){

if(
totalPages>7&&
i!==1&&
i!==totalPages&&
Math.abs(i-productPage)>1
){

if(i===2&&productPage>3){
html+='<li class="page-item disabled"><span class="page-link">...</span></li>';
}

if(i===totalPages-1&&productPage<totalPages-2){
html+='<li class="page-item disabled"><span class="page-link">...</span></li>';
}

continue;
}

html+=`
<li class="page-item ${i===productPage?'active':''}">
<button class="page-link" data-page-number="${i}">
${i}
</button>
</li>`;

}

html+=`
<li class="page-item ${productPage===totalPages?'disabled':''}">
<button class="page-link" data-page-number="${productPage+1}">
Next
</button>
</li>`;

$('#productPager').html(html);
}

function renderOrders(){

const list=[...getOrders()].sort(function(a,b){
return Number(b.orderTs||0)-Number(a.orderTs||0);
});

if(!list.length){

$('#orderRows').html(
'<tr><td colspan="6" class="ad-empty">No orders yet.</td></tr>'
);

return;
}

$('#orderRows').html(

list.map(function(order){

const quantity=Array.isArray(order.cartItems)
?order.cartItems.reduce(function(total,item){
return total+Number(item.quantity||0);
},0)
:0;

const statuses=['Pending','Processing','Shipped','Delivered'];

return `
<tr data-order="${esc(order.orderNumber)}">

<td>
<b>${esc(order.orderNumber)}</b>
<br>
<small class="ad-muted">${quantity} items</small>
</td>

<td>
${esc(order.name||order.user||'Customer')}
<br>
<small class="ad-muted">${esc(order.phone||'')}</small>
</td>

<td>${esc(order.paymentMethod||'')}</td>

<td><b>${peso(order.grandTotal)}</b></td>

<td>${esc(order.orderDate||'')}</td>

<td>
<select class="form-select ad-select order-status">

${statuses.map(function(status){

return `
<option ${String(order.status||'Pending')===status?'selected':''}>
${status}
</option>`;

}).join('')}

</select>
</td>

</tr>`;

}).join('')

);
}

function renderCustomers(){

const list=getCustomers();

if(!list.length){

$('#customerRows').html(
'<tr><td colspan="5" class="ad-empty">No registered customers yet.</td></tr>'
);

return;
}

$('#customerRows').html(

list.map(function(user){

return `
<tr>
<td><b>${esc(user.name||user.username)}</b></td>
<td>@${esc(user.username||'')}</td>
<td>${esc(user.gmail||'')}</td>
<td>${esc(user.contact||'')}</td>
<td>${esc(user.address||'')}</td>
</tr>`;

}).join('')

);
}

function openProduct(product){

editingId=product?product.id:null;

$('#formTitle').text(
product?'Edit Product':'Add Product'
);

$('#pName').val(product?product.name:'');
$('#pPrice').val(product?product.price:'');
$('#pStock').val(product?product.stock:20);
$('#pImage').val(product?product.img:'images/');
$('#pCategory').val(product?product.category:'household');
$('#pSub').val(product?product.sub:'');
$('#pDesc').val(product?product.desc:'');

$('#formMsg')
.removeClass('err ok')
.text('');

$('#productDialog')[0].showModal();
}

function closeProduct(){
$('#productDialog')[0].close();
}

/* NAVIGATION */
$('.ad-nav [data-page]').on('click',function(){
go($(this).data('page'));
});

$('[data-jump]').on('click',function(){
go($(this).data('jump'));
});

/* LOGOUT */
$('#adminLogout').on('click',function(){

sessionStorage.removeItem('st_admin_session');

location.href='index.html';
});

/* SEARCH */
$('#productSearch').on('input',function(){

productPage=1;

renderProducts();
});

/* PAGINATION */
$('#productPager').on('click','.page-link',function(){

const item=$(this).closest('.page-item');

if(item.hasClass('disabled'))return;

productPage=Number($(this).data('page-number'));

renderProducts();

$('html,body').animate({
scrollTop:0
},250);
});

/* ADD PRODUCT */
$('#addProduct').on('click',function(){
openProduct(null);
});

$('#closeDialog,#cancelDialog').on('click',function(){
closeProduct();
});

/* PRODUCT ACTIONS */
$('#productRows').on('click','[data-stock]',function(){

const id=$(this).closest('tr').data('id');

STCatalog.adjustStock(
id,
Number($(this).data('stock'))
);

renderProducts();
renderDashboard();
});

$('#productRows').on('click','[data-edit]',function(){

const id=$(this).closest('tr').data('id');

const product=STCatalog.find(id);

if(product)openProduct(product);
});

$('#productRows').on('click','[data-delete]',function(){

const id=$(this).closest('tr').data('id');

const product=STCatalog.find(id);

if(!product)return;

if(!confirm(
'Delete '+product.name+
'? This product will also disappear from the customer shop.'
))return;

STCatalog.remove(id);

renderProducts();
renderDashboard();
});

/* PRODUCT FORM */
$('#productForm').on('submit',function(e){

e.preventDefault();

const data={
name:$.trim($('#pName').val()),
price:Number($('#pPrice').val()),
stock:Number($('#pStock').val()),
img:$.trim($('#pImage').val()),
category:$('#pCategory').val(),
sub:$.trim($('#pSub').val()),
desc:$.trim($('#pDesc').val())
};

if(
!data.name||
!data.img||
!data.sub||
!Number.isFinite(data.price)||
data.price<0||
!Number.isFinite(data.stock)||
data.stock<0
){

$('#formMsg')
.removeClass('ok')
.addClass('err')
.text('Please complete the product name, price, stock, image and subcategory.');

return;
}

try{

if(editingId){

STCatalog.update(
editingId,
data
);

}else{

STCatalog.add({
id:makeId(data.name),
...data
});

}

closeProduct();

renderProducts();
renderDashboard();

}catch(error){

$('#formMsg')
.removeClass('ok')
.addClass('err')
.text(error.message||'Could not save the product.');

}
});

/* ORDER STATUS */
$('#orderRows').on('change','.order-status',function(){

const row=$(this).closest('tr');

const orderNumber=row.data('order');

const list=getOrders();

const order=list.find(function(item){
return item.orderNumber===orderNumber;
});

if(!order)return;

order.status=$(this).val();

write('st_orders',list);

const last=read('lastOrder',null);

if(last&&last.orderNumber===order.orderNumber){

last.status=order.status;

write('lastOrder',last);
}

renderDashboard();
});

/* UPDATE WHEN STORAGE CHANGES */
$(window).on('storage',function(){

renderDashboard();
renderProducts();
renderOrders();
renderCustomers();
});

$(window).on('st:catalog-changed',function(){

renderDashboard();
renderProducts();
});

/* START */
go('dashboard');

});