const IMG='images/';
const $=s=>document.querySelector(s);
const esc=s=>String(s).replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));

const shop=(cat,sub)=>'products.php'+(cat?'?cat='+encodeURIComponent(cat):'')+(sub?'&sub='+encodeURIComponent(sub):'');

const NAV=[
['Home','index.html',1],
['Household',shop('household')],
['Entertainment',shop('entertainment')],
['Kitchen',shop('kitchen')]
];

const TRUST=[
['truck','Free Delivery','Orders over ₱3,000','Over ₱3,000'],
['shield-check','1-Year Warranty','Official local warranty','Official local warranty'],
['credit-card','Secure Payment','GCash, Maya, cards, COD','GCash, Maya, COD'],
['headset','24/7 Support','Real people, always ready','Always ready']
];

const REVIEWS=[
['Delivery to Cebu was quicker than expected, and the camera setup was genuinely easy.','Mika R.','Cebu City'],
['The team helped me pick the right aircon size and installed it the next day. No surprises.','Paolo D.','Quezon City'],
['My gaming monitor arrived well packed and exactly on time. SmartTech is now my go-to.','Andre S.','Davao City']
];

let drawProg;

const TILES=[
['house','Smart Home','Lighting, hubs & automation','wide tall','SmartHomeCategory.png',shop('household')],
['cup-hot','Kitchen','Cook faster, live better','tall','KitchenCategory.png',shop('kitchen')],
['controller','Gaming','Level up your setup','tall','GamingCategory.png',shop('entertainment','Gaming Gadgets')],
['display','Monitors','See every detail','','MonitorsCategory.png',shop('entertainment','Televisions & Displays')],
['speaker','Audio','Sound worth feeling','','AudioCategory.png',shop('entertainment','Audio & Music')],
['camera-video','Security','Protect what matters','wide','SecurityCategory.png',shop('household','Security Devices')]
];

const FEATURED=[
{id:'airfryer',s:4.9,c:328,o:2999,b:'Best seller',t:['best']},
{id:'cctv',s:4.8,c:181,o:9900,b:'New',t:['new']},
{id:'oled',s:4.7,c:96,o:21988,b:'18% off',t:['sale']},
{id:'doorbell',s:4.8,c:244,o:15500,b:'Hot',t:['best','sale']},
{id:'generator',s:4.6,c:137,o:12000,b:'New',t:['new']}
];

let CARDS=FEATURED.map(f=>{
    const b=PRODUCTS.find(p=>p.id===f.id);

    return b?{
        ...f,
        n:b.name,
        p:b.price,
        i:b.img.replace('images/',''),
        stock:Number(b.stock||0)
    }:null;
}).filter(Boolean);

const TABS=[
['all','Best Sellers'],
['new','New'],
['sale','On Sale']
];

const BUNDLES=[
['Smart Kitchen Set','Air fryer · Rice cooker · Blender',7499,8333,'afryer.jpg',shop('kitchen','Cooking Appliances')],
['Gamer Setup','Computer monitor · Controller · Headphones',31990,34569,'computermonitor.jpg',shop('entertainment','Gaming Gadgets')],
['Security Kit','CCTV camera · Video doorbell · Smoke detector',22900,24900,'cctv.jpg',shop('household','Security Devices')]
];

const BRANDS=['SAMSUNG','LG','SONY','XIAOMI','JBL','DYSON','TP-Link'];

const SERVICES=[
['wrench','Home Service','Professional installation, repair, and maintenance right at your doorstep.','about.html#contact'],
['patch-check','Warranty','We offer product warranty and customer support for replacements or repairs.','help.html#returns'],
['shield-check','Secure Checkout','Pay your way with cards, e-wallets, or Cash on Delivery, safe and hassle-free.','help.html#payment'],
['arrow-counterclockwise','Easy Returns','Simple 30-day returns online or at any SmartTech store.','help.html#returns']
];

const PROMOS=[
['Ber Months Sale: Up to 50% OFF','Start the season early with savings across appliances, TVs, and smart-home essentials.','Shop the Sale','Promo_image.png','Seasonal savings across appliances, TVs, and smart-home essentials.','products.php'],
['Free Delivery on Appliances','Get free delivery on orders over ₱3,000, anywhere in the Philippines.','Shop Appliances','Delivery-Promo.png','Free delivery on orders over ₱3,000, anywhere in the Philippines.',shop('kitchen')]
];

