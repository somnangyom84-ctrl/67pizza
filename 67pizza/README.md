# 67pizza – Food Ordering Platform (PHP 8 + MySQL, XAMPP)

A complete, responsive food-ordering website with a customer storefront and a separate administrator dashboard.
Built with plain **PHP 8 (PDO)**, **MySQL**, **HTML5/CSS3/JavaScript** and **Bootstrap 5** – no Node.js, Composer or frameworks required.

Brand colours: black `#0d0d0f` background, charcoal `#1b1b1f` cards, yellow `#FFD629` accents. Currency: Cambodian riel (KHR).

---

## 1. Requirements

| Component | Version |
|-----------|---------|
| XAMPP     | 8.2.12 (Apache + MySQL/MariaDB + phpMyAdmin) |
| PHP       | 8.1+ (8.2 bundled with XAMPP 8.2.12) – `pdo_mysql`, `fileinfo`, `mbstring` enabled (default in XAMPP) |
| Browser   | Any modern browser |

Bootstrap, Bootstrap Icons, Google Fonts and Chart.js load from CDNs. If you are offline, pages still render (the admin charts fall back to tables).

---

## 2. Installation (Windows + XAMPP)

1. Install XAMPP and open the **XAMPP Control Panel**.
2. Start **Apache** and **MySQL**.
3. Create the folder `C:\xampp\htdocs\67pizza\`.
4. Copy **all project files** into that folder, so that `C:\xampp\htdocs\67pizza\index.php` exists.
5. Open `http://localhost/phpmyadmin/`.
6. Click **Import** → choose `database/67pizza_db.sql` → **Go**.
   The script creates the `67pizza_db` database, all tables, indexes and sample data (categories, products, restaurants, coupons, banners, settings).
7. Check the MySQL connection in `config/database.php`:
   ```php
   define('DB_HOST', '127.0.0.1');
   define('DB_NAME', '67pizza_db');
   define('DB_USER', 'root');
   define('DB_PASS', '');        // set your MySQL password here if you use one
   ```
8. **Create the first administrator** (one-time, secure setup):
   1. Open `config/config.php` and change `ADMIN_SETUP_KEY` to a secret of your own.
   2. Visit `http://localhost/67pizza/admin/setup-admin.php`, enter the setup key, name, email and a password (min. 8 chars, letters + numbers).
   3. After success, set `ADMIN_SETUP_ENABLED` to `false` in `config/config.php` (or delete `admin/setup-admin.php`).
      The script also refuses to run once an admin already exists.
9. Open the website: `http://localhost/67pizza/`
10. Open the admin dashboard: `http://localhost/67pizza/admin/`

> If your project is in a different folder name, update `BASE_URL` in `config/config.php` (e.g. `/myshop`).

---

## 3. Project structure

```
67pizza/
├── admin/                 Admin dashboard (login, products, categories, orders, customers,
│   ├── includes/          coupons, reviews, restaurants, banners, messages, reports, settings)
│   └── setup-admin.php    One-time secure admin creation script
├── actions/               POST handlers (login, register, logout, cart, checkout, contact,
│                          favorite, review, order-cancel, profile, forgot-password, newsletter)
├── assets/
│   ├── css/  style.css, admin.css
│   ├── js/   app.js, admin.js
│   ├── images/            Sample product/banner photos + placeholder.svg fallback
│   └── uploads/           Admin uploads (products/, banners/, settings/) – script execution disabled
├── config/   config.php (app settings, secure session), database.php (PDO)
├── database/ 67pizza_db.sql
├── includes/ header, navbar, footer, product-card, functions, auth, csrf, validation
├── index.php, menu.php, category.php, product.php, restaurants.php, restaurant.php, search.php,
│   about.php, contact.php, login.php, register.php, forgot-password.php, reset-password.php,
│   cart.php, checkout.php, profile.php, orders.php, order-details.php, favorites.php, notifications.php
├── .htaccess
└── README.md
```

---

## 4. Features

**Customers** – register/login/logout, profile & password change, browse categories, search & filter, product details with reviews, cart (session for guests, database for logged-in users, merged on login), favorites, coupons (validated server-side), checkout with COD or manual transfer, order history with status timeline, order cancellation (pending/confirmed only), reviews for completed orders (moderated), in-site notifications, contact form.

**Admin** – dashboard statistics & charts, product CRUD with image upload, categories, orders with enforced status transitions (`pending → confirmed → preparing → ready_for_delivery → delivering → completed`, cancel allowed before delivering), payment records, customers (activate/deactivate/role), coupons, review moderation, restaurants, hero/offer banners, contact messages, reports by date range, site settings (delivery fee, free-delivery threshold, minimum order, payment methods, contact info, payment QR image).

