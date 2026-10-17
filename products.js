function needLogin(){
    if(localStorage.getItem('st_session'))return false;
    const page=location.pathname.split('/').pop()||'products.php';
    location.href='login.php?next='+encodeURIComponent(page);
    return true;
}

const FREE_SHIP_MIN=3000;
const shippingFor=subtotal=>subtotal>=FREE_SHIP_MIN?0:150;
let currentCategory=window.INITIAL_CATEGORY||'household';
let currentSubcat=null;
const escapeHtml=value=>String(value??'').replace(/[&<>"']/g,char=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));
function safeProductDescription(value){
    const parsed=new DOMParser().parseFromString(String(value??''),'text/html');
    const allowed=new Set(['p','br','ul','ol','li','strong','b','em','i']);
    const skipped=new Set(['script','style','iframe','object','svg','math']);
    const output=document.createElement('div');
    const copy=(source,target)=>{
        source.childNodes.forEach(node=>{
            if(node.nodeType===Node.TEXT_NODE){
                target.appendChild(document.createTextNode(node.nodeValue||''));
                return;
            }
            if(node.nodeType!==Node.ELEMENT_NODE)return;
            const tag=node.tagName.toLowerCase();
            if(skipped.has(tag))return;
            const container=allowed.has(tag)?document.createElement(tag):target;
            if(container!==target)target.appendChild(container);
            copy(node,container);
        });
    };
    copy(parsed.body,output);
    return output.innerHTML;
}

function renderTopCats(){
    $('.cat-box').each(function(){
        $(this).toggleClass('active',$(this).data('cat')===currentCategory);
    });
}

function renderSubcats(){
    const $area=$('#subcat-area');
    $area.empty();

    const subs=[...new Set(
        PRODUCTS.filter(p=>p.category===currentCategory).map(p=>p.sub)
    )];

    if(!subs.length)return;

    const $wrapper=$('<div>').addClass('subcats');

    $('<button>')
        .addClass('subcat-btn')
        .toggleClass('active',currentSubcat===null)
        .attr('data-sub','')
        .text('All')
        .appendTo($wrapper);

    $.each(subs,function(_,sub){
        $('<button>')
            .addClass('subcat-btn')
            .toggleClass('active',currentSubcat===sub)
            .attr('data-sub',sub)
            .text(sub)
            .appendTo($wrapper);
    });

    $area.append($wrapper);
}

function renderProducts(){
    const $grid=$('#product-grid');
    $grid.empty();

    const list=PRODUCTS.filter(product=>{
        return product.category===currentCategory&&(currentSubcat===null||product.sub===currentSubcat);
    });

    $.each(list,function(_,product){
        const stock=Number(product.stock||0);

        let stockText='';
        let stockClass='in';

        if(stock<=0){
            stockText='Out of stock';
            stockClass='out';
        }else if(stock<=9){
            stockText='Only '+stock+' left in stock';
            stockClass='low';
        }else{
            stockText=stock+' in stock';
        }

        $grid.append(`
        <div class="product-card" data-id="${escapeHtml(product.id)}">
            <a class="product-image-link" href="product.php?id=${encodeURIComponent(product.id)}" aria-label="View details for ${escapeHtml(product.name)}">
            <div class="img">
                <img src="${escapeHtml(product.img)}" alt="${escapeHtml(product.name)}" onerror="this.style.display='none'">
            </div>
            </a>

            <div class="product-name">${escapeHtml(product.name)}</div>
            <div class="product-price">₱${product.price.toLocaleString()}</div>

            <div style="color:var(--muted);font-size:13px;margin-bottom:8px">
                ${escapeHtml(product.sub)}
            </div>

            <div class="stock-line ${stockClass}">
                ${stockText}
            </div>

            <div class="product-actions">
                <button class="btn-add" data-add="${escapeHtml(product.id)}" ${stock<=0?'disabled':''}>
                    ${stock<=0?'Out of Stock':'Add to Cart'}
                </button>

                <button class="btn-buy" data-buy="${escapeHtml(product.id)}" ${stock<=0?'disabled':''}>
                    ${stock<=0?'Unavailable':'Buy Now'}
                </button>

                <a class="btn-view" href="product.php?id=${encodeURIComponent(product.id)}">
                    View Details
                </a>
            </div>
        </div>`);
    });
}

let cart=[];

try{
    cart=JSON.parse(localStorage.getItem('st_cart'))||[];
}catch(e){
    cart=[];
}

function saveCart(){
    localStorage.setItem('st_cart',JSON.stringify(cart));
    updateCartCount();
}

function updateCartCount(){
    const count=cart.reduce((total,item)=>total+Number(item.qty||1),0);
    $('#cart-count').text(count);
}

function findProduct(id){
    return PRODUCTS.find(p=>p.id===id);
}

function addToCart(id){
    if(needLogin())return false;

    const product=findProduct(id);

    if(!product){
        alert('Product not found.');
        return false;
    }

    const stock=Number(product.stock||0);

    if(stock<=0){
        alert(product.name+' is out of stock.');
        return false;
    }

    const index=cart.findIndex(item=>item.id===id);
    const current=index>-1?Number(cart[index].qty||1):0;

    if(current>=stock){
        alert('Only '+stock+' stock available for '+product.name+'.');
        return false;
    }

    if(index>-1){
        cart[index].qty=current+1;
    }else{
        cart.push({
            id:product.id,
            name:product.name,
            price:product.price,
            qty:1,
            img:product.img
        });
    }

    saveCart();
    renderCart();

    return true;
}

function buyNow(id){
    if(needLogin())return false;

    const product=findProduct(id);

    if(!product){
        alert('Product not found.');
        return false;
    }

    const stock=Number(product.stock||0);

    if(stock<=0){
        alert(product.name+' is out of stock.');
        return false;
    }

    const existing=cart.find(item=>item.id===id);

    if(!existing){
        cart.push({
            id:product.id,
            name:product.name,
            price:product.price,
            qty:1,
            img:product.img
        });

        saveCart();
    }else if(Number(existing.qty||1)>stock){
        existing.qty=stock;
        saveCart();
    }

    try{
        sessionStorage.setItem('st_buy',id);
    }catch(e){}

    location.href='cart.php';

    return true;
}

function renderCart(){
    const $container=$('#cart-items');

    if(!$container.length)return;

    $container.empty();

    if(!cart.length){
        $container.html('<p style="text-align:center;color:#777">Your cart is empty.</p>');
        $('#cart-total').text('Total: ₱0');
        updateCartCount();
        return;
    }

    let total=0;

    $.each(cart,function(index,item){
        const product=findProduct(item.id);
        const stock=product?Number(product.stock||0):0;

        if(stock>0&&item.qty>stock){
            item.qty=stock;
        }

        total+=item.price*item.qty;

        $container.append(`
        <div class="cart-item">

            <div class="cart-left">
                <img src="${escapeHtml(item.img)}" alt="${escapeHtml(item.name)}" onerror="this.style.display='none'">

                <div class="cart-meta">
                    <div style="font-weight:bold">${escapeHtml(item.name)}</div>
                    <div style="color:var(--muted);font-size:13px">
                        ₱${item.price.toLocaleString()}
                    </div>
                </div>
            </div>

            <div style="display:flex;align-items:center;gap:12px">

                <div class="qty-controls">
                    <button class="qty-minus" data-index="${index}">-</button>

                    <span style="min-width:24px;text-align:center">
                        ${item.qty}
                    </span>

                    <button class="qty-plus" data-index="${index}" ${item.qty>=stock?'disabled':''}>
                        +
                    </button>
                </div>

                <div style="min-width:100px;text-align:right">
                    ₱${(item.price*item.qty).toLocaleString()}
                </div>

                <button class="remove-btn" data-remove="${index}">
                    Remove
                </button>
            </div>
        </div>`);
    });

    saveCart();

    $('#cart-total').text('Total: ₱'+total.toLocaleString());
    updateCartCount();
}

function changeQty(index,amount){
    const item=cart[index];
    if(!item)return;

    const product=findProduct(item.id);

    if(!product){
        alert('Product no longer exists.');
        return;
    }

    const stock=Number(product.stock||0);
    const next=Math.max(1,Number(item.qty||1)+amount);

    if(next>stock){
        alert('Only '+stock+' stock available for '+product.name+'.');
        return;
    }

    item.qty=next;

    saveCart();
    renderCart();
}

function removeItem(index){
    if(!confirm('Remove this item?'))return;

    cart.splice(index,1);

    saveCart();
    renderCart();
}

function clearCart(){
    if(!confirm('Clear entire cart?'))return;

    cart=[];

    saveCart();
    renderCart();
}

function scrollToCart(){
    renderCart();

    const $cart=$('#cart-section');

    if(!$cart.length)return;

    $('html,body').stop(true).animate({
        scrollTop:$cart.offset().top-20
    },500);
}

function scrollToTop(){
    $('html,body').stop(true).animate({
        scrollTop:0
    },500);
}

function openPayment(){
    if(!cart.length){
        alert('Cart empty. Add items first.');
        return;
    }

    const $payItems=$('#pay-items');

    if(!$payItems.length){
        location.href='cart.php';
        return;
    }

    $payItems.empty();

    let total=0;

    $.each(cart,function(_,item){
        total+=item.price*item.qty;

        $payItems.append(`
        <div style="padding:6px 0">
            ${escapeHtml(item.name)} x${Number(item.qty)} —
            ₱${(item.price*item.qty).toLocaleString()}
        </div>`);
    });

    $('#pay-total').text('₱'+total.toLocaleString());

    $('#payment').stop(true,true).slideDown(400);

    setTimeout(function(){
        $('html,body').stop(true).animate({
            scrollTop:$('#payment').offset().top-20
        },500);
    },150);
}

function closePayment(){
    $('#payment').stop(true,true).slideUp(400);
}

function completePayment(){
    location.href='cart.php';
}

function showDetails(id){
    const product=findProduct(id);

    if(!product)return;

    const stock=Number(product.stock||0);

    let stockText='';
    let stockClass='in';

    if(stock<=0){
        stockText='Out of stock';
        stockClass='out';
    }else if(stock<=9){
        stockText='Only '+stock+' left in stock';
        stockClass='low';
    }else{
        stockText=stock+' in stock';
    }

    $('#descContent').html(`
        <img src="${escapeHtml(product.img)}" alt="${escapeHtml(product.name)}">

        <h2>${escapeHtml(product.name)}</h2>

        <p>
            <strong>₱${product.price.toLocaleString()}</strong>
        </p>

        <p style="color:#555">${escapeHtml(product.sub)}</p>

        <p class="stock-line ${stockClass}">
            ${stockText}
        </p>

        <div style="text-align:left;margin:10px auto;max-width:600px">
            ${safeProductDescription(product.desc)}
        </div>

        <div style="margin-top:15px">
            <button class="btn-add" data-add="${escapeHtml(product.id)}" ${stock<=0?'disabled':''}>
                ${stock<=0?'Out of Stock':'Add to Cart'}
            </button>

            <button class="btn-buy" data-buy="${escapeHtml(product.id)}" ${stock<=0?'disabled':''}>
                ${stock<=0?'Unavailable':'Buy Now'}
            </button>
        </div>
    `);

    $('#descOverlay').css('display','flex').hide().fadeIn(300);
}

function closeOverlay(){
    $('#descOverlay').stop(true,true).fadeOut(300);
}

function init(){
    renderTopCats();
    renderSubcats();
    renderProducts();
    renderCart();
    updateCartCount();
}

$(document).ready(function(){

    init();

    $('.cat-box').on('click',function(){
        currentCategory=$(this).data('cat');
        currentSubcat=null;

        renderTopCats();
        renderSubcats();
        renderProducts();
        scrollToTop();
    });

    $(document).on('click','.subcat-btn',function(){
        const sub=$(this).attr('data-sub');

        currentSubcat=sub===''?null:sub;

        renderSubcats();
        renderProducts();
    });

    $(document).on('click','.btn-add',function(){
        const id=$(this).data('add');
        if(id)addToCart(id);
    });

    $(document).on('click','.btn-buy',function(){
        const id=$(this).data('buy');
        if(id)buyNow(id);
    });

    $(document).on('click','.btn-view',function(){
        const id=$(this).data('view');
        if(id)location.href='product.php?id='+encodeURIComponent(id);
    });

    $(document).on('click','.qty-minus',function(){
        changeQty(Number($(this).data('index')),-1);
    });

    $(document).on('click','.qty-plus',function(){
        changeQty(Number($(this).data('index')),1);
    });

    $(document).on('click','.remove-btn',function(){
        removeItem(Number($(this).data('remove')));
    });

    $('input[name="paymethod"]').on('change',function(){
        const method=$('input[name="paymethod"]:checked').val();

        if(method==='Card'){
            $('#card-fields').stop(true,true).slideDown(300);
        }else{
            $('#card-fields').stop(true,true).slideUp(300);
        }
    });

    $('#descOverlay').on('click',function(event){
        if(event.target===this)closeOverlay();
    });
});