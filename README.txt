# SmartTech Appliances

A database-backed e-commerce website project for browsing and managing home appliances.

## Features

- Browse, search, and filter products
- Product detail pages with image galleries and videos
- Shopping cart, demo checkout, order history, and order tracking
- Customer accounts and wishlists
- Admin dashboard for managing products, stock, homepage content, media, and vouchers
- Homepage demo video that can be replaced through the admin panel

## Built With

- HTML, CSS, JavaScript, and Bootstrap
- PHP
- MariaDB / MySQL

## Run Locally

1. Install XAMPP or another environment with PHP and MariaDB/MySQL.
2. Copy or clone this repository into your web server's document root, such as `C:\xampp\htdocs\`.
3. Start **Apache** and **MySQL** from the XAMPP Control Panel.
4. Create a database named `smarttech` in phpMyAdmin.
5. Select the database and import `php/schema.sql`.
6. Configure the database connection in `php/database.php` to match your local MySQL host, port, database name, username, and password.
7. Open the storefront in your browser:

   `http://localhost/SmartTech-Appliances.github.io/`

## Access the Admin Panel

1. Make sure Apache and MySQL are running and the database has been set up.
2. Sign in using an administrator account configured in the `smarttech` database. The account must have the `admin` role.
3. Open the admin dashboard:

   `http://localhost/SmartTech-Appliances.github.io/admin.php`

   If you are not signed in as an administrator, the site will redirect you to the login page.

Do not publish administrator usernames or passwords in this README. Only create or use administrator accounts in a trusted local environment. Before putting the site online, secure or remove any development-only admin setup scripts and configure unique production credentials.

## Admin Storefront Management

The admin panel can manage products and their media, homepage promotions, bundles, category tiles, the Deal of the Month, vouchers, and the homepage demo video.

Product and storefront media is uploaded to `uploads/store/`. The homepage demo video is initially at `videos/demo-video.mp4`; admins can replace it from **Admin → Storefront → Homepage demo video**.

## Important Notes

- Checkout is a project demo and does not process real payments.
- This project requires PHP and MariaDB/MySQL; GitHub Pages alone cannot run it.
- `php/schema.sql` contains the database schema and initial content, not a backup of the live database or customer data.
- Never commit database passwords, production credentials, private keys, customer information, or database exports containing personal data.
- Before deploying publicly, configure secure production credentials, HTTPS, backups, and a real payment provider.