**Payments** – Cash on Delivery and an optional, clearly labelled *manual bank transfer* that staff verify by hand. No online gateway (ABA/KHQR/Visa) is integrated and the site never claims a payment is complete automatically. No banking credentials are ever collected.

---

## 5. Security measures

- PDO prepared statements everywhere; output escaped with `htmlspecialchars()`.
- `password_hash()` / `password_verify()`; passwords never stored in plain text.
- CSRF tokens on every state-changing form and AJAX call.
- Session ID regeneration on login, `HttpOnly` + `SameSite=Lax` cookies, strict mode.
- Login throttling: 5 failed attempts per email/IP → 15-minute lock (also applied to password-reset requests).
- Server-side validation on all forms; ownership checks on orders/favorites/notifications; admin role enforced server-side.
- Uploads: MIME (finfo) + extension + size + `getimagesize()` checks, random filenames, PHP execution disabled in `assets/uploads/`.
- `config/`, `includes/`, `database/` and `.sql` files blocked by `.htaccess`.
- Orders, status changes and payments are written inside database transactions; order items keep the product name and unit price at purchase time.

---

## 6. Sample data & test coupons

Categories: Burgers, Pizza, Pasta, Fries, Drinks, Desserts.
Products: Classic Burger (12,000 ៛), Cheese Burger (14,000 ៛), Chicken Tikka Pizza (28,000 ៛), Pepperoni Pizza (26,000 ៛), Creamy Pasta (18,000 ៛), Masala Fries (8,000 ៛), Iced Coffee (7,000 ៛), Chocolate Lava Cake (12,000 ៛).
Coupons: `WELCOME20` (20 % off first order, min 20,000 ៛, max 20,000 ៛ discount) and `PIZZA5K` (5,000 ៛ off orders over 40,000 ៛).
No customer accounts are seeded – register one at `/register.php`.

---

## 7. Password reset in local development

There is no mail server in XAMPP. With `APP_ENV = 'local'`, the reset link is displayed on screen after you submit the forgot-password form (`DEV_SHOW_RESET_LINK`). In production set `APP_ENV` to `production` and configure PHP `mail()`/SMTP.

---

## 8. Troubleshooting

| Problem | Fix |
|---------|-----|
| *Database connection failed* | MySQL not running, wrong credentials in `config/database.php`, or the SQL file was not imported. |
| `#1046 No database selected` on import | The SQL file creates the database itself – import it from the phpMyAdmin home page (not from inside another database). |
| Page shows raw PHP code | You opened the file via `file://` – always use `http://localhost/67pizza/`. |
| 404 / broken CSS | Folder name does not match `BASE_URL` in `config/config.php`. |
| Image upload fails | Ensure `assets/uploads/*` is writable and `upload_max_filesize` ≥ 2M in `php.ini` (restart Apache). |
| 500 error in `assets/uploads` | Remove the `php_flag` block in `assets/uploads/.htaccess` if PHP runs as CGI/FastCGI. |
| Admin setup says disabled | An admin already exists or `ADMIN_SETUP_ENABLED` is `false`. Use *Customers → Make admin* to add more admins. |
| Locked out after failed logins | Wait 15 minutes or run `TRUNCATE login_attempts;` in phpMyAdmin. |
| Charts are empty | Chart.js is loaded from a CDN; offline the dashboard falls back to tables. |

---

## 9. Manual test checklist

1. Import SQL → open homepage → categories, popular items, banners and restaurants render from the DB.
2. Register a customer → log in → add items to cart (guest cart merges on login).
3. Apply `WELCOME20` → totals update server-side → checkout with COD → order appears in *My Orders* with status *Pending* and a notification is created.
4. Admin → Orders → confirm → preparing → ready → delivering → completed. Customer timeline updates; COD payment becomes *Paid*.
5. Customer reviews an item from the completed order → Admin → Reviews → approve → review appears on the product page and homepage.
6. Admin → Products → add/edit/delete with image upload; Categories cannot be deleted while they contain products.
7. Contact form → message appears in Admin → Messages.
8. Try to open `/admin/` as a customer → redirected to admin login. Try another user's `order-details.php?id=…` → *Order not found*.

> Note: the project was authored in an environment without PHP/MySQL available, so it could not be executed there. Please run the checklist above on your XAMPP installation and report any error message – each page is self-contained and easy to debug.
