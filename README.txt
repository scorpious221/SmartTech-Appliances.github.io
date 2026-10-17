# SmartTech Appliances

A PHP and MariaDB-backed e-commerce website prototype for browsing and managing home appliances.

## Features

### Customer storefront
- Browse, search, and filter products by category
- View product details, image galleries, and product videos
- Add products to a wishlist or shopping cart
- Register and sign in to a customer account
- Complete a demo checkout and view order history
- Track order status
- Use vouchers that meet their discount and eligibility requirements
- Submit product reviews after eligible purchases and access customer account features

### Admin panel
- View a dashboard with revenue, order, customer, product, and stock summaries
- Add, edit, and delete products
- Manage product stock
- Replace product images and manage additional product images and videos
- Manage homepage promotions, featured bundles, category tiles, and the Deal of the Month
- Replace the homepage demo video
- Create and manage checkout vouchers and their eligibility rules
- Review and manage orders, returns/refunds, and account requests

### Storefront experience
- Responsive desktop and mobile layouts
- Homepage demo video opened by the **Watch Demo** button
- Product carousel with previous/next controls and aligned product cards

## Built With

- HTML, CSS, and JavaScript
- Bootstrap and Bootstrap Icons
- PHP
- MariaDB / MySQL

## Run Locally

1. Install [XAMPP](https://www.apachefriends.org/) or another environment that supports PHP and MariaDB/MySQL.
2. Copy or clone the project into your web server's document root. For XAMPP, this is usually `C:\xampp\htdocs\`.
3. Start **Apache** and **MySQL** from the XAMPP Control Panel.
4. In phpMyAdmin, create a database named `smarttech`.
5. Select the `smarttech` database and import `php/schema.sql`. This creates the required tables and inserts the project's initial content.
6. Check `php/database.php` and configure the host, port, database name, username, and password for your local database.
7. If you plan to upload product or storefront media, make sure PHP/Apache can write to the `uploads/store/` directory.
8. Open the storefront in your browser:

   `http://localhost/SmartTech-Appliances.github.io/`

## Access the Admin Panel

1. Make sure Apache and MySQL are running and the database has been set up.
2. Sign in with an administrator account in the `smarttech` database. The account must have the `admin` role.
3. Open:

   `http://localhost/SmartTech-Appliances.github.io/admin.php`

If you are not signed in as an administrator, the site redirects you to the login page. Admin changes require an authenticated admin session.

Do not publish administrator passwords in this README or commit them to the repository. Use only trusted local credentials during development, and configure unique, secure credentials before deployment.

## Media Uploads

- The homepage demo video is initially stored at `videos/demo-video.mp4`.
- Replace it in **Admin → Storefront → Homepage demo video**.
- Product images, galleries, and videos are managed in **Admin → Products**.
- Supported images: JPG, PNG, WebP, and GIF, up to 8 MB each.
- Supported videos: MP4, WebM, and OGG, up to 30 MB each.
- Uploaded media is stored under `uploads/store/`.

## Database and Data

`php/schema.sql` contains the database schema and initial/sample storefront and voucher data. It is **not** an export of the local database and does not contain local customer orders or account data.

The local database itself is not included when pushing this repository. Back up databases securely, and never commit database exports containing customer or other private information.

## Important Notes

- Checkout is a project demo; it does not process real payments.
- This project needs PHP and MariaDB/MySQL. GitHub Pages alone cannot run it.
- Before making the site public, configure secure production database credentials, HTTPS, backups, and a real payment provider.
- Do not commit passwords, private keys, production credentials, customer data, or private database backups.
