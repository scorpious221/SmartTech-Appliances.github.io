<?php
session_start();
require_once "php/database.php";

function e($value){
    return htmlspecialchars((string)$value,ENT_QUOTES,"UTF-8");
}

function renderStars($rating,$hasRatings=true){
    if(!$hasRatings)return "★★★★★";
    $rating=(int)round((float)$rating);
    return str_repeat("★",$rating).str_repeat("☆",5-$rating);
}

function safeDescription($description){
    $document=new DOMDocument("1.0","UTF-8");
    $previous=libxml_use_internal_errors(true);
    $document->loadHTML('<?xml encoding="UTF-8"><div id="description-root">'.$description.'</div>',LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    $root=$document->getElementById("description-root");
    if(!$root)return e($description);

    $allowed=["p","br","ul","ol","li","strong","b","em","i"];
    $skip=["script","style","iframe","object","svg","math"];
    $render=function($node)use(&$render,$allowed,$skip){
        if($node->nodeType===XML_TEXT_NODE)return e($node->nodeValue);
        if($node->nodeType!==XML_ELEMENT_NODE)return "";
        $tag=strtolower($node->nodeName);
        if(in_array($tag,$skip,true))return "";
        $content="";
        foreach($node->childNodes as $child)$content.=$render($child);
        if(!in_array($tag,$allowed,true))return $content;
        return $tag==="br"?"<br>":"<".$tag.">".$content."</".$tag.">";
    };

    $safe="";
    foreach($root->childNodes as $child)$safe.=$render($child);
    return $safe;
}

if(empty($_SESSION["csrf_token"]))$_SESSION["csrf_token"]=bin2hex(random_bytes(32));
$csrfToken=$_SESSION["csrf_token"];
$productCode=trim($_GET["id"]??"");

if($_SERVER["REQUEST_METHOD"]==="POST"){
    if(!isset($_SESSION["user_id"])||($_SESSION["role"]??"")!=="customer"){
        header("Location: login.php?next=".urlencode("product.php?id=".$productCode."#reviews"));
        exit;
    }
    if(!isset($_POST["csrf_token"])||!hash_equals($csrfToken,(string)$_POST["csrf_token"])){
        http_response_code(403);
        exit("Your session has expired. Refresh the page and try again.");
    }

    $itemId=(int)($_POST["order_item_id"]??0);
    $rating=(int)($_POST["rating"]??0);
    $comment=trim($_POST["comment"]??"");
    $lookup=$conn->prepare("
        SELECT p.id
        FROM products p
        WHERE p.product_code=?
        LIMIT 1
    ");
    $lookup->execute([$productCode]);
    $productId=(int)$lookup->fetchColumn();

    if(!$productId||$itemId<=0||$rating<1||$rating>5||strlen($comment)<5||strlen($comment)>1000){
        $_SESSION["product_review_message"]="Please choose a completed purchase, select 1–5 stars, and write at least 5 characters.";
        header("Location: product.php?id=".urlencode($productCode)."#reviews");
        exit;
    }

    $eligible=$conn->prepare("
        SELECT oi.id
        FROM order_items oi
        JOIN orders o ON o.id=oi.order_id
        WHERE oi.id=? AND oi.product_id=? AND o.user_id=? AND o.status='Completed'
          AND NOT EXISTS (SELECT 1 FROM product_reviews pr WHERE pr.order_item_id=oi.id)
        LIMIT 1
    ");
    $eligible->execute([$itemId,$productId,(int)$_SESSION["user_id"]]);

    if(!$eligible->fetchColumn()){
        $_SESSION["product_review_message"]="Reviews are available once your delivered order is marked complete. Each purchased item can be reviewed once.";
        header("Location: product.php?id=".urlencode($productCode)."#reviews");
        exit;
    }

    $insert=$conn->prepare("
        INSERT INTO product_reviews(product_id,user_id,order_item_id,rating,comment)
        VALUES(?,?,?,?,?)
    ");
    $insert->execute([$productId,(int)$_SESSION["user_id"],$itemId,$rating,$comment]);
    $_SESSION["product_review_message"]="Thank you! Your verified-purchase review has been posted.";
    header("Location: product.php?id=".urlencode($productCode)."#reviews");
    exit;
}

$stmt=$conn->prepare("
    SELECT id,product_code,name,price,sale_price,is_bestseller,image,category,subcategory,description,stock
    FROM products WHERE product_code=? LIMIT 1
");
$stmt->execute([$productCode]);
$product=$stmt->fetch(PDO::FETCH_ASSOC);

if(!$product){
    http_response_code(404);
    $notFound=true;
}else{
    $stmt=$conn->prepare("SELECT media_type AS type,file_path AS path FROM product_media WHERE product_id=? ORDER BY sort_order,id");
    $stmt->execute([(int)$product["id"]]);
    $productGallery=[["type"=>"image","path"=>$product["image"]],...$stmt->fetchAll(PDO::FETCH_ASSOC)];

    $stmt=$conn->prepare("
        SELECT COUNT(*) AS review_count,COALESCE(AVG(rating),0) AS average_rating
        FROM product_reviews WHERE product_id=?
    ");
    $stmt->execute([$product["id"]]);
    $ratingSummary=$stmt->fetch(PDO::FETCH_ASSOC);
    $ratingCounts=array_fill(1,5,0);
    $stmt=$conn->prepare("SELECT rating,COUNT(*) AS review_count FROM product_reviews WHERE product_id=? GROUP BY rating");
    $stmt->execute([$product["id"]]);
    foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $ratingCount){
        $ratingCounts[(int)$ratingCount["rating"]]=(int)$ratingCount["review_count"];
    }

    $stmt=$conn->prepare("
        SELECT pr.rating,pr.comment,pr.created_at,SUBSTRING_INDEX(u.full_name,' ',1) AS reviewer_name,u.username
        FROM product_reviews pr
        JOIN users u ON u.id=pr.user_id
        WHERE pr.product_id=?
        ORDER BY pr.created_at DESC,pr.id DESC
        LIMIT 50
    ");
    $stmt->execute([$product["id"]]);
    $reviews=$stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt=$conn->prepare("
        SELECT p.product_code,p.name,COALESCE(p.sale_price,p.price) AS price,
               p.price AS regular_price,p.sale_price,p.image,
               COALESCE(AVG(pr.rating),0) AS average_rating,COUNT(pr.id) AS review_count
        FROM products p
        LEFT JOIN product_reviews pr ON pr.product_id=p.id
        WHERE p.category=? AND p.id<>?
        GROUP BY p.id,p.product_code,p.name,p.price,p.sale_price,p.image
        ORDER BY average_rating DESC,p.id DESC
        LIMIT 4
    ");
    $stmt->execute([$product["category"],$product["id"]]);
    $related=$stmt->fetchAll(PDO::FETCH_ASSOC);

    $eligibleReviews=[];
    $hasReviewed=false;
    if(isset($_SESSION["user_id"])&&($_SESSION["role"]??"")==="customer"){
        $stmt=$conn->prepare("SELECT COUNT(*) FROM product_reviews WHERE product_id=? AND user_id=?");
        $stmt->execute([$product["id"],(int)$_SESSION["user_id"]]);
        $hasReviewed=(int)$stmt->fetchColumn()>0;

        $stmt=$conn->prepare("
            SELECT oi.id AS order_item_id,o.order_number
            FROM order_items oi
            JOIN orders o ON o.id=oi.order_id
            WHERE oi.product_id=? AND o.user_id=? AND o.status='Completed'
              AND NOT EXISTS (SELECT 1 FROM product_reviews pr WHERE pr.order_item_id=oi.id)
            ORDER BY o.id DESC
        ");
        $stmt->execute([$product["id"],(int)$_SESSION["user_id"]]);
        $eligibleReviews=$stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

$reviewMessage=$_SESSION["product_review_message"]??"";
unset($_SESSION["product_review_message"]);
$accountHref=isset($_SESSION["user_id"])&&($_SESSION["role"]??"")==="customer"?"php/profile.php":"login.php";
$storeCategories=$conn->query("SELECT DISTINCT category FROM products WHERE category<>'' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title><?php echo isset($product["name"])?e($product["name"])." — SmartTech Appliances":"Product not found — SmartTech Appliances"; ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="styles.css" rel="stylesheet">
<link href="shop.css?v=product-ratings-2" rel="stylesheet">
<style>
.pd-wrap{max-width:1180px;margin:auto;padding:36px 24px 90px}
.pd-crumbs{display:flex;gap:9px;align-items:center;margin-bottom:24px;color:var(--st-ink-2);font-size:13px}
.pd-crumbs a{color:inherit}
.pd-main{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:44px;align-items:start}
.pd-image{display:grid;place-items:center;min-height:430px;border-radius:18px;background:#f1f2f6;padding:24px}
.pd-image img,.pd-image video{width:100%;height:390px;object-fit:contain}
.pd-gallery{min-width:0}.pd-thumbs{display:flex;gap:10px;overflow:auto;padding:12px 2px}.pd-thumb{width:72px;height:72px;flex:none;border:2px solid transparent;border-radius:10px;background:#f1f2f6;padding:5px;cursor:pointer}.pd-thumb.active{border-color:var(--st-clay)}.pd-thumb img{width:100%;height:100%;object-fit:contain}.pd-thumb i{font-size:24px;color:var(--st-ink)}
.pd-info{padding:8px 0}
.pd-kicker{color:var(--st-ink-2);font-size:13px}
.pd-info h1{margin:8px 0 12px;font-size:clamp(26px,4vw,38px);line-height:1.2}
.pd-rating{display:flex;align-items:center;gap:10px;margin-bottom:20px}
.pd-stars{color:#ed941f;letter-spacing:2px}.pd-stars.unrated{color:#26332f}
.pd-price{font-size:30px;font-weight:800;margin:18px 0}
.pd-price del{font-size:18px;color:var(--st-ink-2);font-weight:500;margin-left:8px}
.pd-labels{display:flex;gap:8px;margin:16px 0 0}.pd-labels span{padding:5px 9px;border-radius:6px;background:#f4e6c8;color:#5c3b0a;font-size:11px;font-weight:800;text-transform:uppercase}.pd-labels .sale{background:#a3321a;color:#fff}
.pd-stock{font-weight:700;font-size:13px;margin:0 0 18px}
.pd-assurance{display:flex;gap:12px;flex-wrap:wrap;margin:22px 0;color:var(--st-ink-2);font-size:13px}
.pd-assurance span{display:inline-flex;align-items:center;gap:6px}
.pd-actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:24px}
.pd-actions button{min-height:48px;padding:0 24px;border-radius:12px;font-weight:700}
.pd-add{border:0;background:var(--st-clay);color:white}
.pd-buy{border:1px solid var(--st-ink);background:white;color:var(--st-ink)}
.pd-section{margin-top:64px;padding-top:32px;border-top:1px solid var(--st-sand)}
.pd-section h2{font-size:25px;font-weight:800;margin-bottom:18px}
.pd-product-description-content{padding:24px;border:1px solid var(--st-sand);border-radius:16px;background:#fff;color:var(--st-ink-2);line-height:1.8;white-space:pre-line}
.pd-product-description-content strong{display:block;margin-top:18px;color:var(--st-ink)}
.pd-summary{display:grid;grid-template-columns:190px 1fr;gap:28px;padding:24px;border:1px solid var(--st-sand);border-radius:18px}
.pd-score{display:grid;place-items:center;align-content:center;gap:6px;text-align:center}
.pd-score strong{font-size:42px;line-height:1}
.pd-bars{display:grid;gap:8px}
.pd-bar{display:grid;grid-template-columns:36px 1fr 32px;gap:10px;align-items:center;font-size:13px}
.pd-bar-track{height:8px;border-radius:9px;background:#e9edf0;overflow:hidden}
.pd-bar-fill{height:100%;background:#ed941f}
.pd-review-layout{display:grid;grid-template-columns:minmax(0,1fr) 2fr;gap:24px;margin-top:22px}
.pd-review-form,.pd-review{padding:20px;border:1px solid var(--st-sand);border-radius:16px;background:#fff}
.pd-review-list{display:grid;gap:12px}
.pd-review p{margin:12px 0;line-height:1.6;white-space:pre-line}
.pd-review small{color:var(--st-ink-2)}
.pd-review-head{display:flex;justify-content:space-between;gap:12px;align-items:center}
.pd-related{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}
.pd-related a{color:inherit;text-decoration:none}
.pd-related img{width:100%;height:180px;object-fit:contain;background:#f1f2f6;border-radius:12px;padding:12px}
.pd-related b,.pd-related small{display:block;margin-top:8px}.pd-related .pd-stars{font-size:13px}.pd-related .pd-stars.unrated{color:#26332f}
.pd-notice{padding:14px 16px;border-radius:12px;background:#eef6f1;color:#1b6b4a}
.pd-empty{padding:24px;text-align:center;color:var(--st-ink-2)}
@media(max-width:767.98px){
    .pd-wrap{padding:20px 18px 76px}
    .pd-main{grid-template-columns:1fr;gap:22px}
    .pd-image{min-height:300px}
    .pd-image img{height:280px}
    .pd-price{font-size:26px}
    .pd-actions{display:grid;grid-template-columns:repeat(2,minmax(0,1fr))}
    .pd-actions>*{width:100%;padding:0 12px}
    .pd-summary{grid-template-columns:1fr;gap:20px    }
    @media(max-width:575.98px){.pd-actions{grid-template-columns:minmax(0,1fr)}}
    .pd-review-layout{grid-template-columns:1fr}
    .pd-related{grid-template-columns:repeat(2,minmax(0,1fr))}
    .pd-related img{height:145px}
}
</style>
</head>
<body>
<a class="visually-hidden-focusable st-skip" href="#main">Skip to content</a>
<div class="st-topbar text-center">Free delivery over ₱3,000 • 1-Year Warranty • GCash, Maya, COD</div>
<header class="st-header sticky-top">
<nav class="navbar navbar-expand-lg navbar-dark container-xxl px-lg-5" aria-label="Primary">
<a class="navbar-brand st-logo order-1 me-auto" href="index.html" aria-label="SmartTech home">
<span class="st-mark d-lg-none"><i class="bi bi-house" aria-hidden="true"></i></span>
<img class="d-none d-lg-block" src="images/smarttechappliances.svg" alt="" width="60" height="49">
<span>SmartTech</span>
</a>
<div class="d-flex gap-2 order-2 order-lg-3 ms-lg-4">
<button class="st-icon-btn d-lg-none" type="button" aria-label="Search" onclick="document.querySelector('.stq-btn')?.click()"><i class="bi bi-search"></i></button>
<a class="st-icon-btn d-none d-lg-inline-grid" href="wishlist.html" aria-label="Wishlist"><i class="bi bi-heart"></i></a>
<a class="st-icon-btn st-cart-btn" href="cart.php" aria-label="Cart"><i class="bi bi-bag"></i><span class="st-count" id="cart-count">0</span></a>
<a class="st-icon-btn d-none d-lg-inline-grid" href="<?php echo e($accountHref); ?>" aria-label="Account"><i class="bi bi-person"></i></a>
</div>
<div class="collapse navbar-collapse order-3 order-lg-2" id="nav">
<ul class="navbar-nav me-lg-4 gap-lg-3 pt-2 pt-lg-0" id="navList">
<li class="nav-item"><a class="nav-link" href="index.html">Home</a></li>
<?php foreach($storeCategories as $category): ?>
<li class="nav-item"><a class="nav-link<?php echo !empty($product)&&$product["category"]===$category?" active":""; ?>" href="products.php?cat=<?php echo urlencode($category); ?>" data-cat="<?php echo e($category); ?>"><?php echo e($category); ?></a></li>
<?php endforeach; ?>
</ul>
<form class="st-search ms-lg-auto flex-grow-1 my-3 my-lg-0" role="search" onsubmit="return false">
<i class="bi bi-search" aria-hidden="true"></i>
<input type="search" class="form-control" placeholder="Search appliances" aria-label="Search products" readonly>
</form>
</div>
</nav>
</header>
<span class="st-icons" hidden></span>
<main class="pd-wrap" id="main">
<?php if(!empty($notFound)): ?>
<div class="pd-empty"><h1>Product not found</h1><p>This item may have been removed from the shop.</p><a class="btn-clay" href="products.php">Back to shop</a></div>
<?php else: ?>
<nav class="pd-crumbs" aria-label="Breadcrumb">
<a href="index.html">Home</a><i class="bi bi-chevron-right"></i>
<a href="products.php">Shop</a><i class="bi bi-chevron-right"></i>
<span><?php echo e($product["category"]); ?></span><i class="bi bi-chevron-right"></i>
<b><?php echo e($product["name"]); ?></b>
</nav>

<?php if($reviewMessage): ?><p class="pd-notice" role="status"><?php echo e($reviewMessage); ?></p><?php endif; ?>

<section class="pd-main">
<div class="pd-gallery">
<div class="pd-image" id="productMediaViewer">
<img src="<?php echo e($product["image"]); ?>" alt="<?php echo e($product["name"]); ?>" onerror="this.style.visibility='hidden'">
</div>
<?php if(count($productGallery)>1): ?>
<div class="pd-thumbs" aria-label="Product images and videos">
<?php foreach($productGallery as $index=>$media): ?>
<button class="pd-thumb<?php echo $index===0?" active":""; ?>" type="button" data-gallery-index="<?php echo (int)$index; ?>" aria-label="<?php echo $media["type"]==="video"?"Play product video":"View product image ".($index+1); ?>">
<?php if($media["type"]==="video"): ?><i class="bi bi-play-circle" aria-hidden="true"></i><?php else: ?><img src="<?php echo e($media["path"]); ?>" alt=""><?php endif; ?>
</button>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>
<div class="pd-info">
<span class="pd-kicker"><?php echo e(ucfirst($product["category"])); ?> · <?php echo e($product["subcategory"]); ?></span>
<h1><?php echo e($product["name"]); ?></h1>
<div class="pd-rating">
<span class="pd-stars<?php echo (int)$ratingSummary["review_count"]===0?" unrated":""; ?>"><?php echo e(renderStars($ratingSummary["average_rating"],(int)$ratingSummary["review_count"]>0)); ?></span>
<b><?php echo number_format((float)$ratingSummary["average_rating"],1); ?></b>
<a href="#reviews"><?php echo (int)$ratingSummary["review_count"]; ?> reviews</a>
</div>
<?php if($product["sale_price"]!==null||$product["is_bestseller"]): ?>
<div class="pd-labels">
<?php if($product["sale_price"]!==null): ?><span class="sale">Sale</span><?php endif; ?>
<?php if($product["is_bestseller"]): ?><span>Bestseller</span><?php endif; ?>
</div>
<?php endif; ?>
<p class="pd-price">₱<?php echo number_format((float)($product["sale_price"]??$product["price"]),2); ?><?php if($product["sale_price"]!==null): ?> <del>₱<?php echo number_format((float)$product["price"],2); ?></del><?php endif; ?></p>
<p class="pd-stock <?php echo (int)$product["stock"]>0?"text-success":"text-danger"; ?>">
<?php echo (int)$product["stock"]>0?(int)$product["stock"]." in stock":"Currently out of stock"; ?>
</p>
<div class="pd-assurance">
<span><i class="bi bi-shield-check"></i> 1-year warranty</span>
<span><i class="bi bi-truck"></i> Free delivery over ₱3,000</span>
<span><i class="bi bi-arrow-repeat"></i> Return requests within 7 days of delivery</span>
</div>
<div class="pd-actions">
<button class="pd-add" type="button" data-detail-add <?php echo (int)$product["stock"]<=0?"disabled":""; ?>>Add to Cart</button>
<button class="pd-buy" type="button" data-detail-buy <?php echo (int)$product["stock"]<=0?"disabled":""; ?>>Buy Now</button>
</div>
</div>
</section>

<section class="pd-section" id="description">
<h2>Product Description</h2>
<div class="pd-product-description-content"><?php echo safeDescription($product["description"]); ?></div>
</section>

<section class="pd-section" id="reviews">
<h2>Product reviews</h2>
<div class="pd-summary">
<div class="pd-score">
<strong><?php echo number_format((float)$ratingSummary["average_rating"],1); ?></strong>
<span class="pd-stars<?php echo (int)$ratingSummary["review_count"]===0?" unrated":""; ?>"><?php echo e(renderStars($ratingSummary["average_rating"],(int)$ratingSummary["review_count"]>0)); ?></span>
<small>Based on <?php echo (int)$ratingSummary["review_count"]; ?> verified reviews</small>
</div>
<div class="pd-bars">
<?php for($stars=5;$stars>=1;$stars--):
    $count=$ratingCounts[$stars];
    $percent=(int)$ratingSummary["review_count"]?($count/(int)$ratingSummary["review_count"]*100):0;
?>
<div class="pd-bar"><span><?php echo $stars; ?> ★</span><div class="pd-bar-track"><div class="pd-bar-fill" style="width:<?php echo $percent; ?>%"></div></div><b><?php echo $count; ?></b></div>
<?php endfor; ?>
</div>
</div>
<div class="pd-review-layout">
<div>
<?php if($eligibleReviews): ?>
<form class="pd-review-form" method="POST">
<h3 class="fs-5 fw-bold">Rate your purchase</h3>
<input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
<label class="form-label" for="reviewOrder">Completed purchase</label>
<select class="form-select mb-3" id="reviewOrder" name="order_item_id" required>
<?php foreach($eligibleReviews as $eligible): ?>
<option value="<?php echo (int)$eligible["order_item_id"]; ?>"><?php echo e($eligible["order_number"]); ?></option>
<?php endforeach; ?>
</select>
<label class="form-label" for="reviewRating">Rating</label>
<select class="form-select mb-3" id="reviewRating" name="rating" required>
<option value="5">★★★★★ — Excellent</option><option value="4">★★★★☆ — Good</option><option value="3">★★★☆☆ — Okay</option><option value="2">★★☆☆☆ — Poor</option><option value="1">★☆☆☆☆ — Very poor</option>
</select>
<label class="form-label" for="reviewComment">Your review</label>
<textarea class="form-control mb-3" id="reviewComment" name="comment" rows="4" minlength="5" maxlength="1000" required placeholder="Share what you liked or what could be better"></textarea>
<button class="btn-clay" type="submit">Post review</button>
</form>
<?php elseif(!isset($_SESSION["user_id"])||($_SESSION["role"]??"")!=="customer"): ?>
<div class="pd-review-form"><h3 class="fs-5 fw-bold">Bought this product?</h3><p>Sign in and complete your order to leave a verified review.</p><a href="login.php?next=<?php echo urlencode("product.php?id=".$productCode."#reviews"); ?>">Sign in to review</a></div>
<?php else: ?>
<div class="pd-review-form"><h3 class="fs-5 fw-bold">Verified reviews</h3><p><?php echo $hasReviewed?"You already reviewed this product. Thanks for sharing your experience.":"Reviews are available after you confirm delivery in My Orders."; ?></p><?php if(!$hasReviewed): ?><a href="php/profile.php?tab=orders">View My Orders</a><?php endif; ?></div>
<?php endif; ?>
</div>
<div class="pd-review-list">
<?php if(!$reviews): ?><div class="pd-empty">No reviews yet. Be the first to review after completing a purchase.</div><?php endif; ?>
<?php foreach($reviews as $review): ?>
<article class="pd-review">
<div class="pd-review-head"><b><?php echo e($review["reviewer_name"]?:$review["username"]); ?></b><small><?php echo e(date("M j, Y",strtotime($review["created_at"]))); ?></small></div>
<div class="pd-stars"><?php echo e(renderStars($review["rating"])); ?></div>
<p><?php echo e($review["comment"]); ?></p>
<small><i class="bi bi-patch-check-fill"></i> Verified purchase</small>
</article>
<?php endforeach; ?>
</div>
</div>
</section>

<?php if($related): ?>
<section class="pd-section">
<h2>Related products</h2>
<div class="pd-related">
<?php foreach($related as $item): ?>
<a href="product.php?id=<?php echo urlencode($item["product_code"]); ?>">
<img src="<?php echo e($item["image"]); ?>" alt="" loading="lazy">
<b><?php echo e($item["name"]); ?></b>
<small><span class="pd-stars<?php echo (int)$item["review_count"]===0?" unrated":""; ?>"><?php echo e(renderStars($item["average_rating"],(int)$item["review_count"]>0)); ?></span> · <?php echo (int)$item["review_count"]?number_format((float)$item["average_rating"],1)." · ":"No ratings · "; ?>₱<?php echo number_format((float)$item["price"],2); ?></small>
</a>
<?php endforeach; ?>
</div>
</section>
<?php endif; ?>
<?php endif; ?>
</main>
<nav class="st-bottomnav d-lg-none" aria-label="Mobile navigation">
<a href="index.html"><i class="bi bi-house"></i><span>Home</span></a>
<a href="products.php" class="active" aria-current="page"><i class="bi bi-shop"></i><span>Shop</span></a>
<a href="wishlist.html"><i class="bi bi-heart"></i><span>Wishlist</span></a>
<a href="cart.php"><i class="bi bi-bag"></i><span>Cart</span></a>
<a href="<?php echo e($accountHref); ?>"><i class="bi bi-person"></i><span>Account</span></a>
</nav>
<script>
const PRODUCT=<?php echo empty($notFound)?json_encode(["id"=>$product["product_code"],"name"=>$product["name"],"price"=>(float)($product["sale_price"]??$product["price"]),"img"=>$product["image"],"stock"=>(int)$product["stock"]],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT):"null"; ?>;
const PRODUCT_GALLERY=<?php echo empty($notFound)?json_encode($productGallery,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT):"[]"; ?>;
document.querySelectorAll('[data-gallery-index]').forEach(button=>button.addEventListener('click',()=>{
    const media=PRODUCT_GALLERY[Number(button.dataset.galleryIndex)];
    const viewer=document.getElementById('productMediaViewer');
    if(!media||!viewer)return;
    document.querySelectorAll('[data-gallery-index]').forEach(item=>item.classList.toggle('active',item===button));
    if(media.type==='video'){
        const video=document.createElement('video');
        video.src=media.path;
        video.controls=true;
        video.playsInline=true;
        video.setAttribute('aria-label','Product video');
        viewer.replaceChildren(video);
    }else{
        const image=document.createElement('img');
        image.src=media.path;
        image.alt=PRODUCT.name;
        viewer.replaceChildren(image);
    }
}));
const cartCount=document.getElementById('cart-count');
if(cartCount){
    try{
        const cart=JSON.parse(localStorage.getItem('st_cart')||'[]');
        cartCount.textContent=String(Array.isArray(cart)?cart.reduce((sum,item)=>sum+Number(item.qty||1),0):0);
    }catch(error){
        console.error('Unable to read cart count.',error);
    }
}
function detailCart(buy){
    if(!PRODUCT)return;
    let items=[];
    try{
        items=JSON.parse(localStorage.getItem('st_cart')||'[]');
        if(!Array.isArray(items))throw new Error('Invalid cart data');
    }catch(error){
        alert('Your cart could not be read. Please refresh the page and try again.');
        return;
    }
    const existing=items.find(item=>item.id===PRODUCT.id);
    if(existing){
        if(Number(existing.qty||1)>=PRODUCT.stock){alert(`Only ${PRODUCT.stock} stock available.`);return}
        existing.qty=Number(existing.qty||1)+1;
    }else{
        items.push({id:PRODUCT.id,name:PRODUCT.name,price:PRODUCT.price,img:PRODUCT.img,qty:1});
    }
    try{
        localStorage.setItem('st_cart',JSON.stringify(items));
        if(buy)sessionStorage.setItem('st_buy',PRODUCT.id);
    }catch(error){
        alert('Your cart could not be saved. Please check your browser storage and try again.');
        return;
    }
    if(buy){
        location.href='cart.php';
        return;
    }
    const count=document.getElementById('cart-count');
    if(count)count.textContent=String(items.reduce((sum,item)=>sum+Number(item.qty||1),0));
    alert('Added to cart.');
}
document.querySelector('[data-detail-add]')?.addEventListener('click',()=>detailCart(false));
document.querySelector('[data-detail-buy]')?.addEventListener('click',()=>detailCart(true));
</script>
<script src="search.js?v=dynamic-catalog-1"></script>
<script src="back-to-top.js"></script>
</body>
</html>