const FOOT=[
['Shop',[
['Smart Home',shop('household')],
['Kitchen',shop('kitchen')],
['Gaming',shop('entertainment','Gaming Gadgets')],
['Monitors',shop('entertainment','Televisions & Displays')],
['Audio',shop('entertainment','Audio & Music')]
]],
['Help',[
['Delivery & Pickup','help.html#delivery'],
['Returns','help.html#returns'],
['Warranty','help.html#returns'],
['Track Order','track.php'],
['Contact Us','about.html#contact']
]],
['About',[
['Our Story','about.html'],
['SmartTech Care','help.html'],
['Careers','about.html'],
['Stores','about.html#contact'],
['Newsroom','about.html']
]]
];

const peso=n=>'₱'+n.toLocaleString('en-PH');

const set=(sel,html)=>{
    const el=$(sel);
    if(el)el.innerHTML=html;
};

set('#navList',NAV.map(([l,h,a])=>`
<li class="nav-item">
<a class="nav-link${a?' active':''}" href="${h}"${a?' aria-current="page"':''}>
${l}
</a>
</li>
`).join(''));

set('#trustList',TRUST.map(([i,t,d,sh])=>`
<li class="col-6 col-lg-3">
<span class="st-icon-box"><i class="bi bi-${i}"></i></span>
<div>
<b>${t}</b>
<small>
<span class="d-none d-lg-inline">${d}</span>
<span class="d-lg-none">${sh}</span>
</small>
</div>
</li>
`).join(''));

const renderTiles=tiles=>set('#bento',tiles.map(tile=>`
<a class="st-tile ${esc(tile.layout_class||'')}" href="${shop(tile.category,tile.subcategory)}">
<img src="${esc(tile.image)}" alt="" loading="lazy">
<span class="st-icon-box"><i class="bi bi-${esc(tile.icon)}"></i></span>
<span>
<h3>${esc(tile.title)}</h3>
<small>${esc(tile.description)}</small>
</span>
</a>
`).join(''));
renderTiles(TILES.map(([icon,title,description,layout,image,href])=>{
    const target=new URL(href,location.href);
    return{
        icon,title,description,layout_class:layout,image:IMG+image,
        category:target.searchParams.get('cat')||'household',
        subcategory:target.searchParams.get('sub')||''
    };
}));

const renderBundles=bundles=>set('#bundles',bundles.map(bundle=>`
<div class="col-md-6 col-lg-4">
<article class="st-bundle">

<div class="st-media">
<img src="${esc(bundle.image)}" alt="${esc(bundle.title)}" loading="lazy">
${bundle.compare_at_price>bundle.price?`<span class="st-badge">Save ${peso(bundle.compare_at_price-bundle.price)}</span>`:''}
</div>

<div class="st-body">
<h3>${esc(bundle.title)}</h3>
<p>${esc(bundle.description)}</p>

<div class="st-price">
<strong>${peso(bundle.price)}</strong>
${bundle.compare_at_price>bundle.price?`<s class="st-old">${peso(bundle.compare_at_price)}</s>`:''}
</div>

<a href="${esc(bundle.link_url)}" class="btn st-btn st-btn-outline">
View Bundle
</a>
</div>

</article>
</div>
`).join(''));
renderBundles(BUNDLES.map(([title,description,price,compare_at_price,image,link_url])=>({
    title,description,price,compare_at_price,image:IMG+image,link_url
})));

set('#brands',BRANDS.map(b=>`<li>${b}</li>`).join(''));

set('#services',SERVICES.map(([i,t,d,h])=>`
<div class="col-md-6 col-lg-3">
<article class="st-svc">

<span class="st-icon-box">
<i class="bi bi-${i}"></i>
</span>

<h3>${t}</h3>
<p>${d}</p>

<a href="${h}">
Learn more <i class="bi bi-chevron-right"></i>
</a>

</article>
</div>
`).join(''));

const renderPromos=promos=>set('#promos',promos.map(promo=>`
<div class="col-lg-6">

<article class="st-promo">

<img src="${esc(promo.image)}" alt="${esc(promo.image_alt||'')}" loading="lazy">

<div>
<h3>${esc(promo.title)}</h3>
<p>
<span>${esc(promo.description)}</span>
</p>
</div>

<a href="${esc(promo.product_code?'product.php?id='+encodeURIComponent(promo.product_code):promo.link_url)}" class="btn st-btn st-btn-accent align-self-start">
${esc(promo.button_label)}
</a>

</article>

</div>
`).join(''));
renderPromos(PROMOS.map(([title,description,button_label,image,image_alt,link_url])=>({
    title,description,button_label,image:IMG+image,image_alt,link_url
})));

