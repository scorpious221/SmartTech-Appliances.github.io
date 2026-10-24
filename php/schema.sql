CREATE TABLE IF NOT EXISTS products (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_code VARCHAR(100) NOT NULL,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    sale_price DECIMAL(10, 2) NULL DEFAULT NULL,
    is_bestseller TINYINT(1) NOT NULL DEFAULT 0,
    image VARCHAR(512) NOT NULL DEFAULT '',
    category VARCHAR(100) NOT NULL,
    subcategory VARCHAR(100) NOT NULL DEFAULT '',
    description LONGTEXT NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_products_product_code (product_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE products
    ADD COLUMN IF NOT EXISTS sale_price DECIMAL(10, 2) NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS is_bestseller TINYINT(1) NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS product_media (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id INT UNSIGNED NOT NULL,
    media_type VARCHAR(10) NOT NULL,
    file_path VARCHAR(512) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_product_media_product (product_id,sort_order,id),
    CONSTRAINT fk_product_media_product
        FOREIGN KEY (product_id) REFERENCES products (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS home_promos (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    button_label VARCHAR(80) NOT NULL,
    image VARCHAR(512) NOT NULL DEFAULT '',
    image_alt VARCHAR(180) NOT NULL DEFAULT '',
    product_code VARCHAR(100) NULL,
    link_url VARCHAR(512) NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE home_promos
    ADD COLUMN IF NOT EXISTS product_code VARCHAR(100) NULL;

CREATE TABLE IF NOT EXISTS home_bundles (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(180) NOT NULL,
    description VARCHAR(500) NOT NULL DEFAULT '',
    product_codes TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    compare_at_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    image VARCHAR(512) NOT NULL DEFAULT '',
    link_url VARCHAR(512) NOT NULL DEFAULT 'products.php',
    active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS home_categories (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(100) NOT NULL,
    description VARCHAR(255) NOT NULL DEFAULT '',
    icon VARCHAR(60) NOT NULL DEFAULT 'grid',
    image VARCHAR(512) NOT NULL DEFAULT '',
    category VARCHAR(100) NOT NULL,
    subcategory VARCHAR(120) NOT NULL DEFAULT '',
    layout_class VARCHAR(80) NOT NULL DEFAULT '',
    active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS home_deal (
    id TINYINT UNSIGNED NOT NULL,
    product_code VARCHAR(100) NOT NULL,
    title VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    image VARCHAR(512) NOT NULL DEFAULT '',
    compare_at_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    ends_at DATETIME NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS home_demo_video (
    id TINYINT UNSIGNED NOT NULL,
    video_path VARCHAR(512) NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO home_demo_video(id,video_path) VALUES
(1,'videos/demo-video.mp4');

CREATE TABLE IF NOT EXISTS vouchers (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code VARCHAR(40) NOT NULL,
    label VARCHAR(180) NOT NULL,
    description VARCHAR(500) NOT NULL DEFAULT '',
    discount_type VARCHAR(20) NOT NULL,
    discount_value DECIMAL(10,2) NOT NULL,
    minimum_spend DECIMAL(10,2) NOT NULL DEFAULT 0,
    first_order_only TINYINT(1) NOT NULL DEFAULT 0,
    discount_cap DECIMAL(10,2) NULL,
    eligible_products LONGTEXT NULL,
    eligible_categories LONGTEXT NULL,
    required_product_sets LONGTEXT NULL,
    starts_at DATETIME NULL,
    ends_at DATETIME NULL,
    total_usage_limit INT UNSIGNED NULL,
    per_customer_limit INT UNSIGNED NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_vouchers_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO home_promos(id,title,description,button_label,image,image_alt,link_url,active,sort_order) VALUES
(1,'Ber Months Sale: Up to 50% OFF','Start the season early with savings across appliances, TVs, and smart-home essentials.','Shop the Sale','images/Promo_image.png','Seasonal savings across appliances, TVs, and smart-home essentials.','products.php',1,1),
(2,'Free Delivery on Appliances','Get free delivery on orders over ₱3,000, anywhere in the Philippines.','Shop Appliances','images/Delivery-Promo.png','Free delivery on orders over ₱3,000, anywhere in the Philippines.','products.php?cat=kitchen',1,2);

INSERT IGNORE INTO home_bundles(id,title,description,product_codes,price,compare_at_price,image,link_url,active,sort_order) VALUES
(1,'Smart Kitchen Set','Air fryer · Rice cooker · Blender','["airfryer","ricecook","blender"]',7499,8333,'images/afryer.jpg','products.php?cat=kitchen&sub=Cooking%20Appliances',1,1),
(2,'Gamer Setup','Computer monitor · Controller · Headphones','["cmon","controller","headphones"]',31990,34569,'images/computermonitor.jpg','products.php?cat=entertainment&sub=Gaming%20Gadgets',1,2),
(3,'Security Kit','CCTV camera · Video doorbell · Smoke detector','["cctv","doorbell","smoke"]',22900,24900,'images/cctv.jpg','products.php?cat=household&sub=Security%20Devices',1,3);

INSERT IGNORE INTO home_categories(id,title,description,icon,image,category,subcategory,layout_class,active,sort_order) VALUES
(1,'Smart Home','Lighting, hubs & automation','house','images/SmartHomeCategory.png','household','','wide tall',1,1),
(2,'Kitchen','Cook faster, live better','cup-hot','images/KitchenCategory.png','kitchen','','tall',1,2),
(3,'Gaming','Level up your setup','controller','images/GamingCategory.png','entertainment','Gaming Gadgets','tall',1,3),
(4,'Monitors','See every detail','display','images/MonitorsCategory.png','entertainment','Televisions & Displays','',1,4),
(5,'Audio','Sound worth feeling','speaker','images/AudioCategory.png','entertainment','Audio & Music','',1,5),
(6,'Security','Protect what matters','camera-video','images/SecurityCategory.png','household','Security Devices','wide',1,6);

INSERT IGNORE INTO home_deal(id,product_code,title,description,image,compare_at_price,active) VALUES
(1,'fridge','Samsung Side-by-Side Refrigerator','Spacious No-Frost storage, a built-in water and ice dispenser, and Digital Inverter efficiency—now at our best price this season.','images/ref.jpg',116000,1);

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    age TINYINT UNSIGNED NULL,
    sex VARCHAR(30) NULL,
    birthday DATE NULL,
    contact VARCHAR(20) NULL,
    address TEXT NULL,
    profile_image VARCHAR(255) NULL,
    role VARCHAR(30) NOT NULL DEFAULT 'customer',
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_wishlist (
    user_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, product_id),
    KEY idx_user_wishlist_product (product_id),
    CONSTRAINT fk_user_wishlist_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_user_wishlist_product
        FOREIGN KEY (product_id) REFERENCES products (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_addresses (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    label VARCHAR(40) NOT NULL DEFAULT 'Home',
    recipient_name VARCHAR(255) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    address_line VARCHAR(255) NOT NULL,
    barangay VARCHAR(120) NOT NULL,
    city VARCHAR(120) NOT NULL,
    province VARCHAR(120) NOT NULL,
    postal_code VARCHAR(20) NOT NULL,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_user_addresses_user (user_id),
    CONSTRAINT fk_user_addresses_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_number VARCHAR(40) NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    address TEXT NOT NULL,
    payment_method VARCHAR(30) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    shipping_fee DECIMAL(10, 2) NOT NULL DEFAULT 0,
    voucher_code VARCHAR(40) NULL,
    discount_amount DECIMAL(10, 2) NOT NULL DEFAULT 0,
    grand_total DECIMAL(10, 2) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'Pending',
    cancellation_reason VARCHAR(80) NULL,
    cancellation_details VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_orders_order_number (order_number),
    KEY idx_orders_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS voucher_code VARCHAR(40) NULL,
    ADD COLUMN IF NOT EXISTS discount_amount DECIMAL(10, 2) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS cancellation_reason VARCHAR(80) NULL,
    ADD COLUMN IF NOT EXISTS cancellation_details VARCHAR(500) NULL;

INSERT IGNORE INTO vouchers
    (code,label,description,discount_type,discount_value,minimum_spend,first_order_only,discount_cap,required_product_sets,active)
VALUES
    ('WELCOME300','₱300 off your first order','Minimum spend ₱2,500. First order only.','fixed',300,2500,1,NULL,NULL,1),
    ('HOME500','₱500 off','Minimum spend ₱5,000.','fixed',500,5000,0,NULL,NULL,1),
    ('BUNDLE10','10% off a featured bundle, up to ₱1,000','Add every item from any featured bundle to your cart. Discount applies to the bundle items only.','percent',10,0,0,1000,'[["airfryer","ricecook","blender"],["cmon","controller","headphones"],["cctv","doorbell","smoke"]]',1),
    ('SMART10','10% off your first order, up to ₱500','Minimum spend ₱2,000. First order only.','percent',10,2000,1,500,NULL,1);

CREATE TABLE IF NOT EXISTS order_items (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NULL,
    product_name VARCHAR(255) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    KEY idx_order_items_order_id (order_id),
    KEY idx_order_items_product_id (product_id),
    CONSTRAINT fk_order_items_order
        FOREIGN KEY (order_id) REFERENCES orders (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product
        FOREIGN KEY (product_id) REFERENCES products (id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_delivery_events (
    order_id INT UNSIGNED NOT NULL,
    delivered_at DATETIME NULL,
    completed_at DATETIME NULL,
    PRIMARY KEY (order_id),
    CONSTRAINT fk_order_delivery_events_order
        FOREIGN KEY (order_id) REFERENCES orders (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO order_delivery_events(order_id,delivered_at)
SELECT id,created_at FROM orders WHERE status='Delivered'
ON DUPLICATE KEY UPDATE delivered_at=COALESCE(delivered_at,VALUES(delivered_at));

CREATE TABLE IF NOT EXISTS product_reviews (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    order_item_id INT UNSIGNED NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    comment VARCHAR(1000) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_product_reviews_order_item (order_item_id),
    KEY idx_product_reviews_product (product_id,created_at),
    CONSTRAINT fk_product_reviews_product
        FOREIGN KEY (product_id) REFERENCES products (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_product_reviews_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_product_reviews_order_item
        FOREIGN KEY (order_item_id) REFERENCES order_items (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS refund_requests (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id INT UNSIGNED NOT NULL,
    order_item_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    reason VARCHAR(40) NOT NULL,
    details VARCHAR(1000) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'Pending',
    admin_note VARCHAR(1000) NULL,
    reviewed_by INT UNSIGNED NULL,
    requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_refund_requests_status (status,requested_at),
    KEY idx_refund_requests_user (user_id,requested_at),
    CONSTRAINT fk_refund_requests_order
        FOREIGN KEY (order_id) REFERENCES orders (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_refund_requests_order_item
        FOREIGN KEY (order_item_id) REFERENCES order_items (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_refund_requests_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_refund_requests_admin
        FOREIGN KEY (reviewed_by) REFERENCES users (id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS account_deletion_requests (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'Pending',
    admin_note VARCHAR(1000) NULL,
    requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_by INT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_account_deletion_requests_status (status,requested_at),
    KEY idx_account_deletion_requests_user (user_id,requested_at),
    CONSTRAINT fk_account_deletion_requests_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_account_deletion_requests_admin
        FOREIGN KEY (reviewed_by) REFERENCES users (id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS app_feedback (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    comment VARCHAR(1000) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_app_feedback_user (user_id),
    CONSTRAINT fk_app_feedback_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
