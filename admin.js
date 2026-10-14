$(function(){

const PAGE_SIZE=6;
let productPage=1;
let editingProduct=null;

const products=Array.isArray(window.DB_PRODUCTS)?window.DB_PRODUCTS:[];
const customers=Array.isArray(window.DB_CUSTOMERS)?window.DB_CUSTOMERS:[];
const orders=Array.isArray(window.DB_ORDERS)?window.DB_ORDERS:[];

const titles={
    dashboard:['Dashboard','Quick summary of your SmartTech store.'],
    products:['Products & Stock','Add, edit, delete and manage product inventory.'],
    orders:['Orders','Review orders and update their delivery status.'],
    customers:['Customers','Registered SmartTech customer accounts.']
};

function esc(s){
    return String(s==null?'':s).replace(/[&<>"']/g,c=>({
        '&':'&amp;',
        '<':'&lt;',
        '>':'&gt;',
        '"':'&quot;',
        "'":'&#39;'
    }[c]));
}

function peso(n){
    return '₱'+Number(n||0).toLocaleString('en-PH',{
        minimumFractionDigits:2,
        maximumFractionDigits:2
    });
}

function stockClass(stock){
    if(Number(stock)<=0)return 'out';
    if(Number(stock)<=9)return 'low';
    return 'in';
}

function stockText(stock){
    if(Number(stock)<=0)return 'Out of Stock';
    if(Number(stock)<=9)return 'Low Stock';
    return 'In Stock';
}

function statusClass(status){
    return String(status||'').toLowerCase().replace(/\s+/g,'-');
}

function orderBadge(status){
    return `<span class="ad-status ${statusClass(status)}">${esc(status)}</span>`;
}

function formatDate(value){
    if(!value)return '';

    const date=new Date(String(value).replace(' ','T'));

    if(Number.isNaN(date.getTime()))return value;

    return date.toLocaleString('en-PH',{
        year:'numeric',
        month:'short',
        day:'numeric',
        hour:'numeric',
        minute:'2-digit'
    });
}

function makeProductCode(name){
    let base=String(name)
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g,'-')
        .replace(/^-|-$/g,'')||'product';

    let code=base;
    let number=2;

    while(products.some(p=>p.id===code)){
        code=base+'-'+number++;
    }

    return code;
}

function phpPost(action,values){
    const form=$('<form>',{
        method:'POST',
        action:action
    });

    $.each(values,function(name,value){
        $('<input>',{
            type:'hidden',
            name:name,
            value:value
        }).appendTo(form);
    });

    form.appendTo('body').trigger('submit');
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
    if(page==='orders')renderOrders();
    if(page==='customers')renderCustomers();

    $('html,body').scrollTop(0);
}

function renderDashboard(){
    const delivered=orders.filter(o=>String(o.status).toLowerCase()==='delivered');

    const revenue=delivered.reduce((total,o)=>{
        return total+Number(o.grandTotal||0);
    },0);

    const pending=orders.filter(o=>{
        return ['pending','processing'].includes(
            String(o.status||'Pending').toLowerCase()
        );
    });

    const low=products.filter(p=>Number(p.stock)<=9);

    $('#mRevenue').text(peso(revenue));
    $('#mOrders').text(orders.length);
    $('#mProducts').text(products.length);
    $('#mCustomers').text(customers.length);
    $('#mPending').text(pending.length);
    $('#mLow').text(low.length);

    renderSalesChart(delivered);
    renderLowStock();
    renderRecentOrders();
}

function renderSalesChart(delivered){
    const days=[];

    for(let i=6;i>=0;i--){
        const start=new Date();
        start.setHours(0,0,0,0);
        start.setDate(start.getDate()-i);

        const end=new Date(start);
        end.setHours(23,59,59,999);

        const total=delivered
            .filter(o=>{
                const time=new Date(
                    String(o.createdAt).replace(' ','T')
                ).getTime();

                return time>=start.getTime()&&time<=end.getTime();
            })
            .reduce((sum,o)=>sum+Number(o.grandTotal||0),0);

        days.push({
            label:start.toLocaleDateString('en-PH',{weekday:'short'}),
            total:total
        });
    }

    const max=Math.max(1,...days.map(d=>d.total));

    $('#salesChart').html(days.map(day=>{
        const height=Math.max(4,Math.round(day.total/max*180));

        return`
        <div class="ad-bar-wrap">
            <div
                class="ad-bar"
                title="${esc(day.label)}: ${peso(day.total)}"
                style="height:${height}px"
            ></div>
            <small>${esc(day.label)}</small>
        </div>`;
    }).join(''));
}

