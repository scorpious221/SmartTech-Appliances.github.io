$(function(){

const PAGE_SIZE=6;
let productPage=1;
let editingProduct=null;

const products=Array.isArray(window.DB_PRODUCTS)?window.DB_PRODUCTS:[];
const promos=Array.isArray(window.DB_PROMOS)?window.DB_PROMOS:[];
const bundles=Array.isArray(window.DB_BUNDLES)?window.DB_BUNDLES:[];
const homeCategories=Array.isArray(window.DB_HOME_CATEGORIES)?window.DB_HOME_CATEGORIES:[];
const deal=window.DB_DEAL||null;
const homeDemoVideo=window.DB_DEMO_VIDEO||null;
const vouchers=Array.isArray(window.DB_VOUCHERS)?window.DB_VOUCHERS:[];
const productCategories=Array.isArray(window.DB_PRODUCT_CATEGORIES)?window.DB_PRODUCT_CATEGORIES:[];
const customers=Array.isArray(window.DB_CUSTOMERS)?window.DB_CUSTOMERS:[];
const orders=Array.isArray(window.DB_ORDERS)?window.DB_ORDERS:[];
const refundRequests=Array.isArray(window.DB_REFUND_REQUESTS)?window.DB_REFUND_REQUESTS:[];
const accountDeletionRequests=Array.isArray(window.DB_ACCOUNT_DELETION_REQUESTS)?window.DB_ACCOUNT_DELETION_REQUESTS:[];
const appFeedback=Array.isArray(window.DB_APP_FEEDBACK)?window.DB_APP_FEEDBACK:[];

const titles={
    dashboard:['Dashboard','Quick summary of your SmartTech store.'],
    products:['Products & Stock','Add, edit, delete and manage product inventory.'],
    storefront:['Storefront','Manage homepage promotions, bundles, and category tiles.'],
    vouchers:['Vouchers','Create and manage checkout voucher codes and rules.'],
    orders:['Orders','Review orders and update their delivery status.'],
    refunds:['Returns & refunds','Review return and refund requests from customers.'],
    requests:['Account requests','Review account deletion requests and customer app ratings.'],
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

    $('<input>',{
        type:'hidden',
        name:'csrf_token',
        value:window.ADMIN_CSRF_TOKEN||''
    }).appendTo(form);

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
    if(page==='storefront')renderStorefront();
    if(page==='vouchers')renderVouchers();
    if(page==='orders')renderOrders();
    if(page==='refunds')renderRefundRequests();
    if(page==='requests')renderAccountRequests();
    if(page==='customers')renderCustomers();

    $('html,body').scrollTop(0);
}

function renderDashboard(){
    const delivered=orders.filter(o=>['delivered','completed'].includes(String(o.status).toLowerCase()));

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
    const compactPeso=value=>Number(value).toLocaleString('en-PH',{
        style:'currency',
        currency:'PHP',
        notation:'compact',
        maximumFractionDigits:1
    });

    $('#salesChart').html(days.map(day=>{
        const height=day.total>0?Math.max(3,day.total/max*100):0;

        return`
        <div class="ad-bar-wrap">
            <small class="ad-bar-value">${day.total>0?esc(compactPeso(day.total)):'—'}</small>
            <div class="ad-bar-track">
                <div
                    class="ad-bar"
                    role="img"
                    aria-label="${esc(day.label)} sales ${peso(day.total)}"
                    title="${esc(day.label)}: ${peso(day.total)}"
                    style="height:${height}%"
                ></div>
            </div>
            <small class="ad-bar-day">${esc(day.label)}</small>
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
        <td>
            <b>${peso(o.grandTotal)}</b>
            ${Number(o.discountAmount)>0?`<br><small class="ad-muted">${esc(o.voucherCode||'Voucher')} · −${peso(o.discountAmount)}</small>`:''}
        </td>
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
                        ${p.salePrice!==null||p.isBestseller?`<div class="ad-prod-labels">
                            ${p.salePrice!==null?`<span class="ad-status low">Sale · ${peso(p.salePrice)}</span>`:''}
                            ${p.isBestseller?'<span class="ad-status in">Bestseller</span>':''}
                        </div>`:''}
                    </div>
                </div>
            </td>

            <td>
                ${esc(p.category)}
                <br>
                <small class="ad-muted">${esc(p.sub)}</small>
            </td>

            <td>${p.salePrice!==null?`<b>${peso(p.salePrice)}</b><br><del class="ad-muted">${peso(p.price)}</del>`:`<b>${peso(p.price)}</b>`}</td>

            <td>
                <div class="ad-stock-control">
                    <button type="button" data-stock="-1">−</button>
                    <span>${p.stock}</span>
                    <button type="button" data-stock="1" ${Number(p.stock)>=100?'disabled':''}>+</button>
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
    $('#pSalePrice').val(product&&product.salePrice!==null?product.salePrice:'');
    $('#pOnSale').prop('checked',Boolean(product&&product.salePrice!==null));
    $('#pSalePrice').prop('disabled',!(product&&product.salePrice!==null));
    $('#pBestseller').prop('checked',Boolean(product&&product.isBestseller));
    $('.ad-label-chip').each(function(){
        $(this).toggleClass('is-checked',$(this).find('input').is(':checked'));
    });
    $('#pStock').val(product?product.stock:10);
    $('#pImage').val('');
    $('#pCategory').val(product?product.category:'household');
    populateProductSubcategories($('#pCategory').val(),product?product.sub:'');
    $('#pDesc').val(product?product.desc:'');
    $('#existingProductMedia').html(product?`
        <div class="ad-media-preview">
            <b>Current main image</b>
            <label><img src="${esc(product.img)}" alt=""><span>Keep as the main image</span></label>
            ${product.media&&product.media.length?`<b>Additional media</b>${product.media.map(media=>`
                <label class="ad-media-item">
                    <input type="checkbox" name="remove_media[]" value="${Number(media.id)}">
                    ${media.type==='video'?'<i class="bi bi-play-circle"></i>':'<img src="'+esc(media.path)+'" alt="">'}
                    <span>${media.type==='video'?'Video':'Image'} · select to remove</span>
                </label>
            `).join('')}`:''}
        </div>
    `:'<p class="ad-note">Upload a main image to create this product.</p>');

    $('#formMsg').removeClass('err ok').text('');

    $('#productDialog')[0].showModal();
}

function populateProductSubcategories(category,selected=''){
    const values=[...new Set(products
        .filter(product=>product.category===category)
        .map(product=>String(product.sub||'').trim())
        .filter(Boolean))];
    if(selected&&!values.includes(selected))values.push(selected);
    const options=values.sort().map(value=>`<option value="${esc(value)}">${esc(value)}</option>`).join('');
    $('#pSub')
        .html(`<option value="">${category?'Choose a subcategory':'Choose a category first'}</option>${options}`)
        .prop('disabled',!category)
        .val(selected||'');
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
        'Completed',
        'Cancelled'
    ];

    $('#orderRows').html(orders.map(o=>`
    <tr data-order-id="${o.id}" class="${o.status==='Cancelled'?'order-cancelled':''}">

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
            ${o.cancellationReason?`<br><small class="ad-muted">Cancellation: ${esc(o.cancellationReason)}${o.cancellationDetails?' — '+esc(o.cancellationDetails):''}</small>`:''}
        </td>

        <td>${esc(o.paymentMethod||'')}</td>

        <td><b>${peso(o.grandTotal)}</b></td>

        <td>${esc(formatDate(o.createdAt))}</td>

        <td>
            <div class="order-status-editor">
                <select class="form-select ad-select order-status" data-original-status="${esc(o.status)}" ${['Cancelled','Completed'].includes(o.status)?'disabled':''}>
                    ${statuses.map(status=>`
                    <option value="${status}" ${o.status===status?'selected':''} ${status==='Completed'||(status==='Cancelled'&&!['Pending','Processing'].includes(o.status))?'disabled':''}>
                        ${status}
                    </option>
                    `).join('')}
                </select>
                <div class="order-cancel-fields" hidden>
                    <label class="visually-hidden" for="cancelReason${Number(o.id)}">Cancellation reason</label>
                    <select class="form-select ad-select order-cancel-reason" id="cancelReason${Number(o.id)}">
                        <option value="">Choose cancellation reason</option>
                        <option>Customer requested cancellation</option>
                        <option>Unable to fulfill order</option>
                        <option>Payment issue</option>
                        <option>Item unavailable</option>
                        <option>Other</option>
                    </select>
                    <textarea class="form-control order-cancel-details" rows="2" maxlength="500" placeholder="Explain the reason for cancellation" hidden></textarea>
                </div>
                <button class="ad-btn sm order-save" type="button" hidden>Save</button>
                <small class="order-status-message" role="alert" hidden></small>
            </div>
        </td>

    </tr>
    `).join(''));
}

function renderRefundRequests(){
    if(!refundRequests.length){
        $('#refundRows').html('<tr><td colspan="6" class="ad-empty">No return/refund requests.</td></tr>');
        return;
    }

    $('#refundRows').html(refundRequests.map(request=>`
    <tr data-refund-id="${Number(request.id)}">
        <td><b>${esc(request.order_number)}</b><br><small>${esc(request.product_name)} × ${Number(request.quantity)} · ${peso(Number(request.price)*Number(request.quantity))}</small></td>
        <td>${esc(request.full_name)}<br><small>${esc(request.email)}</small></td>
        <td><b>${esc(request.reason)}</b><br><small>${esc(request.details)}</small></td>
        <td>${esc(formatDate(request.requested_at))}</td>
        <td>${orderBadge(request.status)}${request.admin_note?`<br><small>${esc(request.admin_note)}</small>`:''}</td>
        <td>
            ${request.status==='Pending'?`
                <textarea class="form-control mb-2 refund-note" rows="2" maxlength="1000" placeholder="Optional note to customer"></textarea>
                <div class="d-flex gap-2">
                    <button class="ad-btn sm" type="button" data-refund-status="Approved">Approve request</button>
                    <button class="ad-btn danger sm" type="button" data-refund-status="Rejected">Reject</button>
                </div>
            `:'—'}
        </td>
    </tr>
    `).join(''));
}

function renderAccountRequests(){
    if(!accountDeletionRequests.length){
        $('#deletionRequestRows').html('<tr><td colspan="4" class="ad-empty">No account deletion requests.</td></tr>');
    }else{
        $('#deletionRequestRows').html(accountDeletionRequests.map(request=>`
        <tr data-account-request-id="${Number(request.id)}">
            <td><b>${esc(request.full_name||request.username)}</b><br><small>@${esc(request.username)} · ${esc(request.email)}</small></td>
            <td>${esc(formatDate(request.requested_at))}</td>
            <td>${orderBadge(request.status)}${request.admin_note?`<br><small>${esc(request.admin_note)}</small>`:''}</td>
            <td>
                ${request.status==='Pending'?`
                    <textarea class="form-control mb-2 account-request-note" rows="2" maxlength="1000" placeholder="Optional internal note"></textarea>
                    <div class="d-flex gap-2">
                        <button class="ad-btn sm" type="button" data-account-request-status="Reviewed">Mark reviewed</button>
                        <button class="ad-btn danger sm" type="button" data-account-request-status="Declined">Decline</button>
                    </div>
                `:'—'}
            </td>
        </tr>
        `).join(''));
    }

    if(!appFeedback.length){
        $('#appFeedbackRows').html('<tr><td colspan="4" class="ad-empty">No app ratings yet.</td></tr>');
    }else{
        $('#appFeedbackRows').html(appFeedback.map(feedback=>`
        <tr>
            <td><b>${esc(feedback.full_name||feedback.username)}</b><br><small>${esc(feedback.email)}</small></td>
            <td>${'★'.repeat(Math.min(5,Number(feedback.rating)||0))}${'☆'.repeat(5-Math.min(5,Number(feedback.rating)||0))}</td>
            <td>${esc(feedback.comment||'—')}</td>
            <td>${esc(formatDate(feedback.updated_at))}</td>
        </tr>
        `).join(''));
    }
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

function renderStorefront(){
    const list=(items,type,titleFor,detailFor,imageFor)=>items.length?items.map(item=>`
        <div class="ad-list-row">
            <div class="ad-content-row">
                <img src="${esc(imageFor(item))}" alt="">
                <div><b>${esc(titleFor(item))}</b><small>${esc(detailFor(item))}</small></div>
            </div>
            <div class="ad-actions">
                <span class="ad-status ${Number(item.active)?'in':'out'}">${Number(item.active)?'Published':'Hidden'}</span>
                <button class="ad-btn outline sm" type="button" data-content-edit="${type}" data-content-id="${Number(item.id)}">Edit</button>
                <button class="ad-btn danger sm" type="button" data-content-delete="${type}" data-content-id="${Number(item.id)}">Delete</button>
            </div>
        </div>
    `).join(''):'<div class="ad-empty">No content added yet.</div>';

    $('#promoRows').html(list(promos,'promo',item=>item.title,item=>item.button_label+' · '+(item.product_code||item.link_url),item=>item.image));
    $('#bundleRows').html(list(bundles,'bundle',item=>item.title,(item)=>(item.product_codes||[]).join(', ')+' · '+peso(item.price),item=>item.image));
    $('#homeCategoryRows').html(list(homeCategories,'category',item=>item.title,(item)=>item.category+(item.subcategory?' · '+item.subcategory:''),item=>item.image));
    $('#demoVideoRow').html(homeDemoVideo?`
        <div class="ad-list-row">
            <div class="ad-content-row">
                <video class="ad-demo-video-preview" controls preload="metadata" aria-label="Current homepage demo video">
                    <source src="${esc(homeDemoVideo.video_path)}">
                </video>
                <div><b>Current homepage demo</b><small>${esc(homeDemoVideo.video_path)}</small></div>
            </div>
        </div>
    `:'<div class="ad-empty">Homepage demo video is not configured.</div>');
    $('#dealRow').html(deal?`
        <div class="ad-list-row">
            <div class="ad-content-row"><img src="${esc(deal.image||'')}" alt=""><div><b>${esc(deal.title)}</b><small>Product: ${esc(deal.product_code)} · Comparison price ${peso(deal.compare_at_price)}${deal.ends_at?' · Ends '+esc(formatDate(deal.ends_at)):''}</small></div></div>
            <span class="ad-status ${Number(deal.active)?'in':'out'}">${Number(deal.active)?'Published':'Hidden'}</span>
        </div>
    `:'<div class="ad-empty">Deal record is not configured.</div>');
}

function renderVouchers(){
    if(!vouchers.length){
        $('#voucherRows').html('<tr><td colspan="6" class="ad-empty">No vouchers created.</td></tr>');
        return;
    }
    $('#voucherRows').html(vouchers.map(voucher=>{
        const scope=[
            (voucher.eligible_products||[]).length?(voucher.eligible_products||[]).join(', '):'',
            (voucher.eligible_categories||[]).length?(voucher.eligible_categories||[]).join(', '):'',
            (voucher.required_product_sets||[]).length?'Complete product set':''
        ].filter(Boolean).join(' · ')||'All products';
        const offer=voucher.discount_type==='percent'
            ?Number(voucher.discount_value)+'%'+(voucher.discount_cap?' · max '+peso(voucher.discount_cap):'')
            :peso(voucher.discount_value)+' off';
        const dates=[voucher.starts_at,voucher.ends_at].filter(Boolean).map(formatDate).join(' – ')||'No expiry';
        const limits=[
            voucher.total_usage_limit?'total '+voucher.total_usage_limit:'',
            voucher.per_customer_limit?'per customer '+voucher.per_customer_limit:''
        ].filter(Boolean).join(' · ')||'No usage limit';
        return`
        <tr>
            <td><b>${esc(voucher.code)}</b></td>
            <td>${esc(voucher.label)}<br><small class="ad-muted">${esc(offer)} · Min ${peso(voucher.minimum_spend)}</small></td>
            <td>${esc(scope)}${Number(voucher.first_order_only)?'<br><small class="ad-muted">First order only</small>':''}</td>
            <td>${esc(dates)}<br><small class="ad-muted">${esc(limits)}</small></td>
            <td><span class="ad-status ${Number(voucher.active)?'in':'out'}">${Number(voucher.active)?'Active':'Inactive'}</span></td>
            <td><div class="ad-actions"><button class="ad-btn outline sm" type="button" data-content-edit="voucher" data-content-id="${Number(voucher.id)}">Edit</button><button class="ad-btn danger sm" type="button" data-content-delete="voucher" data-content-id="${Number(voucher.id)}">Delete</button></div></td>
        </tr>`;
    }).join(''));
}

function optionList(values,selected=[]){
    return values.map(value=>{
        const raw=typeof value==='string'?value:value.value;
        const label=typeof value==='string'?value:value.label;
        return`<option value="${esc(raw)}" ${selected.includes(raw)?'selected':''}>${esc(label)}</option>`;
    }).join('');
}

function updateBundlePricing(){
    const selectedCodes=$('#contentFields .bundle-product-checkbox:checked').map((index,checkbox)=>checkbox.value).get();
    const originalCents=selectedCodes.reduce((total,code)=>{
        const product=products.find(entry=>entry.id===code);
        return total+(product?Math.round((Number(product.price)||0)*100):0);
    },0);
    const preset=$('#contentFields .bundle-discount-preset').val();
    const discountPercent=preset==='custom'
        ?Number($('#contentFields .bundle-discount-custom').val())
        :Number(preset);
    const validDiscount=Number.isFinite(discountPercent)?Math.min(99.99999999,Math.max(0,discountPercent)):0;
    const bundleCents=Math.round(originalCents*(100-validDiscount)/100);

    $('#contentFields .bundle-original-price').val((originalCents/100).toFixed(2));
    $('#contentFields .bundle-price').val((bundleCents/100).toFixed(2));
    $('#contentFields [name="discount_percent"]').val(validDiscount.toFixed(8));
    $('#contentFields .bundle-discount-custom')
        .prop('disabled',preset!=='custom')
        .prop('required',preset==='custom');
    $('#contentFields .bundle-savings').text(`Save ${peso((originalCents-bundleCents)/100)} (${validDiscount.toFixed(2).replace(/\.?0+$/,'')}%)`);
}

function openContentEditor(type,item=null){
    const isNew=!item;
    const fields=[];
    const inputPlaceholders={
        title:'Enter a title',
        button_label:'e.g. Shop now',
        image_alt:'Describe the image for screen readers',
        link_url:'e.g. products.php',
        sort_order:'e.g. 1',
        code:'e.g. SMART10',
        label:'e.g. 10% off your order',
        compare_at_price:'e.g. 0.00',
        discount_value:'e.g. 10',
        minimum_spend:'e.g. 2000.00',
        discount_cap:'e.g. 500.00',
        total_usage_limit:'e.g. 100',
        per_customer_limit:'e.g. 1'
    };
    const areaPlaceholders={
        description:'Write a short description',
        bundle_sets:'One set per line, product codes separated by commas'
    };
    const field=(label,name,control,full=false)=>fields.push(`<label class="field${full?' full':''}">${label}${control}</label>`);
    const text=(name,value='',attrs='')=>{
        const placeholder=inputPlaceholders[name];
        const placeholderAttr=placeholder&&!/\bplaceholder=/.test(attrs)?`placeholder="${esc(placeholder)}"`:'';
        return`<input class="form-control" name="${name}" value="${esc(value)}" ${placeholderAttr} ${attrs}>`;
    };
    const area=(name,value='',attrs='')=>{
        const placeholder=areaPlaceholders[name];
        const placeholderAttr=placeholder&&!/\bplaceholder=/.test(attrs)?`placeholder="${esc(placeholder)}"`:'';
        return`<textarea class="form-control" name="${name}" rows="3" ${placeholderAttr} ${attrs}>${esc(value)}</textarea>`;
    };
    const select=(name,options,value)=>`<select class="form-select" name="${name}">${options}</select>`;
    const imageField=()=>{
        field('Cover image', 'image',`<input class="form-control" name="image" type="file" accept="image/jpeg,image/png,image/webp,image/gif" ${isNew?'required':''}><small class="ad-note">JPG, PNG, WebP, or GIF · up to 8 MB${item?.image?` · Current image: ${esc(item.image)}`:''}</small>`,true);
    };
    if(type==='promo'){
        field('Title','title',text('title',item?.title||'','placeholder="e.g. Weekend appliance deals" maxlength="180" required'));
        field('Button label','button_label',text('button_label',item?.button_label||'Shop now','maxlength="80" required'));
        field('Description','description',area('description',item?.description||'','placeholder="Describe the promotion and its benefits." maxlength="2000" required'),true);
        field('Image description (accessibility)','image_alt',text('image_alt',item?.image_alt||'','maxlength="180"'));
        field('Link directly to a product (optional)','product_code',select('product_code',`<option value="">Use destination URL instead</option>${optionList(products.map(product=>({value:product.id,label:product.name+' · '+product.id})),item?.product_code?[item.product_code]:[])}`,item?.product_code||''));
        field('Button destination URL','link_url',text('link_url',item?.link_url||'products.php','maxlength="512" required'));
        imageField();
        field('Display order','sort_order',text('sort_order',item?.sort_order??(promos.length+1),'type="number" min="0" step="1"'));
    }else if(type==='bundle'){
        field('Bundle name','title',text('title',item?.title||'','placeholder="e.g. Smart Kitchen Set" maxlength="180" required'));
        field('Bundle description','description',area('description',item?.description||'','placeholder="Briefly describe the products included." maxlength="500"'));
        const selectedCodes=item?.product_codes||[];
        fields.push(`<div class="field full"><span>Included products</span><div class="bundle-product-list">${products.map(product=>`<label class="bundle-product-option"><input class="bundle-product-checkbox" type="checkbox" name="product_codes[]" value="${esc(product.id)}" ${selectedCodes.includes(product.id)?'checked':''}><span><b>${esc(product.name)}</b><small>${esc(product.id)} · ${peso(product.price)}</small></span></label>`).join('')}</div></div>`);
        const initialDiscount=item&&Number(item.compare_at_price)>0
            ?Math.min(99.99,Math.max(0,(1-Number(item.price)/Number(item.compare_at_price))*100))
            :0;
        const roundedDiscount=Number(initialDiscount.toFixed(8));
        const discountOptions=[0,5,10,15,20].map(value=>({value:String(value),label:value===0?'No discount':`${value}%`}));
        const presetDiscount=discountOptions.some(option=>Math.abs(Number(option.value)-roundedDiscount)<0.0000001);
        discountOptions.push({value:'custom',label:'Custom percentage'});
        fields.push(`<div class="field"><span>Discount percentage</span><div class="bundle-discount-controls">${select('discount_preset',optionList(discountOptions,[presetDiscount?String(roundedDiscount):'custom']),'').replace('<select class="form-select"','<select class="form-select bundle-discount-preset"')}<label class="bundle-custom-discount"><input class="form-control bundle-discount-custom" type="number" min="0" max="99.99999999" step="0.00000001" value="${presetDiscount?'':roundedDiscount.toFixed(8)}" placeholder="e.g. 10" aria-label="Custom discount percentage"><span>%</span></label></div><small class="ad-note bundle-savings"></small><input type="hidden" name="discount_percent" value=""></div>`);
        field('Bundle price (₱)','price',`<input class="form-control bundle-price" name="price" value="${esc(item?.price??'')}" type="number" min="0.01" step="0.01" readonly required>`);
        field('Original price (₱)','compare_at_price',`<input class="form-control bundle-original-price" name="compare_at_price" value="${esc(item?.compare_at_price??'')}" type="number" min="0" step="0.01" readonly>`);
        field('Destination URL','link_url',text('link_url',item?.link_url||'products.php','maxlength="512" required'));
        imageField();
        field('Display order','sort_order',text('sort_order',item?.sort_order??(bundles.length+1),'type="number" min="0" step="1"'));
    }else if(type==='category'){
        const iconOptions=[
            ['grid','Grid'],['house','House'],['cup-hot','Kitchen'],['controller','Gaming controller'],
            ['display','Display'],['speaker','Speaker'],['camera-video','Video camera'],['lightbulb','Light bulb'],
            ['shield-check','Shield check'],['plug','Plug'],['pc-display','Computer display'],
            ['headphones','Headphones'],['wifi','Wi-Fi'],['lamp','Lamp'],['tools','Tools'],['box-seam','Box']
        ].map(([value,label])=>({value,label:`${label} · ${value}`}));
        if(item?.icon&&!iconOptions.some(option=>option.value===item.icon)){
            iconOptions.push({value:item.icon,label:`Current icon · ${item.icon}`});
        }
        const categories=[...new Set([...productCategories,item?.category||''].filter(Boolean))].sort();
        field('Tile title','title',text('title',item?.title||'','placeholder="e.g. Smart Home" maxlength="100" required'));
        field('Short description','description',text('description',item?.description||'','placeholder="e.g. Lighting, hubs & automation" maxlength="255"'));
        field('Bootstrap icon','icon',select('icon',`<option value="">Choose a Bootstrap icon</option>${optionList(iconOptions,item?.icon?[item.icon]:[])}`,item?.icon||'').replace('<select class="form-select"','<select class="form-select" required'));
        field('Product category','category',select('category',`<option value="">Choose a product category</option>${optionList(categories,item?.category?[item.category]:[])}`,item?.category||'').replace('<select class="form-select"','<select class="form-select" required'));
        const initialSubcategories=products
            .filter(product=>product.category===item?.category)
            .map(product=>String(product.sub||'').trim())
            .filter(Boolean);
        if(item?.subcategory&&!initialSubcategories.includes(item.subcategory))initialSubcategories.push(item.subcategory);
        field('Subcategory (optional)','subcategory',select('subcategory',`<option value="">${item?.category?'No subcategory (all products)':'Choose a category first'}</option>${optionList([...new Set(initialSubcategories)].sort(),item?.subcategory?[item.subcategory]:[])}`,item?.subcategory||'').replace('<select class="form-select"','<select class="form-select" data-category-subcategory'+(item?.category?'':' disabled')));
        field('Tile size','layout_class',select('layout_class',optionList([{value:'',label:'Standard'},{value:'wide',label:'Wide'},{value:'tall',label:'Tall'},{value:'wide tall',label:'Wide and tall'}]),item?.layout_class||''));
        imageField();
        field('Display order','sort_order',text('sort_order',item?.sort_order??(homeCategories.length+1),'type="number" min="0" step="1"'));
    }else if(type==='deal'){
        field('Featured product','product_code',select('product_code',`<option value="">Choose a featured product</option>${optionList(products.map(product=>({value:product.id,label:product.name+' · '+product.id})),item?[item.product_code]:[])}`,item?.product_code||''));
        field('Deal title','title',text('title',item?.title||'','placeholder="e.g. Deal of the Month" maxlength="180" required'));
        field('Deal description','description',area('description',item?.description||'','placeholder="Describe the featured product and deal." maxlength="2000" required'),true);
        field('Original price (₱)','compare_at_price',text('compare_at_price',item?.compare_at_price??0,'type="number" min="0" step="0.01"'));
        field('Deal ends at (optional)','ends_at',text('ends_at',item?.ends_at?item.ends_at.replace(' ','T').slice(0,16):'','type="datetime-local"')+'<small class="ad-note">Choose a date and time, or leave empty for no end date.</small>');
        fields.push(`<label class="field full">Deal image override (optional)<input class="form-control" name="image" type="file" accept="image/jpeg,image/png,image/webp,image/gif"><small class="ad-note">Leave empty to keep the current deal image. Image uploads are limited to 8 MB.</small></label>`);
    }else if(type==='demo_video'){
        fields.push(`<label class="field full">New homepage demo video<input class="form-control" name="video" type="file" accept="video/mp4,video/webm,video/ogg" required><small class="ad-note">MP4, WebM, or OGG · maximum 30 MB. Replacing the video updates the homepage Watch Demo button.</small>${item?.video_path?`<small class="ad-note">Current video: ${esc(item.video_path)}</small>`:''}</label>`);
    }else{
        field('Voucher code','code',text('code',item?.code||'','maxlength="40" pattern="[A-Za-z0-9_-]+" required'));
        field('Offer title','label',text('label',item?.label||'','maxlength="180" required'));
        field('Terms and conditions','description',area('description',item?.description||'','placeholder="Add any limits or conditions for this voucher." maxlength="500"'),true);
        field('Discount type','discount_type',select('discount_type',optionList([{value:'fixed',label:'Fixed amount'},{value:'percent',label:'Percentage'}]),item?.discount_type||'fixed'));
        field('Discount value','discount_value',text('discount_value',item?.discount_value??'','type="number" min="0.01" step="0.01" required'));
        field('Minimum spend (₱)','minimum_spend',text('minimum_spend',item?.minimum_spend??0,'type="number" min="0" step="0.01"'));
        field('Maximum discount (₱, optional)','discount_cap',text('discount_cap',item?.discount_cap??'','type="number" min="0.01" step="0.01"'));
        field('Eligible products','eligible_products',select('eligible_products[]',optionList(products.map(product=>({value:product.id,label:product.name+' · '+product.id})),item?.eligible_products||[]),'').replace('<select ','<select multiple size="6" '));
        field('Eligible categories','eligible_categories',select('eligible_categories[]',optionList(productCategories,item?.eligible_categories||[]),'').replace('<select ','<select multiple size="5" '));
        field('Required bundle sets','bundle_sets',area('bundle_sets',(item?.required_product_sets||[]).map(set=>set.join(', ')).join('\n'),'placeholder="One set per line, product codes separated by commas"'));
        field('Starts at','starts_at',text('starts_at',item?.starts_at?item.starts_at.replace(' ','T').slice(0,16):'','type="datetime-local"')+'<small class="ad-note">Choose a date and time, or leave empty to start immediately.</small>');
        field('Ends at','ends_at',text('ends_at',item?.ends_at?item.ends_at.replace(' ','T').slice(0,16):'','type="datetime-local"')+'<small class="ad-note">Choose a date and time, or leave empty for no end date.</small>');
        field('Total uses limit (optional)','total_usage_limit',text('total_usage_limit',item?.total_usage_limit??'','type="number" min="1" step="1"'));
        field('Uses per customer (optional)','per_customer_limit',text('per_customer_limit',item?.per_customer_limit??'','type="number" min="1" step="1"'));
        field('First order only','first_order_only',`<input type="checkbox" name="first_order_only" value="1" ${Number(item?.first_order_only)?'checked':''}>`);
    }
    if(type!=='demo_video')field(type==='voucher'?'Voucher enabled':'Published on storefront','active',`<input type="checkbox" name="active" value="1" ${item?Number(item.active)?'checked':'':'checked'}>`);
    $('#contentFormTitle').text((isNew?'Add ':'Edit ')+({promo:'homepage promo',bundle:'featured bundle',category:'homepage category tile',deal:'deal of the month',demo_video:'homepage demo video',voucher:'voucher'}[type]));
    $('#contentId').val(item?.id||'');
    $('#contentAction').val('save_'+type);
    $('#contentFields').html(fields.join(''));
    if(type==='category'){
        const $category=$('#contentFields [name="category"]');
        const $subcategory=$('#contentFields [name="subcategory"]');
        $category.on('change',function(){
            const category=String($(this).val()||'');
            const values=[...new Set(products
                .filter(product=>product.category===category)
                .map(product=>String(product.sub||'').trim())
                .filter(Boolean))].sort();
            $subcategory
                .html(`<option value="">${category?'No subcategory (all products)':'Choose a category first'}</option>${optionList(values)}`)
                .prop('disabled',!category)
                .val('');
        });
    }
    if(type==='bundle')updateBundlePricing();
    $('#contentMessage').removeClass('err ok').text('');
    $('#saveContent').prop('disabled',false).text('Save changes');
    $('#contentDialog')[0].showModal();
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

$('#pOnSale').on('change',function(){
    const enabled=$(this).is(':checked');
    $(this).closest('.ad-label-chip').toggleClass('is-checked',enabled);
    $('#pSalePrice').prop('disabled',!enabled);
    if(!enabled)$('#pSalePrice').val('');
});

$('#pBestseller').on('change',function(){
    $(this).closest('.ad-label-chip').toggleClass('is-checked',$(this).is(':checked'));
});

$('#pCategory').on('change',function(){
    populateProductSubcategories(String($(this).val()||''));
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
    const onSale=$('#pOnSale').is(':checked');
    const salePrice=Number($('#pSalePrice').val());
    const stock=Number($('#pStock').val());
    const image=$('#pImage')[0].files.length>0;
    const sub=$.trim($('#pSub').val());

    if(
        !name||
        (!editingProduct&&!image)||
        !$.trim($('#pCategory').val())||
        !sub||
        !Number.isFinite(price)||
        price<=0||
        price>999999||
        (onSale&&(!Number.isFinite(salePrice)||salePrice<=0||salePrice>999999||salePrice>=price))||
        !Number.isFinite(stock)||
        stock<0||
        stock>100
    ){
        e.preventDefault();

        $('#formMsg')
            .removeClass('ok')
            .addClass('err')
            .text('Complete the product details and choose a main image for new products. Price must not exceed ₱999,999; stock must be between 0 and 100. Sale price must be greater than zero and lower than the regular price.');

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
    const row=$(this).closest('tr');
    const status=this.value;
    const cancelFields=row.find('.order-cancel-fields');
    const save=row.find('.order-save');
    const message=row.find('.order-status-message');
    cancelFields.prop('hidden',status!=='Cancelled');
    save.prop('hidden',status===this.dataset.originalStatus);
    message.prop('hidden',true).text('');
    if(status!=='Cancelled'){
        row.find('.order-cancel-reason').val('');
        row.find('.order-cancel-details').val('').prop('hidden',true);
    }
});

$('#orderRows').on('change','.order-cancel-reason',function(){
    const row=$(this).closest('tr');
    const isOther=this.value==='Other';
    row.find('.order-cancel-details')
        .prop('hidden',!isOther)
        .prop('required',isOther);
});

$('#orderRows').on('click','.order-save',async function(){
    const button=$(this);
    const row=button.closest('tr');
    const select=row.find('.order-status');
    const status=select.val();
    const reason=row.find('.order-cancel-reason').val();
    const details=row.find('.order-cancel-details').val().trim();
    const message=row.find('.order-status-message');

    message.prop('hidden',true).text('');
    if(status==='Cancelled'&&(!reason||(reason==='Other'&&!details))){
        message.text(reason==='Other'?'Please explain the cancellation reason.':'Please choose a cancellation reason.')
            .prop('hidden',false);
        if(!reason)row.find('.order-cancel-reason').trigger('focus');
        else row.find('.order-cancel-details').trigger('focus');
        return;
    }

    button.prop('disabled',true).text('Saving…');
    const body=new URLSearchParams({
        id:String(row.data('order-id')),
        status,
        reason,
        details,
        csrf_token:window.ADMIN_CSRF_TOKEN||''
    });
    try{
        const response=await fetch('php/update_order_status.php',{
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8','Accept':'application/json'},
            body:body.toString()
        });
        let result;
        try{
            result=await response.json();
        }catch(error){
            throw new Error('The server returned an unexpected response. Refresh and try again.');
        }
        if(!response.ok||!result.success){
            throw new Error(result.message||'Unable to update this order status.');
        }
        const order=orders.find(item=>Number(item.id)===Number(row.data('order-id')));
        if(order){
            order.status=status;
            if(status==='Cancelled'){
                order.cancellationReason=reason;
                order.cancellationDetails=details;
            }
        }
        renderOrders();
    }catch(error){
        message.text(error.message||'Unable to update this order status.').prop('hidden',false);
        button.prop('disabled',false).text('Save');
    }
});

$('#refundRows').on('click','[data-refund-status]',function(){
    const row=$(this).closest('tr');
    phpPost('php/update_refund_request.php',{
        id:Number(row.data('refund-id')),
        status:$(this).data('refund-status'),
        admin_note:row.find('.refund-note').val()
    });
});

$('#deletionRequestRows').on('click','[data-account-request-status]',function(){
    const row=$(this).closest('tr');
    phpPost('php/update_account_deletion_request.php',{
        id:Number(row.data('account-request-id')),
        status:$(this).data('account-request-status'),
        admin_note:row.find('.account-request-note').val()
    });
});

$(document).on('click','[data-content-new]',function(){
    openContentEditor($(this).data('content-new'));
});

$(document).on('click','[data-content-edit]',function(){
    const type=String($(this).data('content-edit'));
    const id=Number($(this).data('content-id'));
    const sources={promo:promos,bundle:bundles,category:homeCategories,deal:deal?[deal]:[],demo_video:homeDemoVideo?[homeDemoVideo]:[],voucher:vouchers};
    const item=(sources[type]||[]).find(entry=>Number(entry.id)===id);
    if(item)openContentEditor(type,item);
});

$(document).on('click','[data-content-close]',function(){
    $('#contentDialog')[0].close();
});

$(document).on('change input','.bundle-product-checkbox,.bundle-discount-preset,.bundle-discount-custom',function(){
    updateBundlePricing();
});

$(document).on('click','[data-content-delete]',async function(){
    const button=$(this);
    const type=String(button.data('content-delete'));
    const id=Number(button.data('content-id'));
    if(!confirm('Delete this '+type+'? This change will appear on the storefront immediately.'))return;
    const data=new FormData();
    data.append('action','delete_'+type);
    data.append('id',String(id));
    data.append('csrf_token',window.ADMIN_CSRF_TOKEN||'');
    try{
        const response=await fetch('php/manage_store_content.php',{method:'POST',body:data});
        const result=await response.json();
        if(!response.ok||!result.ok)throw new Error(result.message||'Unable to delete this content.');
        location.reload();
    }catch(error){
        alert(error.message||'Unable to delete this content. Please try again.');
    }
});

$('#contentForm').on('submit',async function(event){
    event.preventDefault();
    const submit=$('#saveContent');
    const message=$('#contentMessage');
    submit.prop('disabled',true).text('Saving...');
    message.removeClass('err ok').text('');
    try{
        const response=await fetch('php/manage_store_content.php',{method:'POST',body:new FormData(this)});
        const result=await response.json();
        if(!response.ok||!result.ok)throw new Error(result.message||'Unable to save these changes.');
        location.reload();
    }catch(error){
        message.addClass('err').text(error.message||'Unable to save these changes. Please try again.');
        submit.prop('disabled',false).text('Save changes');
    }
});

/* START */
const requestedPage=new URLSearchParams(location.search).get('page');

go(
    titles[requestedPage]
    ?requestedPage
    :'dashboard'
);

});