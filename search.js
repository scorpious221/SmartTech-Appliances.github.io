(function(){

const CAT={
    household:'Household Appliances',
    entertainment:'Entertainment Appliances',
    kitchen:'Kitchen Appliances'
};

const RAW=[
    ['smoke','Smoke Detector',2500,'smokedetector.JPG','household','Security Devices'],
    ['motion','Motion Sensor',5330,'motionsensor.jpg','household','Security Devices'],
    ['doorknob','Smart Doorknob',10350,'smartdoorknob.jpg','household','Security Devices'],
    ['doorbell','Video Doorbell',13500,'vidbell.jpg','household','Security Devices'],
    ['cctv','CCTV Camera',8900,'cctv.jpg','household','Security Devices'],
    ['kettle','Electric Kettle',1080,'kettle.jpg','household','Utilities'],
    ['solar','Solar Panel',1590,'solarpanel.jpg','household','Utilities'],
    ['sewing','Sewing Machine',38000,'sewingmachine.jpg','household','Utilities'],
    ['avr','Voltage Regulator',18899,'voltagereg.jpg','household','Utilities'],
    ['generator','Portable Generator',10000,'portablegen.jpg','household','Utilities'],
    ['printer','Printer',23000,'printer.jpg','household','Home Office'],
    ['shredder','Paper Shredder',16888,'papershredder.jpg','household','Home Office'],
    ['laminator','Laminator',4550,'laminator.jpg','household','Home Office'],
    ['photocopy','Photocopy Machine',28999,'photocopy.jpg','household','Home Office'],
    ['multiport','Multi-Port Hubs',7999,'multiport.jpg','household','Home Office'],
    ['lcd','LCD Monitor',8900,'lcd.jpg','entertainment','Televisions & Displays'],
    ['smarttv','Smart TV',10679,'stv.jpg','entertainment','Televisions & Displays'],
    ['projector','Projector',36000,'proj.jpg','entertainment','Televisions & Displays'],
    ['cmon','Computer Monitor',30000,'computermonitor.jpg','entertainment','Televisions & Displays'],
    ['oled','OLED TV',17988,'oled.jpg','entertainment','Televisions & Displays'],
    ['karaoke','Karaoke Machine',18199,'karaokemachine.jpg','entertainment','Audio & Music'],
    ['radio','Portable Radio',3651,'radio.jpg','entertainment','Audio & Music'],
    ['amplifier','Amplifier',2950,'ampli.jpg','entertainment','Audio & Music'],
    ['turntable','Turn Table',12999,'turntable.jpg','entertainment','Audio & Music'],
    ['headphones','Wireless Headphones',2599,'headphones.jpg','entertainment','Audio & Music'],
    ['ps','PlayStation',44150,'ps5.jpg','entertainment','Gaming Gadgets'],
    ['nsw2','Nintendo Switch 2',27693,'nsw2.jpg','entertainment','Gaming Gadgets'],
    ['rwheel','Racing Wheel Simulator',14995,'rwheel.jpg','entertainment','Gaming Gadgets'],
    ['controller','Gaming Controller',1970,'controller.jpg','entertainment','Gaming Gadgets'],
    ['vr','VR Headset',30334,'vr.jpg','entertainment','Gaming Gadgets'],
    ['fridge','Refrigerator',96000,'ref.jpg','kitchen','Refrigeration'],
    ['icecream','Ice Cream Maker',18995,'icecream.jpg','kitchen','Refrigeration'],
    ['icemaker','Ice Maker',7699,'icemaker.jpg','kitchen','Refrigeration'],
    ['dispenser','Water Dispenser',5898,'dispenser.jpg','kitchen','Refrigeration'],
    ['winecooler','Wine Cooler',6590,'wine.jpg','kitchen','Refrigeration'],
    ['airfryer','Air Fryer',2599,'afryer.jpg','kitchen','Cooking Appliances'],
    ['ricecook','Rice Cooker',2649,'ricecooker.jpg','kitchen','Cooking Appliances'],
    ['stove','Stove',99999,'stove.jpg','kitchen','Cooking Appliances'],
    ['oven','Oven',89995,'oven.jpg','kitchen','Cooking Appliances'],
    ['toaster','Toaster',1475,'toaster.jpg','kitchen','Cooking Appliances'],
    ['blender','Blender',3085,'blender.jpg','kitchen','Food Preparation'],
    ['fprocessor','Food Processor',2850,'fprocessor.jpg','kitchen','Food Preparation'],
    ['mgrinder','Meat Grinder',8790,'mgrinder.jpg','kitchen','Food Preparation'],
    ['cgrinder','Coffee Grinder',8790,'cgrinder.jpg','kitchen','Food Preparation'],
    ['smixer','Stand Mixer',8790,'mixer.jpg','kitchen','Food Preparation']
];

const FALLBACK=RAW.map(r=>({
    id:r[0],
    name:r[1],
    price:r[2],
    img:'images/'+r[3],
    category:r[4],
    sub:r[5]
}));

const scriptUrl=document.currentScript.src;
const PRODUCTS_PAGE=new URL('product.php',scriptUrl).href;
const assetUrl=path=>new URL(path,scriptUrl).href;

const onProductsPage=()=>{
    return typeof PRODUCTS!=='undefined'&&
        typeof showDetails==='function'&&
        typeof renderProducts==='function';
};

const savedCatalog=()=>{
    try{
        const value=JSON.parse(localStorage.getItem('st_catalog'));
        return Array.isArray(value)?value:null;
    }catch(e){
        return null;
    }
};

let databaseCatalog=null;
let catalogRequest=0;
const catalog=()=>{
    if(databaseCatalog)return databaseCatalog;
    if(typeof PRODUCTS!=='undefined')return PRODUCTS;

    return savedCatalog()||FALLBACK;
};

function refreshCatalog(){
    const request=++catalogRequest;
    return fetch(new URL('php/get_storefront_content.php',scriptUrl),{headers:{Accept:'application/json'}})
        .then(response=>{
            if(!response.ok)throw new Error('Product search catalog request failed: '+response.status);
            return response.json();
        })
        .then(content=>{
            if(!Array.isArray(content.products))throw new Error('Product search catalog response is invalid.');
            if(request!==catalogRequest)return;
            databaseCatalog=content.products;
            if(form.classList.contains('stq-open')&&input.value.trim())render();
        })
        .catch(error=>console.error('Unable to refresh the product search catalog.',error));
}

const css=`
.st-search{z-index:20}
.st-search input{cursor:text}
.stq-res{position:absolute;top:calc(100% + 8px);left:0;right:0;z-index:3000;display:none;max-height:min(60vh,480px);overflow:auto;padding:8px;background:#fff;border:1px solid #e5e0d5;border-radius:14px;box-shadow:0 12px 32px rgba(0,0,0,.2);font-family:"Plus Jakarta Sans",system-ui,sans-serif;color:#1b2033}
.st-search.stq-open.stq-has-query .stq-res{display:block}
.stq-hit{display:flex;gap:12px;align-items:center;padding:8px 10px;border-radius:10px;width:100%;border:0;background:none;text-align:left;cursor:pointer;font:inherit;color:inherit}
.stq-hit:hover,.stq-hit:focus{background:#f7f5f0;outline:none}
.stq-hit img{width:46px;height:46px;object-fit:contain;background:#f1f2f6;border-radius:8px;padding:4px;flex-shrink:0}
.stq-hit strong{font-size:14px;font-weight:700}
.stq-none{text-align:center;color:#6b7280;margin:16px 0}
`;

const style=document.createElement('style');
style.textContent=css;
document.head.appendChild(style);

const form=document.querySelector('.st-search');
const input=form?.querySelector('input');
window.addEventListener('load',()=>{
    const id=new URLSearchParams(location.search).get('product');

    if(!id||!onProductsPage())return;

    const product=PRODUCTS.find(p=>p.id===id);

    if(product){
        location.replace(PRODUCTS_PAGE+'?id='+encodeURIComponent(product.id));
    }
});

if(!form||!input)return;
input.readOnly=false;
input.placeholder='Search products (e.g. air fryer, kitchen, TV)';
input.autocomplete='off';

const res=document.createElement('div');
res.className='stq-res';
res.setAttribute('role','listbox');
res.setAttribute('aria-label','Search results');
form.appendChild(res);

refreshCatalog();

const esc=s=>String(s).replace(/[&<>"']/g,c=>({
    '&':'&amp;',
    '<':'&lt;',
    '>':'&gt;',
    '"':'&quot;',
    "'":'&#39;'
}[c]));

function render(){
    const tokens=input.value
        .trim()
        .toLowerCase()
        .split(/\s+/)
        .filter(Boolean);

    form.classList.toggle('stq-has-query',tokens.length>0);
    if(!tokens.length){
        res.innerHTML='';
        return;
    }

    const list=catalog().filter(p=>{
        const hay=(
            p.name+' '+
            p.sub+' '+
            p.category+' '+
            (CAT[p.category]||'')
        ).toLowerCase();

        return tokens.every(token=>hay.includes(token));
    });

    if(!list.length){
        res.innerHTML='<p class="stq-none">No matches. Try another keyword.</p>';
        return;
    }

    res.innerHTML=list.map(p=>`
    <button class="stq-hit" data-id="${esc(p.id)}">
        <img
        src="${esc(assetUrl(p.img))}"
        alt=""
        onerror="this.style.visibility='hidden'"
        >
        <strong>${esc(p.name)}</strong>
    </button>
    `).join('');
}

function open(){
    form.classList.add('stq-open');
    if(window.matchMedia('(max-width: 991.98px)').matches){
        form.closest('.st-header')?.classList.add('stq-mobile-search-open');
    }
    render();
    refreshCatalog();

    const collapse=form.closest('.navbar-collapse');
    if(collapse&&!collapse.classList.contains('show')){
        const instance=window.bootstrap&&window.bootstrap.Collapse
            ?window.bootstrap.Collapse.getOrCreateInstance(collapse)
            :null;
        if(instance){
            collapse.addEventListener('shown.bs.collapse',()=>input.focus(),{once:true});
            instance.show();
            return;
        }
        collapse.classList.add('show');
    }
    if(document.activeElement!==input)input.focus();
}

function close(){
    form.classList.remove('stq-open');
    form.classList.remove('stq-has-query');
    form.closest('.st-header')?.classList.remove('stq-mobile-search-open');
}

function showProduct(product){
    currentCategory=product.category;
    currentSubcat=product.sub;

    renderTopCats();
    renderSubcats();
    renderProducts();
    showDetails(product.id);
}

function choose(id){
    const product=catalog().find(p=>p.id===id);

    if(!product)return;

    close();

    location.href=PRODUCTS_PAGE+'?id='+encodeURIComponent(product.id);
}

input.addEventListener('input',render);

form.addEventListener('click',event=>{
    event.preventDefault();
    open();
});
form.addEventListener('submit',event=>event.preventDefault());

input.addEventListener('keydown',e=>{
    if(e.key!=='Enter')return;

    const first=res.querySelector('.stq-hit');

    if(first){
        choose(first.dataset.id);
    }
});

res.addEventListener('click',e=>{
    const button=e.target.closest('.stq-hit');

    if(button){
        choose(button.dataset.id);
    }
});

document.addEventListener('keydown',e=>{
    if(e.key==='Escape'){
        close();
    }
});
document.addEventListener('click',e=>{
    if(!form.contains(e.target)&&
        !e.target.closest('.stq-btn')&&
        !e.target.closest('.st-header button[aria-label="Search"]'))close();
});

const button=document.createElement('button');

button.type='button';
button.className='stq-btn';
button.setAttribute('aria-label','Search');

button.innerHTML=`
<svg viewBox="0 0 512 512" fill="currentColor" aria-hidden="true">
<path d="M416 208c0 45.9-14.9 88.3-40 122.7l126.6 126.7c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376C296.3 401.1 253.9 416 208 416 93.1 416 0 322.9 0 208S93.1 0 208 0s208 93.1 208 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z"/>
</svg>
`;

button.addEventListener('click',open);

const icons=document.querySelector('.st-icons');

if(icons){
    icons.insertBefore(button,icons.firstChild);
}else{
    button.classList.add('stq-float');
    document.body.appendChild(button);
}

})();