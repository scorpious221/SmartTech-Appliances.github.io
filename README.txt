SMARTTECH ADMIN + STOCK UPGRADE

NEW FILES
- admin-login.html
- admin.html
- admin.css
- admin.js
- catalog-store.js

REPLACE EXISTING FILES
- index.html
- main.js
- products.html
- shop.js
- cart.html
- wishlist.html
- track.html
- search.js

KEEP YOUR EXISTING FILES
- products-data.js
- products.js
- styles.css
- shop.css
- pages.css
- layout.js
- profile.html
- receipt.html
- login.html
- about.html
- help.html
- image folder and all product images

ADMIN LOGIN
Username: admin
Password: admin123
Open: admin-login.html

IMPORTANT
This is still a front-end/localStorage school/demo system. Admin authentication and data are stored in the browser and are not secure like a real server/database setup.

STOCK RULES
10+ = In Stock
1-9 = Low Stock
0 = Out of Stock

When an order is placed, stock is deducted automatically. Orders are saved to st_orders. Admin can change status from Pending -> Processing -> Shipped -> Delivered. Track Order reads that real status.

PRODUCT SALE AND BESTSELLER LABELS
- In the PHP admin dashboard, edit a product to toggle Bestseller or On Sale.
- On Sale requires a sale price lower than the regular price. The sale price is used in the storefront, cart, voucher calculation, and checkout.
- If your database already exists, apply the products table column updates from php/schema.sql before using these controls.