function renderDeal(deal){
    const section=$('#deal');
    if(!section)return;
    if(!deal){
        section.hidden=true;
        return;
    }
    section.hidden=false;
    const image=$('#dealImage');
    image.src=String(deal.image||deal.product_image||'');
    image.alt=String(deal.title||deal.product_name||'Featured product');
    $('#dealTitle').textContent=String(deal.title||deal.product_name||'Featured product');
    $('#dealDescription').textContent=String(deal.description||'');
    const price=Number(deal.product_price||0);
    const compareAt=Number(deal.compare_at_price||0);
    $('#dealPrice').textContent=peso(price);
    const compare=$('#dealCompareAt');
    compare.textContent=peso(compareAt);
    compare.hidden=compareAt<=price;
    $('#dealTag').textContent=compareAt>price?'SAVE '+peso(compareAt-price):'FEATURED PRODUCT';
    $('#dealLink').href='product.php?id='+encodeURIComponent(String(deal.product_code));
    renderCountdown(deal.ends_at);
}

let wish=[];

try{
    wish=JSON.parse(localStorage.getItem('st_wishlist'))||[];
}catch(e){}

function card(p){
    const on=wish.includes(p.id);
    const href='product.php?id='+encodeURIComponent(p.id);
    const out=p.stock<=0;
    const low=p.stock>0&&p.stock<=9;
    const stockMessage=low
        ?`Only ${p.stock} left — order soon`
        :out
        ?'Currently unavailable'
        :'';

    return`
    <article class="st-card${out?' out':''}">

        <div class="st-media">

            <a href="${href}" tabindex="-1">
                <img src="${esc(p.i.startsWith('images/')||p.i.startsWith('uploads/')?p.i:IMG+p.i)}" alt="${esc(p.n)}" loading="lazy">
            </a>

            <div class="top">

                <span class="st-badge${out?' dark':''}">
                    ${out?'Out of stock':p.b}
                </span>

                <button class="st-wish" data-id="${esc(p.id)}" aria-pressed="${on}">
                    <i class="bi bi-heart${on?'-fill':''}"></i>
                </button>

            </div>

        </div>

        <div class="st-body">

            <h3>
                <a href="${href}" style="text-decoration:none">
                    ${esc(p.n)}
                </a>
            </h3>

            <a class="st-rating" href="${href}" aria-label="See product details and reviews">
                <span>See details &amp; reviews</span>
            </a>

            <p class="st-low"${stockMessage?'':' aria-hidden="true"'}>${stockMessage}</p>

            <div class="st-price">
                <strong>${peso(p.p)}</strong>
                ${Number(p.o)>Number(p.p)?`<s class="st-old">${peso(p.o)}</s>`:''}
            </div>

            <button
                class="st-cart${out?' alt':''}"
                data-id="${esc(p.id)}"
                ${out?'data-out="1" disabled':''}
            >
                ${out?'Out of Stock':'Add to Cart'}
            </button>

        </div>

    </article>`;
}

function updateRailControls(){
    const rail=$('#rail');
    if(!rail)return;
    const maxScroll=rail.scrollWidth-rail.clientWidth;
    const previous=$('#railPrev');
    const next=$('#railNext');
    if(previous)previous.disabled=rail.scrollLeft<=1;
    if(next)next.disabled=maxScroll<=1||rail.scrollLeft>=maxScroll-1;
}

document.addEventListener('click',event=>{
    const card=event.target.closest('.st-card');
    if(!card||event.target.closest('button,a'))return;
    const link=card.querySelector('.st-media a');
    if(link)location.href=link.href;
});

function renderProducts(tab){
    const list=tab==='all'
        ?CARDS
        :CARDS.filter(p=>p.t.includes(tab));

    set('#rail',list.map(card).join(''));
    updateRailControls();

    if(drawProg){
        $('#rail').scrollLeft=0;
        drawProg();
    }
}

set('#tabs',TABS.map(([k,l],i)=>`
<button
class="st-tab"
role="tab"
id="tab-${k}"
data-tab="${k}"
aria-selected="${i===0}"
tabindex="${i?-1:0}"
>
${l}
</button>
`).join(''));

