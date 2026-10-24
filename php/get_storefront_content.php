<?php
require_once "database.php";
header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store, max-age=0");

try{
    $productsStmt=$conn->query("
        SELECT p.product_code AS id,p.name,COALESCE(p.sale_price,p.price) AS price,
               p.price AS regular_price,p.sale_price IS NOT NULL AS is_sale,
               p.image AS img,p.category,p.subcategory AS sub,p.description AS description,p.stock,
               COALESCE(r.average_rating,0) AS average_rating,COALESCE(r.review_count,0) AS review_count,
               COALESCE(s.units_sold,0) AS units_sold
        FROM products p
        LEFT JOIN (
            SELECT product_id,AVG(rating) AS average_rating,COUNT(*) AS review_count
            FROM product_reviews
            GROUP BY product_id
        ) r ON r.product_id=p.id
        LEFT JOIN (
            SELECT oi.product_id,SUM(oi.quantity) AS units_sold
            FROM order_items oi
            JOIN orders o ON o.id=oi.order_id
            WHERE o.status IN ('Delivered','Completed')
            GROUP BY oi.product_id
        ) s ON s.product_id=p.id
        ORDER BY p.id ASC
    ");
    $products=$productsStmt->fetchAll(PDO::FETCH_ASSOC);
    $bestSellingProducts=$products;
    usort($bestSellingProducts,static function(array $a,array $b):int{
        $salesOrder=(int)$b["units_sold"]<=>(int)$a["units_sold"];
        return $salesOrder!==0?$salesOrder:strcmp((string)$a["name"],(string)$b["name"]);
    });
    $bestSellerIds=[];
    foreach($bestSellingProducts as $product){
        if((int)$product["units_sold"]<=0||count($bestSellerIds)>=8)break;
        $bestSellerIds[(string)$product["id"]]=true;
    }
    foreach($products as &$product){
        $product["price"]=(float)$product["price"];
        $product["regular_price"]=(float)$product["regular_price"];
        $product["is_sale"]=(bool)$product["is_sale"];
        $product["is_bestseller"]=isset($bestSellerIds[(string)$product["id"]]);
        $product["units_sold"]=(int)$product["units_sold"];
        $product["average_rating"]=(float)$product["average_rating"];
        $product["review_count"]=(int)$product["review_count"];
        $product["stock"]=(int)$product["stock"];
    }
    unset($product);

    $promos=$conn->query("SELECT id,title,description,button_label,image,image_alt,product_code,link_url FROM home_promos WHERE active=1 ORDER BY sort_order,id")->fetchAll(PDO::FETCH_ASSOC);
    $categories=$conn->query("SELECT id,title,description,icon,image,category,subcategory,layout_class FROM home_categories WHERE active=1 ORDER BY sort_order,id")->fetchAll(PDO::FETCH_ASSOC);
    $bundleRows=$conn->query("SELECT id,title,description,product_codes,price,compare_at_price,image,link_url FROM home_bundles WHERE active=1 ORDER BY sort_order,id")->fetchAll(PDO::FETCH_ASSOC);
    $bundles=[];
    foreach($bundleRows as $bundle){
        $bundle["product_codes"]=json_decode((string)$bundle["product_codes"],true)?:[];
        $bundle["price"]=(float)$bundle["price"];
        $bundle["compare_at_price"]=(float)$bundle["compare_at_price"];
        $bundles[]=$bundle;
    }
    $dealStmt=$conn->query("
        SELECT d.product_code,d.title,d.description,d.image,d.compare_at_price,d.ends_at,
               p.name AS product_name,p.price AS product_price,p.image AS product_image
        FROM home_deal d
        JOIN products p ON p.product_code=d.product_code
        WHERE d.active=1 AND (d.ends_at IS NULL OR d.ends_at>=NOW())
        ORDER BY d.id
        LIMIT 1
    ");
    $deal=$dealStmt->fetch(PDO::FETCH_ASSOC)?:null;
    if($deal){
        $deal["compare_at_price"]=(float)$deal["compare_at_price"];
        $deal["product_price"]=(float)$deal["product_price"];
        if($deal["image"]==="")$deal["image"]=$deal["product_image"];
    }
    $demoVideo=(string)$conn->query("SELECT video_path FROM home_demo_video WHERE id=1")->fetchColumn();

    echo json_encode(
        ["products"=>$products,"promos"=>$promos,"categories"=>$categories,"bundles"=>$bundles,"deal"=>$deal,"demo_video"=>$demoVideo],
        JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_THROW_ON_ERROR
    );
}catch(Throwable $error){
    error_log("Unable to load storefront content: ".$error->getMessage());
    http_response_code(500);
    echo json_encode(["message"=>"Storefront content is temporarily unavailable."]);
}
?>
