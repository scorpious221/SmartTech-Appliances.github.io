(function(){

const DETAILS_PAGE='';

const CATNAMES={
    household:'Household',
    entertainment:'Entertainment',
    kitchen:'Kitchen'
};

const SORTS={
    featured:'Featured',
    asc:'Price: low to high',
    desc:'Price: high to low'
};

const BADGES={
    cctv:'Bestseller',
    doorbell:'Bestseller',
    airfryer:'Bestseller',
    ps:'Bestseller',
    nsw2:'New arrival',
    oled:'New arrival'
};

const esc=s=>String(s).replace(/[&<>"']/g,c=>({
    '&':'&amp;',
    '<':'&lt;',
    '>':'&gt;',
    '"':'&quot;',
    "'":'&#39;'
}[c]));

let sortMode='featured';
let quiet=false;
let toastTimer;
let drawTimer;
let wish=[];

try{
    wish=JSON.parse(localStorage.getItem('st_wishlist'))||[];
}catch(e){}

const style=document.createElement('style');

style.textContent=`
.stock-line{font-size:12px;font-weight:700;margin:-4px 0 12px}
.stock-line.in{color:#1B6B4A}
.stock-line.low{color:#9A6700}
.stock-line.out{color:#A3321A}
.product-card.is-out .img img{opacity:.48}
.product-card.is-out .btn-add,
.product-card.is-out .btn-buy{opacity:.55;cursor:not-allowed}
`;

document.head.appendChild(style);

function stockClass(stock){
    if(Number(stock)<=0)return 'out';
    if(Number(stock)<=9)return 'low';
    return 'in';
}

function stockText(p){
    if(Number(p.stock)<=0)return 'Out of stock';
    if(Number(p.stock)<=9)return `Only ${p.stock} left in stock`;
    return `${p.stock} in stock`;
}

function canAdd(id){
    const p=findProduct(id);

    if(!p){
        alert('Product not found.');
        return false;
    }

    const inCart=cart.find(i=>i.id===id);
    const q=inCart?Number(inCart.qty||1):0;
    const stock=Number(p.stock||0);

    if(stock<=0){
        alert(`${p.name} is out of stock.`);
        return false;
    }

    if(q>=stock){
        alert(`Only ${stock} stock available for ${p.name}.`);
        return false;
    }

    return true;
}

function card(p){
    const on=wish.includes(p.id);
    const out=Number(p.stock)<=0;

    const href=DETAILS_PAGE
        ?DETAILS_PAGE+'?id='+encodeURIComponent(p.id)
        :'?product='+encodeURIComponent(p.id);

    return`
    <article class="product-card${out?' is-out':''}" data-id="${esc(p.id)}">

        <div class="img">

            ${BADGES[p.id]?`<span class="card-badge">${BADGES[p.id]}</span>`:''}

            <button
                class="wish-btn"
                data-wish="${esc(p.id)}"
                aria-pressed="${on}"
                aria-label="${on?'Remove':'Add'} ${esc(p.name)} ${on?'from':'to'} wishlist"
            >
                <i class="bi bi-heart${on?'-fill':''}"></i>
            </button>

            <img
                src="${esc(p.img)}"
                alt="${esc(p.name)}"
                loading="lazy"
                onerror="this.style.display='none'"
            >

            <span class="view-pill" aria-hidden="true">
                View details
                <i class="bi bi-arrow-up-right"></i>
            </span>

        </div>

        <div class="card-body-st">

            <span class="product-sub">${esc(p.sub)}</span>

            <h3 class="product-name">
                <a class="card-link" href="${href}">
                    ${esc(p.name)}
                </a>
            </h3>

            <div class="price-row">
                <span class="product-price">
                    ₱${p.price.toLocaleString()}
                </span>

                <i class="bi bi-arrow-up-right"></i>
            </div>

            <p class="stock-line ${stockClass(p.stock)}">
                ${esc(stockText(p))}
            </p>

            <div class="product-actions">

                <button
                    class="btn-add"
                    data-add="${esc(p.id)}"
                    ${out?'disabled':''}
                >
                    ${out?'Out of Stock':'Add to Cart'}
                </button>

                <button
                    class="btn-buy"
                    data-buy="${esc(p.id)}"
                    ${out?'disabled':''}
                >
                    ${out?'Unavailable':'Buy Now'}
                </button>

            </div>

        </div>

    </article>`;
}

const skeleton=()=>Array(8).fill(`
<div class="product-card skel" aria-hidden="true">
    <div class="img">
        <i class="bi bi-image"></i>
    </div>

    <div class="card-body-st">
        <span class="sk sk-s"></span>
        <span class="sk sk-l"></span>
        <span class="sk sk-m"></span>
        <span class="sk sk-xs"></span>
        <span class="sk sk-btn"></span>
        <span class="sk sk-btn"></span>
    </div>
</div>
`).join('');

function draw(){
    let list=PRODUCTS.filter(p=>{
        return p.category===currentCategory&&(currentSubcat===null||p.sub===currentSubcat);
    });

    if(sortMode==='asc'){
        list=[...list].sort((a,b)=>a.price-b.price);
    }

    if(sortMode==='desc'){
        list=[...list].sort((a,b)=>b.price-a.price);
    }

    const cat=CATNAMES[currentCategory];

    $('#result-count').text(
        list.length+(list.length===1?' product':' products')
    );

    $('#result-path').text(
        cat+' / '+(currentSubcat||'All essentials')
    );

    $('#end-note b').text(
        `You've seen all ${list.length} products`
    );

    $('#product-grid')
        .removeAttr('aria-busy')
        .html(
            list.length
            ?list.map(card).join('')
            :`
            <div class="empty">
                <span class="e-ico">
                    <i class="bi bi-search"></i>
                </span>

                <h3>No products found</h3>

                <p>
                    We couldn't find a match in ${cat}.
                    Try a different filter or clear your filters.
                </p>

                <button class="btn-clay" id="reset-filters">
                    Clear search & filters
                </button>
            </div>`
        );
}

window.renderProducts=function(){
    $('#product-grid')
        .attr('aria-busy','true')
        .html(skeleton());

    clearTimeout(drawTimer);
    drawTimer=setTimeout(draw,300);
};

function toast(p){
    const item=cart.find(i=>i.id===p.id);
    const q=item?item.qty:1;

    $('#toast').html(`
        <span class="t-ico">
            <i class="bi bi-check2"></i>
        </span>

        <div class="t-txt">
            <b>Added to your cart</b>
            <small>
                ${esc(p.name)} • ${q} × ₱${p.price.toLocaleString()}
            </small>
        </div>

        <button type="button" class="t-view" id="toast-cart">
            View cart
        </button>

        <button type="button" class="t-x" id="toast-x">
            <i class="bi bi-x-lg"></i>
        </button>
    `).addClass('show');

    clearTimeout(toastTimer);

    toastTimer=setTimeout(()=>{
        $('#toast').removeClass('show');
    },4000);
}

const _add=window.addToCart;
const _buy=window.buyNow;
const _details=window.showDetails;

window.addToCart=function(id){
    if(needLogin()||!canAdd(id))return false;

    const added=_add(id);
    const p=findProduct(id);

    if(added&&p&&!quiet)toast(p);

    return added;
};

window.buyNow=function(id){
    if(needLogin())return false;

    const p=findProduct(id);

    if(!p){
        alert('Product not found.');
        return false;
    }

    if(Number(p.stock)<=0){
        alert(`${p.name} is out of stock.`);
        return false;
    }

    quiet=true;
    const result=_buy(id);
    quiet=false;

    return result;
};

window.showDetails=function(id){
    _details(id);
};

function setSort(v){
    sortMode=v;
    $('#sortLabel').text(SORTS[v]);

    $('#sortList li').each(function(){
        $(this).attr('aria-selected',this.dataset.v===v);
    });
}

function toggleSort(open){
    $('#sortList').prop('hidden',!open);
    $('#sortBtn').attr('aria-expanded',open);
}

$(function(){

    const q=new URLSearchParams(location.search);

    if(CATNAMES[q.get('cat')]){
        currentCategory=q.get('cat');
        currentSubcat=null;
    }

    const pid=q.get('product');
    const found=pid&&findProduct(pid);

    if(found){
        currentCategory=found.category;
        currentSubcat=null;
    }

    const sub=q.get('sub');

    if(sub&&PRODUCTS.some(p=>p.category===currentCategory&&p.sub===sub)){
        currentSubcat=sub;
    }

    renderTopCats();
    renderSubcats();
    renderProducts();

    if(found)showDetails(pid);

    $('#sortBtn').on('click',function(e){
        e.stopPropagation();
        toggleSort($('#sortList').prop('hidden'));
    });

    $('#sortList')
        .on('click','li',function(){
            setSort(this.dataset.v);
            toggleSort(false);
            renderProducts();
        })
        .on('keydown','li',function(e){
            if(e.key==='Enter'||e.key===' '){
                e.preventDefault();
                $(this).click();
            }
        });

    $(document)
        .on('click',function(){
            toggleSort(false);
        })
        .on('keydown',function(e){
            if(e.key==='Escape')toggleSort(false);
        });

    $(document).on('click','.wish-btn',function(){
        const id=$(this).data('wish');
        const index=wish.indexOf(id);

        if(index>-1){
            wish.splice(index,1);
        }else{
            wish.push(id);
        }

        try{
            localStorage.setItem('st_wishlist',JSON.stringify(wish));
        }catch(e){}

        draw();
    });

    $(document).on('click','.card-link',function(e){
        if(DETAILS_PAGE)return;

        e.preventDefault();

        showDetails(
            $(this).closest('.product-card').data('id')
        );
    });

    $(document).on('click','#reset-filters',function(){
        currentSubcat=null;
        setSort('featured');
        renderSubcats();
        renderProducts();
    });

    $(document).on('click','#toast-cart',function(){
        location.href='cart.php';
    });

    $(document).on('click','#toast-x',function(){
        $('#toast').removeClass('show');
    });

    const openSearch=()=>{
        const button=document.querySelector('.stq-btn');
        if(button)button.click();
    };

    $('.st-search input').on('click keydown',function(e){
        if(e.type==='click'||e.key==='Enter')openSearch();
    });

});
})();