$('#tabs')?.addEventListener('click',e=>{
    const b=e.target.closest('.st-tab');

    if(!b)return;

    document.querySelectorAll('.st-tab').forEach(t=>{
        const on=t===b;
        t.setAttribute('aria-selected',on);
        t.tabIndex=on?0:-1;
    });

    renderProducts(b.dataset.tab);
});

function cartCount(){
    let cart=[];

    try{
        cart=JSON.parse(localStorage.getItem('st_cart'))||[];
    }catch(e){}

    set(
        '#cart-count',
        cart.reduce((t,i)=>t+Number(i.qty||1),0)
    );
}

function cartAdd(id){
    const p=CARDS.find(x=>x.id===id);

    if(!p)return false;

    if(p.stock<=0){
        alert(`${p.n} is out of stock.`);
        return false;
    }

    let cart=[];

    try{
        cart=JSON.parse(localStorage.getItem('st_cart'))||[];
    }catch(e){}

    const item=cart.find(x=>x.id===id);
    const qty=item?Number(item.qty||1):0;

    if(qty>=p.stock){
        alert(`Only ${p.stock} stock available for ${p.n}.`);
        return false;
    }

    if(item){
        item.qty=qty+1;
    }else{
        cart.push({
            id:id,
            name:p.n,
            price:p.p,
            qty:1,
            img:p.i.startsWith('images/')||p.i.startsWith('uploads/')?p.i:IMG+p.i
        });
    }

    try{
        localStorage.setItem('st_cart',JSON.stringify(cart));
    }catch(e){}

    cartCount();

    return true;
}

$('#rail')?.addEventListener('click',e=>{
    const wishButton=e.target.closest('.st-wish');

    if(wishButton){
        const id=wishButton.dataset.id;
        const index=wish.indexOf(id);

        if(index>-1){
            wish.splice(index,1);
        }else{
            wish.push(id);
        }

        localStorage.setItem(
            'st_wishlist',
            JSON.stringify(wish)
        );

        const on=wish.includes(id);

        wishButton.setAttribute(
            'aria-pressed',
            on
        );

        wishButton.innerHTML=
        `<i class="bi bi-heart${on?'-fill':''}"></i>`;

        return;
    }

    const button=e.target.closest('.st-cart');

    if(button&&!button.dataset.out){

        if(cartAdd(button.dataset.id)){
            button.textContent='Added ✓';

            setTimeout(()=>{
                button.textContent='Add to Cart';
            },1200);
        }
    }
});

cartCount();

$('#railPrev')?.addEventListener('click',()=>{
    $('#rail')?.scrollBy({left:-306,behavior:'smooth'});
});
$('#railNext')?.addEventListener('click',()=>{
    $('#rail')?.scrollBy({left:306,behavior:'smooth'});
});
$('#rail')?.addEventListener('scroll',()=>requestAnimationFrame(updateRailControls),{passive:true});
window.addEventListener('resize',updateRailControls);

renderProducts('all');

fetch('php/get_storefront_content.php',{headers:{Accept:'application/json'}})
    .then(response=>{
        if(!response.ok)throw new Error('Storefront content request failed: '+response.status);
        return response.json();
    })
    .then(content=>{
        if(!Array.isArray(content.products)||!Array.isArray(content.promos)||!Array.isArray(content.bundles)||!Array.isArray(content.categories)||!Object.prototype.hasOwnProperty.call(content,'deal')||typeof content.demo_video!=='string'||!content.demo_video){
            throw new Error('Storefront content response is incomplete.');
        }
        const featuredById=new Map(FEATURED.map(item=>[item.id,item]));
        CARDS=content.products.map(product=>{
            const featured=featuredById.get(product.id);
            return{
                id:String(product.id),
                n:product.name,
                p:Number(product.price),
                o:featured?featured.o:0,
                i:String(product.img||''),
                stock:Number(product.stock||0),
                b:featured?featured.b:'Featured',
                t:featured?featured.t:[]
            };
        });
        renderProducts('all');
        renderTiles(content.categories.map(category=>({
            ...category,
            image:String(category.image||'')
        })));
        const productById=new Map(content.products.map(product=>[product.id,product]));
        renderBundles(content.bundles.map(bundle=>({
            ...bundle,
            image:String(bundle.image||''),
            description:bundle.description||bundle.product_codes
                .map(code=>productById.get(code)?.name||code)
                .join(' · ')
        })));
        renderPromos(content.promos.map(promo=>({
            ...promo,
            image:String(promo.image||'')
        })));
        renderDeal(content.deal);
        const demoVideoSource=$('#demoDialog video source');
        if(demoVideoSource&&demoVideoSource.getAttribute('src')!==content.demo_video){
            demoVideoSource.src=content.demo_video;
            demoVideoSource.removeAttribute('type');
            $('#demoDialog video').load();
        }
    })
    .catch(error=>console.error('Unable to load the latest storefront content.',error));

