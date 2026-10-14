<?php

require_once "database.php";

$jsFile = __DIR__ . "/../products-data.js";

if (!file_exists($jsFile)) {
    die("products-data.js was not found.");
}

$js = file_get_contents($jsFile);

/*
    Reads these values from products-data.js:

    id
    name
    price
    img
    category
    sub
    desc
*/

$pattern = "/\{
    \s*id:'([^']+)',
    \s*name:'([^']+)',
    \s*price:([0-9.]+),
    \s*img:'([^']+)',
    \s*category:'([^']+)',
    \s*sub:'([^']+)',
    \s*desc:\s*`(.*?)`
    \s*\}
/sx";

preg_match_all(
    $pattern,
    $js,
    $products,
    PREG_SET_ORDER
);

if (!$products) {
    die("No products were found in products-data.js.");
}

$sql = "
    INSERT INTO products
    (
        product_code,
        name,
        price,
        image,
        category,
        subcategory,
        description,
        stock
    )
    VALUES
    (
        :product_code,
        :name,
        :price,
        :image,
        :category,
        :subcategory,
        :description,
        :stock
    )
";

$stmt = $conn->prepare($sql);

$added = 0;
$skipped = 0;

foreach ($products as $product) {

    $productCode = $product[1];
    $name = $product[2];
    $price = $product[3];
    $image = $product[4];
    $category = $product[5];
    $subcategory = $product[6];
    $description = $product[7];

    /*
        Random stock from 5 to 30.
        This only happens when the product is first inserted.
    */
    $stock = random_int(5, 30);

    $check = $conn->prepare(
        "SELECT id
         FROM products
         WHERE product_code = ?"
    );

    $check->execute([$productCode]);

    if ($check->fetch()) {
        $skipped++;
        continue;
    }

    $stmt->execute([
        ":product_code" => $productCode,
        ":name" => $name,
        ":price" => $price,
        ":image" => $image,
        ":category" => $category,
        ":subcategory" => $subcategory,
        ":description" => $description,
        ":stock" => $stock
    ]);

    $added++;
}

echo "<h2>SmartTech Product Import</h2>";

echo "<p>Products found: "
    . count($products)
    . "</p>";

echo "<p>Products added: "
    . $added
    . "</p>";

echo "<p>Products skipped: "
    . $skipped
    . "</p>";

echo "<p><b>Product import completed!</b></p>";

?>