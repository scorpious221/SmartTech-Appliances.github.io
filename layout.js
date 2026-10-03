/* SmartTech shared layout — header, footer, bottom nav para sa wishlist/login/help/about/track.
   Gamitin: <body data-page="help"> at <script src="layout.js"></script> sa dulo ng body. */
(function () {
  const page = document.body.dataset.page || '';
  const nav = [['Home','homelandingpage.html'],['Household','products.html?cat=household'],['Entertainment','products.html?cat=entertainment'],['Kitchen','products.html?cat=kitchen']];
  const logo = '<a class="navbar-brand st-logo order-1 me-auto" href="homelandingpage.html" aria-label="SmartTech home"><span class="st-mark d-lg-none"><i class="bi bi-house" aria-hidden="true"></i></span><img class="d-none d-lg-block" src="images/smarttechappliances.svg" alt="" width="60" height="49"><span>SmartTech</span></a>';
  const head = `<a class="visually-hidden-focusable st-skip" href="#main">Skip to content</a>
<div class="st-topbar text-center">Free delivery over ₱3,000 • 1-Year Warranty • GCash, Maya, COD</div>
<header class="st-header sticky-top"><nav class="navbar navbar-expand-lg navbar-dark container-xxl px-lg-5" aria-label="Primary">
${logo}
<div class="d-flex gap-2 order-2 order-lg-3 ms-lg-4">
<button class="st-icon-btn d-lg-none" type="button" aria-label="Search" onclick="document.querySelector('.stq-btn')?.click()"><i class="bi bi-search"></i></button>
<a class="st-icon-btn d-none d-lg-inline-grid" href="wishlist.html" aria-label="Wishlist"><i class="bi bi-heart"></i></a>
<a class="st-icon-btn st-cart-btn" href="cart.html" aria-label="Cart"><i class="bi bi-bag"></i><span class="st-count" id="cart-count">0</span></a>
<a class="st-icon-btn d-none d-lg-inline-grid" href="login.html" aria-label="Account"><i class="bi bi-person"></i></a></div>
<div class="collapse navbar-collapse order-3 order-lg-2" id="nav"><ul class="navbar-nav me-lg-4 gap-lg-3 pt-2 pt-lg-0">${nav.map(([l,h]) => `<li class="nav-item"><a class="nav-link" href="${h}">${l}</a></li>`).join('')}</ul>
<form class="st-search ms-lg-auto flex-grow-1 my-3 my-lg-0" role="search" onsubmit="return false"><i class="bi bi-search" aria-hidden="true"></i><input type="search" class="form-control" placeholder="Search appliances" aria-label="Search products" readonly></form></div>
</nav></header><span class="st-icons" hidden></span>`;
  const foot = `<footer class="st-footer"><div class="container-xxl px-lg-5"><div class="sf-top">
<div class="sf-info"><a class="st-logo mb-3" href="homelandingpage.html"><span class="st-mark d-lg-none"><i class="bi bi-house" aria-hidden="true"></i></span><img class="d-none d-lg-block" src="images/smarttechappliances.svg" alt="" width="60" height="49"><span>SmartTech</span></a>
<p class="sf-blurb">Smarter essentials for Filipino homes. Bringing comfort, convenience and a little more ease to every day.</p>
<div class="sf-contact"><b>LET’S TALK</b><a href="mailto:hello@smarttech.example"><i class="bi bi-envelope" aria-hidden="true"></i>hello@smarttech.example</a><small>Concept store • illustrative contact only</small></div></div>
<div class="sf-cols"><nav aria-label="Explore"><h3>Explore</h3><ul><li><a href="products.html?cat=household">Household</a></li><li><a href="products.html?cat=entertainment">Entertainment</a></li><li><a href="products.html?cat=kitchen">Kitchen</a></li><li><a href="about.html">About Us</a></li></ul></nav>
<nav aria-label="Customer care"><h3>Customer care</h3><ul><li><a href="help.html#delivery">Delivery information</a></li><li><a href="help.html#returns">Returns &amp; warranty</a></li><li><a href="help.html#payment">Payment options</a></li><li><a href="help.html#faq">FAQs</a></li></ul></nav>
<nav aria-label="Your SmartTech"><h3>Your SmartTech</h3><ul><li><a href="login.html">My account</a></li><li><a href="track.html">Track my order</a></li><li><a href="wishlist.html">My wishlist</a></li><li><a href="about.html#contact">Contact us</a></li></ul></nav></div></div>
<div class="sf-bottom"><div class="sf-legal"><small>© 2026 SmartTech Appliances. Concept design.</small><small><a href="#">Privacy policy</a> &nbsp;•&nbsp; <a href="#">Terms &amp; conditions</a></small></div>
<div class="sf-pay"><small>PAY YOUR WAY</small><ul aria-label="Accepted payments"><li>GCash</li><li>Maya</li><li>COD</li><li>VISA</li></ul></div></div></div></footer>
<nav class="st-bottomnav d-lg-none" aria-label="Quick navigation">${[['homelandingpage.html','house','Home','home'],['products.html','shop','Shop','shop'],['wishlist.html','heart','Wishlist','wishlist'],['cart.html','bag','Cart','cart'],['login.html','person','Account','login']].map(([h,i,l,k]) => `<a href="${h}"${k===page?' class="active" aria-current="page"':''}><i class="bi bi-${i}" aria-hidden="true"></i><span>${l}</span></a>`).join('')}</nav>`;
  document.body.insertAdjacentHTML('afterbegin', head);
  document.body.insertAdjacentHTML('beforeend', foot);
  const main = document.getElementById('main'); if (main) document.body.insertBefore(main, document.querySelector('.st-footer'));
  window.stCartCount = function () {
    let c = []; try { c = JSON.parse(localStorage.getItem('st_cart')) || []; } catch (e) {}
    const el = document.getElementById('cart-count'); if (el) el.textContent = c.reduce((t, i) => t + (i.qty || 1), 0);
  };
  stCartCount();
})();