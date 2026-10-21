<?php
require_once "php/database.php";

$stmt=$conn->query("SELECT * FROM products ORDER BY id ASC");
$products=[];

while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
    $products[]=[
        "id"=>$row["product_code"],
        "name"=>$row["name"],
        "price"=>(float)$row["price"],
        "img"=>$row["image"],
        "category"=>$row["category"],
        "sub"=>$row["subcategory"],
        "desc"=>$row["description"],
        "stock"=>(int)$row["stock"]
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Shop — SmartTech Appliances</title>
<meta name="description" content="Browse household, entertainment and kitchen appliances.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="styles.css" rel="stylesheet">
<link href="shop.css" rel="stylesheet">
</head>

<body>
<a class="visually-hidden-focusable st-skip" href="#main">Skip to content</a>

<div class="st-topbar text-center">Free delivery over ₱3,000 • 1-Year Warranty • GCash, Maya, COD</div>

<header class="st-header sticky-top">
<nav class="navbar navbar-expand-lg navbar-dark container-xxl px-lg-5" aria-label="Primary">

<a class="navbar-brand st-logo order-1 me-auto" href="index.html">
<span class="st-mark d-lg-none"><i class="bi bi-house"></i></span>
<img class="d-none d-lg-block" src="images/smarttechappliances.svg" alt="" width="60" height="49">
<span>SmartTech</span>
</a>

<div class="d-flex gap-2 order-2 order-lg-3 ms-lg-4">
<button class="st-icon-btn d-lg-none" type="button" aria-label="Search" onclick="document.querySelector('.stq-btn')?.click()"><i class="bi bi-search"></i></button>
<a class="st-icon-btn d-none d-lg-inline-grid" href="wishlist.html"><i class="bi bi-heart"></i></a>
<a class="st-icon-btn st-cart-btn" href="cart.php"><i class="bi bi-bag"></i><span class="st-count" id="cart-count">0</span></a>
<a class="st-icon-btn d-none d-lg-inline-grid" href="login.php"><i class="bi bi-person"></i></a>
</div>

<div class="collapse navbar-collapse order-3 order-lg-2" id="nav">
<ul class="navbar-nav me-lg-4 gap-lg-3 pt-2 pt-lg-0" id="navList">
<li class="nav-item"><a class="nav-link" href="index.html">Home</a></li>
<li class="nav-item"><a class="nav-link" href="products.php?cat=household" data-cat="household">Household</a></li>
<li class="nav-item"><a class="nav-link" href="products.php?cat=entertainment" data-cat="entertainment">Entertainment</a></li>
<li class="nav-item"><a class="nav-link" href="products.php?cat=kitchen" data-cat="kitchen">Kitchen</a></li>
</ul>

<form class="st-search ms-lg-auto flex-grow-1 my-3 my-lg-0" role="search" onsubmit="return false">
<i class="bi bi-search"></i>
<input type="search" class="form-control" placeholder="Search appliances" readonly>
</form>
</div>

</nav>
</header>

<span class="st-icons" hidden></span>

<main id="main">

<section class="container-xxl px-lg-5 shop-head">

<nav class="crumbs">
<a href="index.html">Home</a>
<i class="bi bi-chevron-right"></i>
<b>Shop</b>
</nav>

<div class="d-flex flex-wrap justify-content-between align-items-end gap-4">
<div>
<span class="shop-pill">MADE FOR EVERYDAY LIVING</span>
<h1>Shop smarter. Live better.</h1>
<p class="sub">Thoughtful essentials for a more comfortable, connected home.</p>
</div>

<div class="shop-assure">
<i class="bi bi-patch-check"></i>
<div>
<b>Peace of mind, included.</b>
<small>1-year warranty on every item</small>
</div>
</div>
</div>

<div class="cat-tabs" role="tablist">
<div class="cat-box active" data-cat="household" role="tab" tabindex="0">Household</div>
<div class="cat-box" data-cat="entertainment" role="tab" tabindex="0">Entertainment</div>
<div class="cat-box" data-cat="kitchen" role="tab" tabindex="0">Kitchen</div>
</div>

<div id="subcat-area"></div>

<div class="toolbar">
<div>
<strong id="result-count">0 products</strong>
<small id="result-path"></small>
</div>

<div class="st-sort">
<button class="st-sort-btn" id="sortBtn" type="button" aria-haspopup="listbox" aria-expanded="false">
<span><span class="d-none d-lg-inline">Sort by: </span><span id="sortLabel">Featured</span></span>
<i class="bi bi-chevron-down"></i>
</button>

<ul id="sortList" role="listbox" hidden>
<li role="option" tabindex="0" data-v="featured" aria-selected="true">Featured<i class="bi bi-check2"></i></li>
<li role="option" tabindex="0" data-v="asc">Price: low to high<i class="bi bi-check2"></i></li>
<li role="option" tabindex="0" data-v="desc">Price: high to low<i class="bi bi-check2"></i></li>
</ul>
</div>
</div>

<div id="product-grid" aria-live="polite"></div>

<p class="end-note" id="end-note">
<b></b>
Everyday essentials. All backed by a 1-year warranty.
</p>

</section>

<section class="assure">
<div class="container-xxl px-lg-5">
<h2>Good choices. Zero worries.</h2>

<ul>
<li><span><i class="bi bi-truck"></i></span><div><b>Delivered to your doorstep</b>Free delivery on orders over ₱3,000.</div></li>
<li><span><i class="bi bi-shield-check"></i></span><div><b>A full year of confidence</b>1-year warranty on every product.</div></li>
<li><span><i class="bi bi-wallet2"></i></span><div><b>Pay your way</b>GCash, Maya or cash on delivery.</div></li>
</ul>
</div>
</section>

</main>

<footer class="st-footer">
<div class="container-xxl px-lg-5">

<div class="sf-top">

<div class="sf-info">
<a class="st-logo mb-3" href="index.html">
<img src="images/smarttechappliances.svg" alt="" width="60" height="49">
<span>SmartTech</span>
</a>

<p class="sf-blurb">Smarter essentials for Filipino homes. Bringing comfort, convenience and a little more ease to every day.</p>

<div class="sf-contact">
<b>LET’S TALK</b>
<a href="mailto:hello@smarttech.example"><i class="bi bi-envelope"></i>hello@smarttech.example</a>
<small>Concept store • illustrative contact only</small>
</div>
</div>

<div class="sf-cols">

<nav>
<h3>Explore</h3>
<ul>
<li><a href="products.php?cat=household">Household</a></li>
<li><a href="products.php?cat=entertainment">Entertainment</a></li>
<li><a href="products.php?cat=kitchen">Kitchen</a></li>
<li><a href="about.html">About Us</a></li>
</ul>
</nav>

<nav>
<h3>Customer care</h3>
<ul>
<li><a href="help.html#delivery">Delivery information</a></li>
<li><a href="help.html#returns">Returns &amp; warranty</a></li>
<li><a href="help.html#payment">Payment options</a></li>
<li><a href="help.html#faq">FAQs</a></li>
</ul>
</nav>

<nav>
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
</div>

<div class="sf-pay">
<small>PAY YOUR WAY</small>
<ul>
<li>GCash</li>
<li>Maya</li>
<li>COD</li>
<li>VISA</li>
</ul>
</div>
</div>

</div>
</footer>

<nav class="st-bottomnav d-lg-none">
<a href="index.html"><i class="bi bi-house"></i><span>Home</span></a>
<a href="products.php" class="active"><i class="bi bi-shop"></i><span>Shop</span></a>
<a href="wishlist.html"><i class="bi bi-heart"></i><span>Wishlist</span></a>
<a href="cart.php"><i class="bi bi-bag"></i><span>Cart</span></a>
<a href="login.php"><i class="bi bi-person"></i><span>Account</span></a>
</nav>

<div id="descOverlay" role="dialog" aria-modal="true" aria-label="Product details">
<div class="desc-box">
<button class="desc-close" type="button" onclick="closeOverlay()">&times;</button>
<div id="descContent"></div>
</div>
</div>

<div id="toast" role="status" aria-live="polite"></div>

<script>
const PRODUCTS=<?php echo json_encode($products,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
</script>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="products.js"></script>
<script src="shop.js"></script>
<script src="search.js"></script>

<script>
(function(){
function sync(){
document.querySelectorAll('#navList a[data-cat]').forEach(function(a){
const on=a.dataset.cat===currentCategory;
a.classList.toggle('active',on);
on?a.setAttribute('aria-current','page'):a.removeAttribute('aria-current');
});
}
const base=window.renderTopCats;
window.renderTopCats=function(){base();sync();};
sync();
})();
</script>

</body>
</html>