document.querySelector('.st-search input')?.addEventListener('click',()=>{
    document.querySelector('.stq-btn')?.click();
});

const demoDialog=$('#demoDialog');
const demoVideo=demoDialog?.querySelector('video');
$('#watchDemoButton')?.addEventListener('click',()=>{
    if(demoDialog&&!demoDialog.open)demoDialog.showModal();
});
demoDialog?.querySelector('.st-demo-dialog-close')?.addEventListener('click',()=>{
    demoDialog.close();
});
demoDialog?.addEventListener('click',event=>{
    if(event.target===demoDialog)demoDialog.close();
});
demoDialog?.addEventListener('close',()=>{
    demoVideo?.pause();
    if(demoVideo)demoVideo.currentTime=0;
});

let countdownTimer=null;
function renderCountdown(value){
    const countdown=$('#countdown');
    if(countdownTimer)clearInterval(countdownTimer);
    countdownTimer=null;
    if(!value){
        countdown.textContent='';
        countdown.style.display='none';
        return;
    }
    const end=new Date(String(value).replace(' ','T')).getTime();
    if(Number.isNaN(end)){
        console.error('The deal end date is invalid.');
        countdown.textContent='';
        countdown.style.display='none';
        return;
    }
    countdown.style.display='flex';
    const tick=()=>{
    let s=Math.max(
        0,
        Math.floor((end-Date.now())/1000)
    );

    const units=[
        ['Days',86400],
        ['Hours',3600],
        ['Mins',60],
        ['Secs',1]
    ].map(([label,size])=>{
        const value=Math.floor(s/size);
        s%=size;

        return`
        <div class="st-cd">
            <b>${String(value).padStart(2,'0')}</b>
            <small>${label}</small>
        </div>`;
    });

    set('#countdown',units.join(''));
    };
    tick();
    countdownTimer=setInterval(tick,1000);
}

const form=$('#nlForm');
const email=$('#nlEmail');
const msg=$('#nlMsg');

const EMAIL_RE=/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

const show=(text,ok)=>{
    if(!msg)return;

    msg.textContent=text;
    msg.className='st-msg '+(ok?'ok':'err');

    if(email){
        email.setAttribute(
            'aria-invalid',
            String(!ok)
        );
    }
};

form?.addEventListener('submit',e=>{
    e.preventDefault();

    const value=email.value.trim();

    if(!value){
        return show(
            'Enter your email address.',
            false
        );
    }

    if(!EMAIL_RE.test(value)){
        return show(
            'Enter a valid email, like name@example.com.',
            false
        );
    }

    show(
        'You’re in! Thanks for subscribing.',
        true
    );

    form.reset();
});

set('#reviews',REVIEWS.map(([quote,name,city])=>`
<div class="col-lg-4">

<article class="st-review">

<p class="st-rating">
<span class="st-stars">★★★★★</span>
<b>5.0</b>
</p>

<blockquote>
“${esc(quote)}”
</blockquote>

<footer>

<span class="st-avatar">
${name[0]}
</span>

<div>
<b>${name}</b>
<small>${city} · Verified buyer</small>
</div>

</footer>

</article>

</div>
`).join(''));

function progress(rail,prog){
    if(!rail||!prog)return()=>{};

    const draw=()=>{
        const n=rail.children.length;

        const index=n
            ?Math.min(
                n-1,
                Math.round(
                    rail.scrollLeft/
                    (rail.scrollWidth/n)
                )
            )
            :0;

        prog.innerHTML=Array.from(
            {length:n},
            (_,i)=>`<span${i===index?' class="on"':''}></span>`
        ).join('');
    };

    rail.addEventListener(
        'scroll',
        ()=>requestAnimationFrame(draw),
        {passive:true}
    );

    draw();

    return draw;
}

drawProg=progress(
    $('#rail'),
    $('#railProg')
);