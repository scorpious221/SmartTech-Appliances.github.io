<?php
session_start();
require_once "php/database.php";

if(!isset($_SESSION["user_id"])||($_SESSION["role"]??"")!=="admin"){
header("Location: login.php");
exit;
}

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
o.grand_total,
o.status,
o.created_at,
COALESCE(SUM(oi.quantity),0) AS total_items
FROM orders o
LEFT JOIN order_items oi ON oi.order_id=o.id
GROUP BY
o.id,o.order_number,o.user_id,o.full_name,o.phone,o.address,
o.payment_method,o.subtotal,o.shipping_fee,o.grand_total,
o.status,o.created_at
ORDER BY o.id DESC
");

$dbOrders=[];
$orderIndex=[];

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
"grandTotal"=>(float)$row["grand_total"],
"status"=>$row["status"],
"createdAt"=>$row["created_at"],
"totalItems"=>(int)$row["total_items"],
"items"=>[]
];

$orderIndex[(int)$row["id"]]=count($dbOrders)-1;
}

/* ORDER ITEMS */
$stmt=$conn->query("
SELECT
oi.order_id,
oi.product_name,
oi.price,
oi.quantity,
p.image
FROM order_items oi
LEFT JOIN products p ON p.id=oi.product_id
ORDER BY oi.order_id DESC,oi.id ASC
");

while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
$orderId=(int)$row["order_id"];

if(!isset($orderIndex[$orderId]))continue;

$dbOrders[$orderIndex[$orderId]]["items"][]=[
"name"=>$row["product_name"],
"price"=>(float)$row["price"],
"quantity"=>(int)$row["quantity"],
"image"=>$row["image"]??""
];
}
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
<link href="pages.css" rel="stylesheet">
<link href="admin.css" rel="stylesheet">
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
<button data-page="orders" type="button"><i class="bi bi-receipt"></i><span>Orders</span></button>
<button data-page="customers" type="button"><i class="bi bi-people"></i><span>Customers</span></button>
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
<span class="ad-muted">Delivered sales</span>
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

<button class="ad-btn outline sm" data-jump="products" type="button">View products</button>

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

<button class="ad-btn outline sm" data-jump="orders" type="button">View all</button>

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

<input class="form-control ad-search" id="productSearch" type="search" placeholder="Search product, category or subcategory">

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

<section class="ad-sec" id="ordersSec" hidden>

<section class="ad-panel">

<div class="table-responsive ad-table-wrap ad-mobile-table-wrap">

<table class="table table-hover align-middle mb-0 ad-table ad-mobile-table ad-orders-table">

<thead>
<tr>
<th>Order</th>
<th>Items</th>
<th>Quantity</th>
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
<input type="hidden" name="existing_image" id="pExistingImage">

<div class="ad-form-grid">

<label class="field">
Product name
<input class="form-control" id="pName" name="name" required>
</label>

<label class="field">
Price
<input class="form-control" id="pPrice" name="price" type="number" min="0.01" step="0.01" required>
</label>

<label class="field">
Stock quantity
<input class="form-control" id="pStock" name="stock" type="number" min="0" max="100" step="1" required>
</label>

<label class="field">
Product image
<input class="form-control" id="pImage" name="image" type="file" accept=".jpg,.jpeg,.png,.webp,.gif,image/jpeg,image/png,image/webp,image/gif">
</label>

<div class="field full" id="pImagePreviewWrap" style="display:none">
<span>Current image</span>

<div style="display:flex;align-items:center;gap:12px;margin-top:8px">
<img id="pImagePreview" src="" alt="Product image" style="width:70px;height:70px;object-fit:contain;border:1px solid var(--st-sand);border-radius:10px;background:#f5f5f5;padding:5px">
<small class="ad-muted" id="pImageName"></small>
</div>
</div>

<label class="field">
Category
<select class="form-select" id="pCategory" name="category">
<option value="household">Household</option>
<option value="entertainment">Entertainment</option>
<option value="kitchen">Kitchen</option>
</select>
</label>

<label class="field">
Subcategory
<select class="form-select" id="pSub" name="subcategory" required></select>
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

<script>
window.DB_PRODUCTS=<?php echo json_encode($dbProducts,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
window.DB_CUSTOMERS=<?php echo json_encode($dbCustomers,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
window.DB_ORDERS=<?php echo json_encode($dbOrders,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
</script>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="admin.js"></script>

</body>
</html>