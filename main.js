const IMG='images/';
const $=s=>document.querySelector(s);
const esc=s=>String(s).replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));

const shop=(cat,sub)=>'products.php'+(cat?'?cat='+cat:'')+(sub?'&sub='+encodeURIComponent(sub):'');

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

const CARDS=FEATURED.map(f=>{
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

set('#bento',TILES.map(([i,t,d,c,img,h])=>`
<a class="st-tile ${c}" href="${h}">
<img src="${IMG}${img}" alt="" loading="lazy">
<span class="st-icon-box"><i class="bi bi-${i}"></i></span>
<span>
<h3>${t}</h3>
<small>${d}</small>
</span>
</a>
`).join(''));

set('#bundles',BUNDLES.map(([t,d,p,o,img,h])=>`
<div class="col-md-6 col-lg-4">
<article class="st-bundle">

<div class="st-media">
<img src="${IMG}${img}" alt="${esc(t)}" loading="lazy">
<span class="st-badge">Save ${peso(o-p)}</span>
</div>

<div class="st-body">
<h3>${t}</h3>
<p>${d}</p>

<div class="st-price">
<strong>${peso(p)}</strong>
<s class="st-old">${peso(o)}</s>
</div>

<a href="${h}" class="btn st-btn st-btn-outline">
View Bundle
</a>
</div>

</article>
</div>
`).join(''));

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

set('#promos',PROMOS.map(([t,d,b,img,sh,h])=>`
<div class="col-lg-6">

<article class="st-promo">

<img src="${IMG}${img}" alt="" loading="lazy">

<div>
<h3>${t}</h3>
<p>
<span class="d-none d-lg-inline">${d}</span>
<span class="d-lg-none">${sh}</span>
</p>
</div>

<a href="${h}" class="btn st-btn st-btn-accent align-self-start">
${b}
</a>

</article>

</div>
`).join(''));

let wish=[];

try{
    wish=JSON.parse(localStorage.getItem('st_wishlist'))||[];
}catch(e){}

function card(p){
    const on=wish.includes(p.id);
    const href='products.php?product='+p.id;
    const out=p.stock<=0;
    const low=p.stock>0&&p.stock<=9;

    return`
    <article class="st-card${out?' out':''}">

        <div class="st-media">

            <a href="${href}" tabindex="-1">
                <img src="${IMG}${p.i}" alt="${esc(p.n)}" loading="lazy">
            </a>

            <div class="top">

                <span class="st-badge${out?' dark':''}">
                    ${out?'Out of stock':p.b}
                </span>

                <button class="st-wish" data-id="${p.id}" aria-pressed="${on}">
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

            <div class="st-rating">
                <span class="st-stars">★★★★★</span>
                <b>${p.s}</b>
                <span>(${p.c})</span>
            </div>

            ${
                low
                ?`<p class="st-low">Only ${p.stock} left — order soon</p>`
                :out
                ?'<p class="st-low">Currently unavailable</p>'
                :''
            }

            <div class="st-price">
                <strong>${peso(p.p)}</strong>
                <s class="st-old">${peso(p.o)}</s>
            </div>

            <button
                class="st-cart${out?' alt':''}"
                data-id="${p.id}"
                ${out?'data-out="1" disabled':''}
            >
                ${out?'Out of Stock':'Add to Cart'}
            </button>

        </div>

    </article>`;
}

function renderProducts(tab){
    const list=tab==='all'
        ?CARDS
        :CARDS.filter(p=>p.t.includes(tab));

    set('#rail',list.map(card).join(''));

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
            img:IMG+p.i
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

$('#railNext')?.addEventListener('click',()=>{
    $('#rail').scrollBy({
        left:306,
        behavior:'smooth'
    });
});

renderProducts('all');

document.querySelector('.st-search input')?.addEventListener('click',()=>{
    document.querySelector('.stq-btn')?.click();
});

const end=Date.now()+(2*864e5+14*36e5+36*6e4+8e3);

function tick(){
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
}

tick();
setInterval(tick,1000);

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