function renderLowStock(){
    const lowProducts=products
        .filter(p=>Number(p.stock)<=9)
        .sort((a,b)=>Number(a.stock)-Number(b.stock))
        .slice(0,6);

    if(!lowProducts.length){
        $('#lowStockList').html(
            '<div class="ad-empty">No low-stock products.</div>'
        );
        return;
    }

    $('#lowStockList').html(lowProducts.map(p=>`
    <div class="ad-list-row">
        <div>
            <b>${esc(p.name)}</b>
            <small>${esc(p.category)} · ${esc(p.sub)}</small>
        </div>
        <span class="ad-stock-num">${p.stock}</span>
    </div>
    `).join(''));
}

function renderRecentOrders(){
    const recent=orders.slice(0,6);

    if(!recent.length){
        $('#recentOrders').html(
            '<tr><td colspan="5" class="ad-empty">No orders yet.</td></tr>'
        );
        return;
    }

    $('#recentOrders').html(recent.map(o=>`
    <tr>
        <td><b>${esc(o.orderNumber)}</b></td>
        <td>${esc(o.name||'Customer')}</td>
        <td>${esc(formatDate(o.createdAt))}</td>
        <td><b>${peso(o.grandTotal)}</b></td>
        <td>${orderBadge(o.status)}</td>
    </tr>
    `).join(''));
}

function getFilteredProducts(){
    const search=$.trim($('#productSearch').val()).toLowerCase();

    return products.filter(p=>{
        const text=(p.name+' '+p.id+' '+p.category+' '+p.sub).toLowerCase();
        return !search||text.includes(search);
    });
}

function renderProducts(){
    const filtered=getFilteredProducts();
    const total=filtered.length;
    const totalPages=Math.max(1,Math.ceil(total/PAGE_SIZE));

    if(productPage>totalPages)productPage=totalPages;
    if(productPage<1)productPage=1;

    const start=(productPage-1)*PAGE_SIZE;
    const end=start+PAGE_SIZE;
    const list=filtered.slice(start,end);

    $('#productCount').text(
        total
        ?`Showing ${start+1}–${Math.min(end,total)} of ${total} products`
        :'0 products found'
    );

    if(!list.length){
        $('#productRows').html(
            '<tr><td colspan="6" class="ad-empty">No products found.</td></tr>'
        );
    }else{
        $('#productRows').html(list.map(p=>`
        <tr data-db-id="${p.dbId}">

            <td>
                <div class="ad-prod">
                    <img src="${esc(p.img)}" alt="" onerror="this.style.visibility='hidden'">

                    <div>
                        <b>${esc(p.name)}</b>
                        <small>${esc(p.id)}</small>
                    </div>
                </div>
            </td>

            <td>
                ${esc(p.category)}
                <br>
                <small class="ad-muted">${esc(p.sub)}</small>
            </td>

            <td><b>${peso(p.price)}</b></td>

            <td>
                <div class="ad-stock-control">
                    <button type="button" data-stock="-1">−</button>
                    <span>${p.stock}</span>
                    <button type="button" data-stock="1">+</button>
                </div>
            </td>

            <td>
                <span class="ad-status ${stockClass(p.stock)}">
                    ${stockText(p.stock)}
                </span>
            </td>

            <td>
                <div class="ad-actions">

                    <button class="ad-btn outline sm" data-edit type="button">
                        <i class="bi bi-pencil"></i>
                        Edit
                    </button>

                    <button class="ad-btn danger sm" data-delete type="button">
                        <i class="bi bi-trash"></i>
                        Delete
                    </button>

                </div>
            </td>

        </tr>
        `).join(''));
    }

    renderPager(totalPages);
}

