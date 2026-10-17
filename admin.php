<?php
session_start();
require_once "php/database.php";

if(!isset($_SESSION["user_id"])||($_SESSION["role"]??"")!=="admin"){
    header("Location: login.php");
    exit;
}
if(empty($_SESSION["csrf_token"]))$_SESSION["csrf_token"]=bin2hex(random_bytes(32));
$adminCsrfToken=$_SESSION["csrf_token"];

/* PRODUCTS */
$stmt=$conn->query("SELECT * FROM products ORDER BY id ASC");
$dbProducts=[];

while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
    $dbProducts[]=[
        "dbId"=>(int)$row["id"],
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
$mediaStmt=$conn->query("SELECT id,product_id,media_type,file_path FROM product_media ORDER BY sort_order,id");
$productMedia=[];
foreach($mediaStmt->fetchAll(PDO::FETCH_ASSOC) as $media){
    $productMedia[(int)$media["product_id"]][]=[
        "id"=>(int)$media["id"],
        "type"=>$media["media_type"],
        "path"=>$media["file_path"]
    ];
}
foreach($dbProducts as &$product){
    $product["media"]=$productMedia[$product["dbId"]]??[];
}
unset($product);
$dbPromos=$conn->query("SELECT * FROM home_promos ORDER BY sort_order,id")->fetchAll(PDO::FETCH_ASSOC);
$dbBundles=$conn->query("SELECT * FROM home_bundles ORDER BY sort_order,id")->fetchAll(PDO::FETCH_ASSOC);
foreach($dbBundles as &$bundle){
    $bundle["product_codes"]=json_decode((string)$bundle["product_codes"],true)?:[];
}
unset($bundle);
$dbHomeCategories=$conn->query("SELECT * FROM home_categories ORDER BY sort_order,id")->fetchAll(PDO::FETCH_ASSOC);
$dbDeal=$conn->query("SELECT * FROM home_deal WHERE id=1")->fetch(PDO::FETCH_ASSOC)?:null;
$dbDemoVideo=$conn->query("SELECT id,video_path,updated_at FROM home_demo_video WHERE id=1")->fetch(PDO::FETCH_ASSOC)?:null;
$dbVouchers=$conn->query("SELECT * FROM vouchers ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
foreach($dbVouchers as &$voucher){
    $voucher["eligible_products"]=json_decode((string)($voucher["eligible_products"]??"[]"),true)?:[];
    $voucher["eligible_categories"]=json_decode((string)($voucher["eligible_categories"]??"[]"),true)?:[];
    $voucher["required_product_sets"]=json_decode((string)($voucher["required_product_sets"]??"[]"),true)?:[];
}
unset($voucher);
$dbProductCategories=$conn->query("SELECT DISTINCT category FROM products ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

/* CUSTOMERS */
$stmt=$conn->query("
    SELECT id,username,email,full_name,contact,address
    FROM users
    WHERE role='customer'
    ORDER BY id DESC
");

$dbCustomers=[];

while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
    $dbCustomers[]=[
        "id"=>(int)$row["id"],
        "username"=>$row["username"],
        "email"=>$row["email"],
        "name"=>$row["full_name"],
        "contact"=>$row["contact"],
        "address"=>$row["address"]
    ];
}

/* ORDERS */
$stmt=$conn->query("
    SELECT
        o.id,
        o.order_number,
        o.user_id,
        o.full_name,
        o.phone,
        o.address,
        o.payment_method,
        o.subtotal,
        o.shipping_fee,
        o.voucher_code,
        o.discount_amount,
        o.grand_total,
        o.status,
        o.created_at,
        COALESCE(SUM(oi.quantity),0) AS total_items
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id=o.id
    GROUP BY
        o.id,o.order_number,o.user_id,o.full_name,o.phone,o.address,
        o.payment_method,o.subtotal,o.shipping_fee,o.voucher_code,o.discount_amount,o.grand_total,
        o.status,o.created_at
    ORDER BY o.id DESC
");

$dbOrders=[];

while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
    $dbOrders[]=[
        "id"=>(int)$row["id"],
        "orderNumber"=>$row["order_number"],
        "userId"=>(int)$row["user_id"],
        "name"=>$row["full_name"],
        "phone"=>$row["phone"],
        "address"=>$row["address"],
        "paymentMethod"=>$row["payment_method"],
        "subtotal"=>(float)$row["subtotal"],
        "shippingFee"=>(float)$row["shipping_fee"],
        "voucherCode"=>$row["voucher_code"],
        "discountAmount"=>(float)$row["discount_amount"],
        "grandTotal"=>(float)$row["grand_total"],
        "status"=>$row["status"],
        "createdAt"=>$row["created_at"],
        "totalItems"=>(int)$row["total_items"]
    ];
}

$stmt=$conn->query("
    SELECT rr.id,rr.order_id,rr.order_item_id,rr.reason,rr.details,rr.status,rr.admin_note,rr.requested_at,
           o.order_number,o.full_name,oi.product_name,oi.quantity,oi.price,u.email
    FROM refund_requests rr
    JOIN orders o ON o.id=rr.order_id
    JOIN order_items oi ON oi.id=rr.order_item_id
    JOIN users u ON u.id=rr.user_id
    ORDER BY (rr.status='Pending') DESC,rr.requested_at DESC
");
$dbRefundRequests=$stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt=$conn->query("
    SELECT r.id,r.user_id,r.status,r.admin_note,r.requested_at,u.username,u.full_name,u.email
    FROM account_deletion_requests r
    JOIN users u ON u.id=r.user_id
    ORDER BY (r.status='Pending') DESC,r.requested_at DESC
");
$dbAccountDeletionRequests=$stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt=$conn->query("
    SELECT f.id,f.rating,f.comment,f.updated_at,u.username,u.full_name,u.email
    FROM app_feedback f
    JOIN users u ON u.id=f.user_id
    ORDER BY f.updated_at DESC
");
$dbAppFeedback=$stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>Admin Dashboard — SmartTech Appliances</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<link href="styles.css" rel="stylesheet">
<link href="shop.css" rel="stylesheet">
<link href="pages.css?v=mobile-gutters-2" rel="stylesheet">
<link href="admin.css?v=homepage-demo-video-1" rel="stylesheet">
</head>

<body class="admin-page">

<div class="ad-shell">

<aside class="ad-side">

<a class="ad-brand" href="admin.php">
<img src="images/smarttechappliances.svg" alt="">
<span>SmartTech Admin</span>
</a>

<nav class="ad-nav" aria-label="Admin navigation">
<button class="active" data-page="dashboard" type="button"><i class="bi bi-grid-1x2"></i><span>Dashboard</span></button>
<button data-page="products" type="button"><i class="bi bi-box-seam"></i><span>Products</span></button>
<button data-page="storefront" type="button"><i class="bi bi-window-stack"></i><span>Storefront</span></button>
<button data-page="vouchers" type="button"><i class="bi bi-ticket-perforated"></i><span>Vouchers</span></button>
<button data-page="orders" type="button"><i class="bi bi-receipt"></i><span>Orders</span></button>
<button data-page="refunds" type="button"><i class="bi bi-arrow-counterclockwise"></i><span>Returns &amp; refunds</span></button>
<button data-page="requests" type="button"><i class="bi bi-person-gear"></i><span>Account requests</span></button>
<button data-page="customers" type="button"><i class="bi bi-people"></i><span>Customers</span></button>
<a href="index.html" target="_blank"><i class="bi bi-shop"></i><span>View Store</span></a>
</nav>

<a class="ad-nav ad-logout" href="php/logout.php" style="padding:0;text-decoration:none">
<span class="d-flex w-100 align-items-center gap-3 px-3">
<i class="bi bi-box-arrow-left"></i>
<span>Logout</span>
</span>
</a>

</aside>

<main class="ad-main">

<header class="ad-top">

<div>
<h1 id="pageTitle">Dashboard</h1>
<p id="pageSub">Quick summary of your SmartTech store.</p>
</div>

<div class="ad-admin">
<span class="ad-avatar"><i class="bi bi-person"></i></span>
<span><?php echo htmlspecialchars($_SESSION["full_name"]??"Administrator"); ?></span>
</div>

</header>

<section class="ad-sec" id="dashboardSec">

<div class="ad-cards">

<article class="ad-card">
<small>Total Revenue</small>
<strong id="mRevenue">₱0</strong>
<span>Delivered orders</span>
</article>

<article class="ad-card">
<small>Total Orders</small>
<strong id="mOrders">0</strong>
<span>All orders</span>
</article>

<article class="ad-card">
<small>Total Products</small>
<strong id="mProducts"><?php echo count($dbProducts); ?></strong>
<span>Listed products</span>
</article>

<article class="ad-card">
<small>Total Customers</small>
<strong id="mCustomers"><?php echo count($dbCustomers); ?></strong>
<span>Registered accounts</span>
</article>

<article class="ad-card">
<small>Pending Orders</small>
<strong id="mPending">0</strong>
<span>Need processing</span>
</article>

<article class="ad-card">
<small>Low Stock</small>
<strong id="mLow">0</strong>
<span>9 units or less</span>
</article>

</div>

<div class="ad-grid">

<section class="ad-panel">

<div class="ad-panel-head">
<div>
<h2>Sales Analytics</h2>
<span class="ad-muted">Delivered sales from the last 7 days</span>
</div>
</div>

<div class="ad-chart" id="salesChart"></div>

</section>

<section class="ad-panel">

<div class="ad-panel-head">

<div>
<h2>Low Stock Alerts</h2>
<span class="ad-muted">Products that need restocking</span>
</div>

<button class="ad-btn outline sm" data-jump="products" type="button">
View products
</button>

</div>

<div class="ad-list" id="lowStockList"></div>

</section>

</div>

<section class="ad-panel">

<div class="ad-panel-head">

<div>
<h2>Recent Orders</h2>
<span class="ad-muted">Latest customer transactions</span>
</div>

<button class="ad-btn outline sm" data-jump="orders" type="button">
View all
</button>

</div>

<div class="table-responsive ad-table-wrap">

<table class="table table-hover align-middle mb-0 ad-table">

<thead>
<tr>
<th>Order</th>
<th>Customer</th>
<th>Date</th>
<th>Total</th>
<th>Status</th>
</tr>
</thead>

<tbody id="recentOrders"></tbody>

</table>

</div>

</section>

</section>

<section class="ad-sec" id="productsSec" hidden>

<div class="ad-toolbar">

<div class="flex-grow-1">

<input
class="form-control ad-search"
id="productSearch"
type="search"
placeholder="Search product, category or subcategory"
>

<div id="productCount" class="ad-muted mt-2"></div>

</div>

<button class="ad-btn" id="addProduct" type="button">
<i class="bi bi-plus-lg"></i>
Add Product
</button>

</div>

<section class="ad-panel">

<div class="table-responsive ad-table-wrap">

<table class="table table-hover align-middle mb-0 ad-table">

<thead>
<tr>
<th>Product</th>
<th>Category</th>
<th>Price</th>
<th>Stock</th>
<th>Status</th>
<th>Actions</th>
</tr>
</thead>

<tbody id="productRows"></tbody>

</table>

</div>

<nav class="mt-4">
<ul class="pagination justify-content-center flex-wrap mb-0" id="productPager"></ul>
</nav>

</section>

</section>

<section class="ad-sec" id="storefrontSec" hidden>
<section class="ad-panel mb-4">
<div class="ad-panel-head"><div><h2>Homepage demo video</h2><span class="ad-muted">Replace the video opened by the Watch Demo button.</span></div><button class="ad-btn sm" type="button" data-content-edit="demo_video" data-content-id="1"><i class="bi bi-camera-video"></i>Replace video</button></div>
<div id="demoVideoRow"></div>
</section>
<section class="ad-panel mb-4">
<div class="ad-panel-head"><div><h2>Deal of the month</h2><span class="ad-muted">Feature a product with a custom deal description, comparison price, and optional end time.</span></div><button class="ad-btn sm" type="button" data-content-edit="deal" data-content-id="1"><i class="bi bi-pencil"></i>Edit deal</button></div>
<div id="dealRow"></div>
</section>
<section class="ad-panel mb-4">
<div class="ad-panel-head"><div><h2>Homepage promotions</h2><span class="ad-muted">Manage the promo cards shown on the storefront.</span></div><button class="ad-btn sm" type="button" data-content-new="promo"><i class="bi bi-plus-lg"></i>Add promo</button></div>
<div class="ad-list" id="promoRows"></div>
</section>
<section class="ad-panel mb-4">
<div class="ad-panel-head"><div><h2>Featured bundles</h2><span class="ad-muted">Choose products, bundle pricing, image, and destination link.</span></div><button class="ad-btn sm" type="button" data-content-new="bundle"><i class="bi bi-plus-lg"></i>Add bundle</button></div>
<div class="ad-list" id="bundleRows"></div>
</section>
<section class="ad-panel">
<div class="ad-panel-head"><div><h2>Featured categories</h2><span class="ad-muted">Manage homepage category tiles and their product links.</span></div><button class="ad-btn sm" type="button" data-content-new="category"><i class="bi bi-plus-lg"></i>Add category tile</button></div>
<div class="ad-list" id="homeCategoryRows"></div>
</section>
</section>

<section class="ad-sec" id="vouchersSec" hidden>
<section class="ad-panel">
<div class="ad-panel-head"><div><h2>Checkout vouchers</h2><span class="ad-muted">Create discount codes, eligibility rules, dates, and redemption limits.</span></div><button class="ad-btn sm" type="button" data-content-new="voucher"><i class="bi bi-plus-lg"></i>Add voucher</button></div>
<div class="table-responsive ad-table-wrap">
<table class="table table-hover align-middle mb-0 ad-table">
<thead><tr><th>Code</th><th>Offer</th><th>Eligibility</th><th>Validity / uses</th><th>Status</th><th>Actions</th></tr></thead>
<tbody id="voucherRows"></tbody>
</table>
</div>
</section>
</section>

<section class="ad-sec" id="ordersSec" hidden>

<section class="ad-panel">

<div class="table-responsive ad-table-wrap">

<table class="table table-hover align-middle mb-0 ad-table">

<thead>
<tr>
<th>Order</th>
<th>Customer</th>
<th>Payment</th>
<th>Total</th>
<th>Date</th>
<th>Status</th>
</tr>
</thead>

<tbody id="orderRows"></tbody>

</table>

</div>

</section>

</section>

<section class="ad-sec" id="refundsSec" hidden>
<section class="ad-panel">
<div class="table-responsive ad-table-wrap">
<table class="table table-hover align-middle mb-0 ad-table">
<thead>
<tr><th>Order / Item</th><th>Customer</th><th>Reason / Details</th><th>Requested</th><th>Status</th><th>Review</th></tr>
</thead>
<tbody id="refundRows"></tbody>
</table>
</div>
<p class="small text-secondary p-3 mb-0">Approving a request records the decision only. Arrange any return shipping or payment refund separately; no payment provider is connected.</p>
</section>
</section>

<section class="ad-sec" id="requestsSec" hidden>
<section class="ad-panel mb-4">
<div class="ad-panel-head"><div><h2>Account deletion requests</h2><span class="ad-muted">Requests are reviewed here. This panel does not delete accounts automatically.</span></div></div>
<div class="table-responsive ad-table-wrap">
<table class="table table-hover align-middle mb-0 ad-table">
<thead><tr><th>Customer</th><th>Requested</th><th>Status</th><th>Review</th></tr></thead>
<tbody id="deletionRequestRows"></tbody>
</table>
</div>
</section>
<section class="ad-panel">
<div class="ad-panel-head"><div><h2>Web app ratings</h2><span class="ad-muted">Feedback submitted by customers.</span></div></div>
<div class="table-responsive ad-table-wrap">
<table class="table table-hover align-middle mb-0 ad-table">
<thead><tr><th>Customer</th><th>Rating</th><th>Feedback</th><th>Updated</th></tr></thead>
<tbody id="appFeedbackRows"></tbody>
</table>
</div>
</section>
</section>

<section class="ad-sec" id="customersSec" hidden>

<section class="ad-panel">

<div class="table-responsive ad-table-wrap">

<table class="table table-hover align-middle mb-0 ad-table">

<thead>
<tr>
<th>Customer</th>
<th>Username</th>
<th>Email</th>
<th>Contact</th>
<th>Address</th>
</tr>
</thead>

<tbody id="customerRows"></tbody>

</table>

</div>

</section>

</section>

</main>

</div>

<dialog class="ad-dialog" id="productDialog">

<div class="ad-dialog-head">
<h2 id="formTitle">Add Product</h2>
<button class="ad-x" id="closeDialog" type="button">×</button>
</div>

<form class="ad-form" id="productForm" method="POST" enctype="multipart/form-data" novalidate>

<input type="hidden" name="id" id="pId">
<input type="hidden" name="product_code" id="pCode">
<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($adminCsrfToken,ENT_QUOTES,"UTF-8"); ?>">

<div class="ad-form-grid">

<label class="field">
Product name
<input class="form-control" id="pName" name="name" required>
</label>

<label class="field">
Price
<input class="form-control" id="pPrice" name="price" type="number" min="0" step="0.01" required>
</label>

<label class="field">
Stock quantity
<input class="form-control" id="pStock" name="stock" type="number" min="0" step="1" required>
</label>

<label class="field full">
Main product image
<input class="form-control" id="pImage" name="primary_image" type="file" accept="image/jpeg,image/png,image/webp,image/gif">
<small class="ad-note">Upload JPG, PNG, WebP, or GIF. Images may be up to 8 MB. When editing, leave blank to keep the current main image.</small>
</label>

<div class="field full" id="existingProductMedia"></div>

<label class="field full">
Add more images or videos
<input class="form-control" name="product_gallery[]" type="file" accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/ogg" multiple>
<small class="ad-note">Images up to 8 MB; MP4, WebM, or OGG videos up to 30 MB each. Choose up to 19 additional files; total uploads per save must stay under 35 MB.</small>
</label>

<label class="field">
Category
<input class="form-control" id="pCategory" name="category" list="productCategoryOptions" maxlength="100" required>
<datalist id="productCategoryOptions"><?php foreach($dbProductCategories as $category): ?><option value="<?php echo htmlspecialchars($category,ENT_QUOTES,"UTF-8"); ?>"><?php endforeach; ?></datalist>

</label>

<label class="field">
Subcategory
<input class="form-control" id="pSub" name="subcategory" required>
</label>

<label class="field full">
Description
<textarea class="form-control" id="pDesc" name="description" rows="5"></textarea>
</label>

</div>

<p class="msg" id="formMsg"></p>

<div class="ad-form-foot">
<button class="ad-btn outline" id="cancelDialog" type="button">Cancel</button>
<button class="ad-btn" type="submit">Save Product</button>
</div>

</form>

</dialog>

<dialog class="ad-dialog" id="contentDialog">
<div class="ad-dialog-head">
<h2 id="contentFormTitle">Edit storefront content</h2>
<button class="ad-x" type="button" data-content-close aria-label="Close">×</button>
</div>
<form class="ad-form" id="contentForm" enctype="multipart/form-data">
<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($adminCsrfToken,ENT_QUOTES,"UTF-8"); ?>">
<input type="hidden" name="id" id="contentId">
<input type="hidden" name="action" id="contentAction">
<div class="ad-form-grid" id="contentFields"></div>
<p class="msg" id="contentMessage" role="alert"></p>
<div class="ad-form-foot">
<button class="ad-btn outline" type="button" data-content-close>Cancel</button>
<button class="ad-btn" id="saveContent" type="submit">Save changes</button>
</div>
</form>
</dialog>

<script>
window.DB_PRODUCTS=<?php echo json_encode($dbProducts,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
window.DB_PROMOS=<?php echo json_encode($dbPromos,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
window.DB_BUNDLES=<?php echo json_encode($dbBundles,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
window.DB_HOME_CATEGORIES=<?php echo json_encode($dbHomeCategories,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
window.DB_DEAL=<?php echo json_encode($dbDeal,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
window.DB_DEMO_VIDEO=<?php echo json_encode($dbDemoVideo,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
window.DB_VOUCHERS=<?php echo json_encode($dbVouchers,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
window.DB_PRODUCT_CATEGORIES=<?php echo json_encode($dbProductCategories,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
window.DB_CUSTOMERS=<?php echo json_encode($dbCustomers,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
window.DB_ORDERS=<?php echo json_encode($dbOrders,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
window.DB_REFUND_REQUESTS=<?php echo json_encode($dbRefundRequests,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
window.DB_ACCOUNT_DELETION_REQUESTS=<?php echo json_encode($dbAccountDeletionRequests,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
window.DB_APP_FEEDBACK=<?php echo json_encode($dbAppFeedback,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
window.ADMIN_CSRF_TOKEN=<?php echo json_encode($adminCsrfToken); ?>;
</script>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="admin.js?v=homepage-demo-video-1"></script>

</body>
</html>