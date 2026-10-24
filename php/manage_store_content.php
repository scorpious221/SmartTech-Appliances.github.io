<?php
session_start();
require_once "database.php";
require_once "admin_media.php";
requireAdminPost();
header("Content-Type: application/json; charset=utf-8");

function contentResponse(int $status,array $body):void{
    http_response_code($status);
    echo json_encode($body,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    exit;
}

function contentText(string $key,int $max,bool $required=true):string{
    $value=trim((string)($_POST[$key]??""));
    if(($required&&$value==="")||mb_strlen($value)>$max){
        throw new RuntimeException("Check the ".str_replace("_"," ",$key)." field.");
    }
    return$value;
}

function contentImage(?array $uploaded,?string $existing,bool $required=true):string{
    if($uploaded){
        if($uploaded["type"]!=="image")throw new RuntimeException("Choose an image file for this content.");
        return$uploaded["path"];
    }
    if($existing!==null&&$existing!=="")return$existing;
    if($required)throw new RuntimeException("Upload an image for this content.");
    return"";
}

function contentUpload():?array{
    return saveAdminUpload($_FILES["image"]??[]);
}

function contentLink(string $key,string $default=""):string{
    $link=trim((string)($_POST[$key]??$default));
    $external=preg_match('/^https?:\/\//i',$link)===1;
    if($link===""||strncmp($link,"//",2)===0||preg_match('/[\r\n]/',$link)||(!$external&&preg_match('/^[a-z][a-z0-9+.-]*:/i',$link))){
        throw new RuntimeException("Enter a valid link for the button.");
    }
    return$link;
}

function storedContentImage(PDO $conn,string $table,int $id):?string{
    $stmt=$conn->prepare("SELECT image FROM $table WHERE id=?");
    $stmt->execute([$id]);
    $path=$stmt->fetchColumn();
    return$path===false?null:(string)$path;
}

$action=(string)($_POST["action"]??"");
$rawId=trim((string)($_POST["id"]??""));
$id=$rawId===""?0:filter_var($rawId,FILTER_VALIDATE_INT);
$oldImage=null;
$newUpload=null;

try{
    if(!in_array($action,["save_promo","delete_promo","save_bundle","delete_bundle","save_category","delete_category","save_deal","save_demo_video","save_voucher","delete_voucher"],true)){
        contentResponse(400,["ok"=>false,"message"=>"Unsupported content action."]);
    }
    if($id===false||$id<0)throw new RuntimeException("Invalid content ID.");

    if(strncmp($action,"save_",5)===0&&$id>0){
        $table=["save_promo"=>"home_promos","save_bundle"=>"home_bundles","save_category"=>"home_categories","save_deal"=>"home_deal","save_demo_video"=>"home_demo_video","save_voucher"=>"vouchers"][$action];
        if($action==="save_demo_video"){
            $videoStmt=$conn->prepare("SELECT video_path FROM home_demo_video WHERE id=?");
            $videoStmt->execute([$id]);
            $oldImage=$videoStmt->fetchColumn();
            if($oldImage===false)throw new RuntimeException("The homepage demo video setting is not configured.");
            $oldImage=(string)$oldImage;
        }elseif($table!=="vouchers"){
            $oldImage=storedContentImage($conn,$table,$id);
        }
        if($table!=="vouchers"&&$oldImage===null)throw new RuntimeException("This content item no longer exists.");
    }

    if(in_array($action,["delete_promo","delete_bundle","delete_category"],true)){
        if($id<=0)throw new RuntimeException("Invalid content ID.");
        $table=["delete_promo"=>"home_promos","delete_bundle"=>"home_bundles","delete_category"=>"home_categories"][$action];
        $oldImage=storedContentImage($conn,$table,$id);
        if($oldImage===null)throw new RuntimeException("This content item no longer exists.");
        $stmt=$conn->prepare("DELETE FROM $table WHERE id=?");
        $stmt->execute([$id]);
        deleteAdminUpload($oldImage);
    }elseif($action==="delete_voucher"){
        if($id<=0)throw new RuntimeException("Invalid voucher ID.");
        $stmt=$conn->prepare("DELETE FROM vouchers WHERE id=?");
        $stmt->execute([$id]);
        if(!$stmt->rowCount())throw new RuntimeException("This voucher no longer exists.");
    }elseif($action==="save_promo"){
        $newUpload=contentUpload();
        $image=contentImage($newUpload,$oldImage);
        $productCode=trim((string)($_POST["product_code"]??""));
        if($productCode!==""){
            $productCheck=$conn->prepare("SELECT product_code FROM products WHERE product_code=?");
            $productCheck->execute([$productCode]);
            if(!$productCheck->fetchColumn())throw new RuntimeException("Choose a product that is currently available.");
        }else{
            $productCode=null;
        }
        $data=[
            contentText("title",180),
            contentText("description",2000),
            contentText("button_label",80),
            contentText("image_alt",180,false),
            $image,$productCode,
            contentLink("link_url"),
            isset($_POST["active"])?1:0,
            max(0,(int)($_POST["sort_order"]??0))
        ];
        if($id>0){
            $stmt=$conn->prepare("UPDATE home_promos SET title=?,description=?,button_label=?,image_alt=?,image=?,product_code=?,link_url=?,active=?,sort_order=? WHERE id=?");
            $stmt->execute([...$data,$id]);
        }else{
            $stmt=$conn->prepare("INSERT INTO home_promos(title,description,button_label,image_alt,image,product_code,link_url,active,sort_order) VALUES(?,?,?,?,?,?,?,?,?)");
            $stmt->execute($data);
        }
    }elseif($action==="save_bundle"){
        $newUpload=contentUpload();
        $image=contentImage($newUpload,$oldImage);
        $productCodes=array_values(array_unique(array_filter(array_map("trim",$_POST["product_codes"]??[]))));
        if(!$productCodes)throw new RuntimeException("Select at least one product for the bundle.");
        $check=$conn->prepare("SELECT product_code,price FROM products WHERE product_code IN (".implode(",",array_fill(0,count($productCodes),"?")).")");
        $check->execute($productCodes);
        $bundleProducts=$check->fetchAll(PDO::FETCH_ASSOC);
        if(count($bundleProducts)!==count($productCodes))throw new RuntimeException("One or more bundle products are no longer available.");
        $originalCents=array_reduce($bundleProducts,static function(int $total,array $product):int{
            return $total+(int)round((float)$product["price"]*100);
        },0);
        $discountPercent=filter_var($_POST["discount_percent"]??null,FILTER_VALIDATE_FLOAT);
        if($discountPercent===false||$discountPercent<0||$discountPercent>=100)throw new RuntimeException("Choose a valid bundle discount percentage.");
        $priceCents=(int)round($originalCents*(100-$discountPercent)/100);
        if($originalCents<=0||$priceCents<=0)throw new RuntimeException("The selected products and discount must produce a valid bundle price.");
        $price=$priceCents/100;
        $compare=$originalCents/100;
        $data=[
            contentText("title",180),
            contentText("description",500,false),
            json_encode($productCodes,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),
            $price,$compare,$image,contentLink("link_url","products.php"),
            isset($_POST["active"])?1:0,max(0,(int)($_POST["sort_order"]??0))
        ];
        if($id>0){
            $stmt=$conn->prepare("UPDATE home_bundles SET title=?,description=?,product_codes=?,price=?,compare_at_price=?,image=?,link_url=?,active=?,sort_order=? WHERE id=?");
            $stmt->execute([...$data,$id]);
        }else{
            $stmt=$conn->prepare("INSERT INTO home_bundles(title,description,product_codes,price,compare_at_price,image,link_url,active,sort_order) VALUES(?,?,?,?,?,?,?,?,?)");
            $stmt->execute($data);
        }
    }elseif($action==="save_category"){
        $newUpload=contentUpload();
        $image=contentImage($newUpload,$oldImage);
        $category=contentText("category",100);
        $layout=(string)($_POST["layout_class"]??"");
        $allowedLayouts=["","wide","tall","wide tall"];
        if(!in_array($layout,$allowedLayouts,true))throw new RuntimeException("Choose a valid category tile layout.");
        $icon=contentText("icon",60);
        if(!preg_match('/^[a-z0-9-]+$/',$icon))throw new RuntimeException("Enter a valid Bootstrap icon name.");
        $data=[
            contentText("title",100),contentText("description",255,false),$icon,$image,$category,
            contentText("subcategory",120,false),$layout,isset($_POST["active"])?1:0,max(0,(int)($_POST["sort_order"]??0))
        ];
        if($id>0){
            $stmt=$conn->prepare("UPDATE home_categories SET title=?,description=?,icon=?,image=?,category=?,subcategory=?,layout_class=?,active=?,sort_order=? WHERE id=?");
            $stmt->execute([...$data,$id]);
        }else{
            $stmt=$conn->prepare("INSERT INTO home_categories(title,description,icon,image,category,subcategory,layout_class,active,sort_order) VALUES(?,?,?,?,?,?,?,?,?)");
            $stmt->execute($data);
        }
    }elseif($action==="save_deal"){
        if($id!==1)throw new RuntimeException("Invalid deal of the month record.");
        $newUpload=contentUpload();
        $image=contentImage($newUpload,$oldImage,false);
        $productCode=contentText("product_code",100);
        $productCheck=$conn->prepare("SELECT product_code FROM products WHERE product_code=?");
        $productCheck->execute([$productCode]);
        if(!$productCheck->fetchColumn())throw new RuntimeException("Choose a product that is currently available.");
        $compare=filter_var($_POST["compare_at_price"]??0,FILTER_VALIDATE_FLOAT);
        if($compare===false||$compare<0)throw new RuntimeException("Enter a valid original price.");
        $endValue=trim((string)($_POST["ends_at"]??""));
        $endAt=null;
        if($endValue!==""){
            $endDate=DateTime::createFromFormat("Y-m-d\\TH:i",$endValue);
            $dateErrors=DateTime::getLastErrors();
            if(!$endDate||($dateErrors&&($dateErrors["warning_count"]||$dateErrors["error_count"])))throw new RuntimeException("Enter a valid deal end date.");
            $endAt=$endDate->format("Y-m-d H:i:s");
        }
        $stmt=$conn->prepare("UPDATE home_deal SET product_code=?,title=?,description=?,image=?,compare_at_price=?,ends_at=?,active=? WHERE id=1");
        $stmt->execute([
            $productCode,contentText("title",180),contentText("description",2000),
            $image,$compare,$endAt,isset($_POST["active"])?1:0
        ]);
    }elseif($action==="save_demo_video"){
        if($id!==1)throw new RuntimeException("Invalid homepage demo video setting.");
        $newUpload=saveAdminUpload($_FILES["video"]??[]);
        if(!$newUpload||$newUpload["type"]!=="video"){
            if($newUpload)deleteAdminUpload($newUpload["path"]);
            $newUpload=null;
            throw new RuntimeException("Choose an MP4, WebM, or OGG video to replace the homepage demo.");
        }
        $stmt=$conn->prepare("UPDATE home_demo_video SET video_path=? WHERE id=1");
        $stmt->execute([$newUpload["path"]]);
    }elseif($action==="save_voucher"){
        $code=strtoupper(contentText("code",40));
        if(!preg_match('/^[A-Z0-9_-]+$/',$code))throw new RuntimeException("Voucher codes may contain only letters, numbers, hyphens, and underscores.");
        $type=(string)($_POST["discount_type"]??"");
        $value=filter_var($_POST["discount_value"]??null,FILTER_VALIDATE_FLOAT);
        $minimum=filter_var($_POST["minimum_spend"]??0,FILTER_VALIDATE_FLOAT);
        $cap=trim((string)($_POST["discount_cap"]??""))===""?null:filter_var($_POST["discount_cap"],FILTER_VALIDATE_FLOAT);
        if(!in_array($type,["fixed","percent"],true)||$value===false||$value<=0||($type==="percent"&&$value>100)||$minimum===false||$minimum<0||($cap!==null&&($cap===false||$cap<=0))){
            throw new RuntimeException("Enter valid discount settings. Percentage vouchers must be between 0 and 100.");
        }
        $products=array_values(array_unique(array_filter(array_map("trim",$_POST["eligible_products"]??[]))));
        $categories=array_values(array_unique(array_filter(array_map("trim",$_POST["eligible_categories"]??[]))));
        $sets=[];
        $rawSets=trim((string)($_POST["bundle_sets"]??""));
        if($rawSets!==""){
            foreach(preg_split('/\R/',$rawSets) as $line){
                $set=array_values(array_unique(array_filter(array_map("trim",explode(",",$line)))));
                if($set)$sets[]=$set;
            }
        }
        if($sets){
            $products=array_values(array_unique(array_merge(...$sets)));
            $categories=[];
        }
        $allCodes=array_values(array_unique(array_merge($products,...$sets)));
        if($allCodes){
            $check=$conn->prepare("SELECT COUNT(*) FROM products WHERE product_code IN (".implode(",",array_fill(0,count($allCodes),"?")).")");
            $check->execute($allCodes);
            if((int)$check->fetchColumn()!==count($allCodes))throw new RuntimeException("An eligible product code is no longer available.");
        }
        $validCategories=$conn->query("SELECT DISTINCT category FROM products")->fetchAll(PDO::FETCH_COLUMN);
        if(array_diff($categories,$validCategories))throw new RuntimeException("Choose valid product categories.");
        $toSqlDate=static function(string $value):?string{
            if($value==="")return null;
            $date=DateTime::createFromFormat("Y-m-d\\TH:i",$value);
            $errors=DateTime::getLastErrors();
            if(!$date||($errors&&($errors["warning_count"]||$errors["error_count"])))throw new RuntimeException("Enter valid voucher start and end dates.");
            return$date->format("Y-m-d H:i:s");
        };
        $starts=$toSqlDate(trim((string)($_POST["starts_at"]??"")));
        $ends=$toSqlDate(trim((string)($_POST["ends_at"]??"")));
        if($starts&&$ends&&strtotime($starts)>strtotime($ends))throw new RuntimeException("The voucher end date must be after its start date.");
        $total=trim((string)($_POST["total_usage_limit"]??""))===""?null:filter_var($_POST["total_usage_limit"],FILTER_VALIDATE_INT);
        $perCustomer=trim((string)($_POST["per_customer_limit"]??""))===""?null:filter_var($_POST["per_customer_limit"],FILTER_VALIDATE_INT);
        if(($total!==null&&($total===false||$total<1))||($perCustomer!==null&&($perCustomer===false||$perCustomer<1)))throw new RuntimeException("Usage limits must be positive whole numbers.");
        $data=[
            $code,contentText("label",180),contentText("description",500,false),$type,$value,$minimum,
            isset($_POST["first_order_only"])?1:0,$cap,
            json_encode($products,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),
            json_encode($categories,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),
            json_encode($sets,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),
            $starts,$ends,$total,$perCustomer,isset($_POST["active"])?1:0
        ];
        if($id>0){
            $stmt=$conn->prepare("UPDATE vouchers SET code=?,label=?,description=?,discount_type=?,discount_value=?,minimum_spend=?,first_order_only=?,discount_cap=?,eligible_products=?,eligible_categories=?,required_product_sets=?,starts_at=?,ends_at=?,total_usage_limit=?,per_customer_limit=?,active=? WHERE id=?");
            $stmt->execute([...$data,$id]);
        }else{
            $stmt=$conn->prepare("INSERT INTO vouchers(code,label,description,discount_type,discount_value,minimum_spend,first_order_only,discount_cap,eligible_products,eligible_categories,required_product_sets,starts_at,ends_at,total_usage_limit,per_customer_limit,active) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute($data);
        }
    }
    if($newUpload&&$oldImage)deleteAdminUpload($oldImage);
    contentResponse(200,["ok"=>true,"message"=>"Changes saved."]);
}catch(RuntimeException $error){
    if($newUpload)deleteAdminUpload($newUpload["path"]);
    contentResponse(400,["ok"=>false,"message"=>$error->getMessage()]);
}catch(PDOException $error){
    if($newUpload)deleteAdminUpload($newUpload["path"]);
    if((string)$error->getCode()==="23000")contentResponse(409,["ok"=>false,"message"=>"That voucher code is already in use."]);
    throw$error;
}
?>