function renderPager(totalPages){
    if(totalPages<=1){
        $('#productPager').empty();
        return;
    }

    let html=`
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

function openProduct(product){
    editingProduct=product||null;

    $('#formTitle').text(product?'Edit Product':'Add Product');
    $('#pId').val(product?product.dbId:'');
    $('#pCode').val(product?product.id:'');
    $('#pName').val(product?product.name:'');
    $('#pPrice').val(product?product.price:'');
    $('#pStock').val(product?product.stock:10);
    $('#pImage').val(product?product.img:'images/');
    $('#pCategory').val(product?product.category:'household');
    $('#pSub').val(product?product.sub:'');
    $('#pDesc').val(product?product.desc:'');

    $('#formMsg').removeClass('err ok').text('');

    $('#productDialog')[0].showModal();
}

function closeProduct(){
    $('#productDialog')[0].close();
}

function renderOrders(){
    if(!orders.length){
        $('#orderRows').html(
            '<tr><td colspan="6" class="ad-empty">No orders yet.</td></tr>'
        );
        return;
    }

    const statuses=[
        'Pending',
        'Processing',
        'Shipped',
        'Delivered',
        'Cancelled'
    ];

    $('#orderRows').html(orders.map(o=>`
    <tr data-order-id="${o.id}">

        <td>
            <b>${esc(o.orderNumber)}</b>
            <br>
            <small class="ad-muted">
                ${o.totalItems} ${o.totalItems===1?'item':'items'}
            </small>
        </td>

        <td>
            ${esc(o.name||'Customer')}
            <br>
            <small class="ad-muted">${esc(o.phone||'')}</small>
        </td>

        <td>${esc(o.paymentMethod||'')}</td>

        <td><b>${peso(o.grandTotal)}</b></td>

        <td>${esc(formatDate(o.createdAt))}</td>

        <td>
            <select class="form-select ad-select order-status">
                ${statuses.map(status=>`
                <option value="${status}" ${o.status===status?'selected':''}>
                    ${status}
                </option>
                `).join('')}
            </select>
        </td>

    </tr>
    `).join(''));
}

function renderCustomers(){
    if(!customers.length){
        $('#customerRows').html(
            '<tr><td colspan="5" class="ad-empty">No registered customers yet.</td></tr>'
        );
        return;
    }

    $('#customerRows').html(customers.map(u=>`
    <tr>
        <td><b>${esc(u.name||u.username)}</b></td>
        <td>@${esc(u.username||'')}</td>
        <td>${esc(u.email||'')}</td>
        <td>${esc(u.contact||'')}</td>
        <td>${esc(u.address||'')}</td>
    </tr>
    `).join(''));
}

/* NAV */
$('.ad-nav [data-page]').on('click',function(){
    go($(this).data('page'));
});

$('[data-jump]').on('click',function(){
    go($(this).data('jump'));
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
});

/* PRODUCT DIALOG */
$('#addProduct').on('click',function(){
    openProduct(null);
});

$('#closeDialog,#cancelDialog').on('click',function(){
    closeProduct();
});

/* STOCK */
$('#productRows').on('click','[data-stock]',function(){
    const dbId=$(this).closest('tr').data('db-id');
    const amount=Number($(this).data('stock'));

    phpPost('php/update_stock.php',{
        id:dbId,
        amount:amount
    });
});

/* EDIT */
$('#productRows').on('click','[data-edit]',function(){
    const dbId=Number($(this).closest('tr').data('db-id'));
    const product=products.find(p=>Number(p.dbId)===dbId);

    if(product)openProduct(product);
});

/* DELETE */
$('#productRows').on('click','[data-delete]',function(){
    const dbId=Number($(this).closest('tr').data('db-id'));
    const product=products.find(p=>Number(p.dbId)===dbId);

    if(!product)return;
    if(!confirm('Delete '+product.name+'?'))return;

    phpPost('php/delete_product.php',{
        id:dbId
    });
});

/* SAVE PRODUCT */
$('#productForm').on('submit',function(e){
    const name=$.trim($('#pName').val());
    const price=Number($('#pPrice').val());
    const stock=Number($('#pStock').val());
    const image=$.trim($('#pImage').val());
    const sub=$.trim($('#pSub').val());

    if(
        !name||
        !image||
        !sub||
        !Number.isFinite(price)||
        price<=0||
        !Number.isFinite(stock)||
        stock<0
    ){
        e.preventDefault();

        $('#formMsg')
            .removeClass('ok')
            .addClass('err')
            .text('Please complete the product name, price, stock, image and subcategory.');

        return;
    }

    if(editingProduct){
        this.action='php/update_product.php';
        $('#pId').val(editingProduct.dbId);
    }else{
        this.action='php/add_product.php';
        $('#pCode').val(makeProductCode(name));
    }
});

/* ORDER STATUS */
$('#orderRows').on('change','.order-status',function(){
    const orderId=$(this).closest('tr').data('order-id');
    const status=$(this).val();

    phpPost('php/update_order_status.php',{
        id:orderId,
        status:status
    });
});

/* START */
const requestedPage=new URLSearchParams(location.search).get('page');

go(
    titles[requestedPage]
    ?requestedPage
    :'dashboard'
);

});