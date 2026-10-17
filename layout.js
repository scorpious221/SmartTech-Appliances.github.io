(function(){
const page=document.body.dataset.page||'';

const nav=[
    ['Home','index.html'],
    ['Household','products.php?cat=household'],
    ['Entertainment','products.php?cat=entertainment'],
    ['Kitchen','products.php?cat=kitchen']
];

const logo='<a class="navbar-brand st-logo order-1 me-auto" href="index.html" aria-label="SmartTech home"><span class="st-mark d-lg-none"><i class="bi bi-house" aria-hidden="true"></i></span><img class="d-none d-lg-block" src="images/smarttechappliances.svg" alt="" width="60" height="49"><span>SmartTech</span></a>';

const head=`
<a class="visually-hidden-focusable st-skip" href="#main">Skip to content</a>

<div class="st-topbar text-center">
Free delivery over ₱3,000 • 1-Year Warranty • GCash, Maya, COD
</div>

<header class="st-header sticky-top">
<nav class="navbar navbar-expand-lg navbar-dark container-xxl px-lg-5" aria-label="Primary">

${logo}

<div class="d-flex gap-2 order-2 order-lg-3 ms-lg-4">

<button class="st-icon-btn d-lg-none" type="button" aria-label="Search" onclick="document.querySelector('.stq-btn')?.click()">
<i class="bi bi-search"></i>
</button>

<a class="st-icon-btn d-none d-lg-inline-grid" href="wishlist.html" aria-label="Wishlist">
<i class="bi bi-heart"></i>
</a>

<a class="st-icon-btn st-cart-btn" href="cart.php" aria-label="Cart">
<i class="bi bi-bag"></i>
<span class="st-count" id="cart-count">0</span>
</a>

<a class="st-icon-btn d-none d-lg-inline-grid" href="login.php" aria-label="Account">
<i class="bi bi-person"></i>
</a>

</div>

<div class="collapse navbar-collapse order-3 order-lg-2" id="nav">

<ul class="navbar-nav me-lg-4 gap-lg-3 pt-2 pt-lg-0">
${nav.map(([label,href])=>`
<li class="nav-item">
<a class="nav-link" href="${href}">${label}</a>
</li>
`).join('')}
</ul>

<form class="st-search ms-lg-auto flex-grow-1 my-3 my-lg-0" role="search" onsubmit="return false">
<i class="bi bi-search" aria-hidden="true"></i>
<input type="search" class="form-control" placeholder="Search appliances" aria-label="Search products" readonly>
</form>

</div>

</nav>
</header>

<span class="st-icons" hidden></span>
`;

const foot=`
<footer class="st-footer">
<div class="container-xxl px-lg-5">

<div class="sf-top">

<div class="sf-info">

<a class="st-logo mb-3" href="index.html">

<span class="st-mark d-lg-none">
<i class="bi bi-house" aria-hidden="true"></i>
</span>

<img class="d-none d-lg-block" src="images/smarttechappliances.svg" alt="" width="60" height="49">

<span>SmartTech</span>

</a>

<p class="sf-blurb">
Smarter essentials for Filipino homes. Bringing comfort, convenience and a little more ease to every day.
</p>

<div class="sf-contact">

<b>LET’S TALK</b>

<a href="mailto:hello@smarttech.example">
<i class="bi bi-envelope" aria-hidden="true"></i>
hello@smarttech.example
</a>

<small>Concept store • illustrative contact only</small>

</div>

</div>

<div class="sf-cols">

<nav aria-label="Explore">

<h3>Explore</h3>

<ul>
<li><a href="products.php?cat=household">Household</a></li>
<li><a href="products.php?cat=entertainment">Entertainment</a></li>
<li><a href="products.php?cat=kitchen">Kitchen</a></li>
<li><a href="about.html">About Us</a></li>
</ul>

</nav>

<nav aria-label="Customer care">

<h3>Customer care</h3>

<ul>
<li><a href="help.html#delivery">Delivery information</a></li>
<li><a href="help.html#returns">Returns &amp; warranty</a></li>
<li><a href="help.html#payment">Payment options</a></li>
<li><a href="help.html#faq">FAQs</a></li>
</ul>

</nav>

<nav aria-label="Your SmartTech">

<h3>Your SmartTech</h3>

<ul>
<li><a href="login.php">My account</a></li>
<li><a href="track.php">Track my order</a></li>
<li><a href="wishlist.html">My wishlist</a></li>
<li><a href="about.html#contact">Contact us</a></li>
</ul>

</nav>

</div>

</div>

<div class="sf-bottom">

<div class="sf-legal">
<small>© 2026 SmartTech Appliances. Concept design.</small>
<small>
<a href="policies.html#privacy">Privacy policy</a>
&nbsp;•&nbsp;
<a href="policies.html#terms">Terms &amp; conditions</a>
</small>
</div>

<div class="sf-pay">

<small>PAY YOUR WAY</small>

<ul aria-label="Accepted payments">
<li>GCash</li>
<li>Maya</li>
<li>COD</li>
<li>VISA</li>
</ul>

</div>

</div>

</div>
</footer>

<nav class="st-bottomnav d-lg-none" aria-label="Quick navigation">

${[
    ['index.html','house','Home','home'],
    ['products.php','shop','Shop','shop'],
    ['wishlist.html','heart','Wishlist','wishlist'],
    ['cart.php','bag','Cart','cart'],
    ['login.php','person','Account','login']
].map(([href,icon,label,key])=>`
<a href="${href}"${key===page?' class="active" aria-current="page"':''}>
<i class="bi bi-${icon}" aria-hidden="true"></i>
<span>${label}</span>
</a>
`).join('')}

</nav>
`;

document.body.insertAdjacentHTML('afterbegin',head);
document.body.insertAdjacentHTML('beforeend',foot);

const main=document.getElementById('main');
const footer=document.querySelector('.st-footer');

if(main&&footer){
    document.body.insertBefore(main,footer);
}

window.stCartCount=function(){
    let cart=[];

    try{
        cart=JSON.parse(localStorage.getItem('st_cart'))||[];
    }catch(e){}

    const el=document.getElementById('cart-count');

    if(el){
        el.textContent=cart.reduce(
            (total,item)=>total+Number(item.qty||1),
            0
        );
    }
};

stCartCount();

const backToTopScript=document.createElement('script');
backToTopScript.src=new URL('back-to-top.js',document.currentScript.src).href;
document.body.appendChild(backToTopScript);

})();