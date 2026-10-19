-- =====================================================================
-- 67pizza - Food Ordering Platform
-- MySQL schema + seed data  (import via phpMyAdmin or mysql CLI)
-- Compatible with XAMPP 8.2.x (MySQL / MariaDB 10.4+)
-- =====================================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `67pizza_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `67pizza_db`;

DROP TABLE IF EXISTS `newsletter_subscribers`, `password_resets`, `login_attempts`, `settings`, `contact_messages`,
  `banners`, `restaurants`, `notifications`, `reviews`, `coupons`, `payments`, `order_items`, `orders`,
  `favorites`, `cart_items`, `products`, `categories`, `users`;

-- ---------------------------------------------------------------------
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(190) NOT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `address` VARCHAR(255) DEFAULT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('customer','admin') NOT NULL DEFAULT 'customer',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_login_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(120) NOT NULL,
  `icon` VARCHAR(60) DEFAULT 'bi-egg-fried',
  `description` VARCHAR(255) DEFAULT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categories_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `restaurants` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `slug` VARCHAR(140) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `rating` DECIMAL(2,1) NOT NULL DEFAULT 0.0,
  `rating_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `delivery_time` VARCHAR(40) DEFAULT '25-35 min',
  `distance_km` DECIMAL(4,1) DEFAULT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_restaurants_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `products` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED NOT NULL,
  `restaurant_id` INT UNSIGNED DEFAULT NULL,
  `name` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(170) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `image` VARCHAR(255) DEFAULT NULL,
  `is_available` TINYINT(1) NOT NULL DEFAULT 1,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `sold_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_products_slug` (`slug`),
  KEY `idx_products_category` (`category_id`),
  KEY `idx_products_restaurant` (`restaurant_id`),
  KEY `idx_products_flags` (`is_available`,`is_featured`),
  FULLTEXT KEY `ft_products` (`name`,`description`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_products_restaurant` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cart_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cart_user_product` (`user_id`,`product_id`),
  CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cart_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `favorites` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fav_user_product` (`user_id`,`product_id`),
  CONSTRAINT `fk_fav_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fav_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `coupons` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(40) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `type` ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
  `value` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `min_order` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `max_discount` DECIMAL(12,2) DEFAULT NULL,
  `usage_limit` INT UNSIGNED DEFAULT NULL,
  `used_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `per_user_limit` INT UNSIGNED DEFAULT NULL,
  `first_order_only` TINYINT(1) NOT NULL DEFAULT 0,
  `starts_at` DATETIME DEFAULT NULL,
  `expires_at` DATETIME DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_coupons_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `orders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_number` VARCHAR(30) NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `coupon_id` INT UNSIGNED DEFAULT NULL,
  `coupon_code` VARCHAR(40) DEFAULT NULL,
  `customer_name` VARCHAR(120) NOT NULL,
  `customer_phone` VARCHAR(30) NOT NULL,
  `delivery_address` VARCHAR(255) NOT NULL,
  `delivery_instructions` VARCHAR(255) DEFAULT NULL,
  `order_notes` TEXT DEFAULT NULL,
  `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `discount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `delivery_fee` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `payment_method` ENUM('cod','manual_transfer') NOT NULL DEFAULT 'cod',
  `payment_status` ENUM('unpaid','paid','refunded') NOT NULL DEFAULT 'unpaid',
  `status` ENUM('pending','confirmed','preparing','ready_for_delivery','delivering','completed','cancelled') NOT NULL DEFAULT 'pending',
  `cancel_reason` VARCHAR(255) DEFAULT NULL,
  `confirmed_at` DATETIME DEFAULT NULL,
  `completed_at` DATETIME DEFAULT NULL,
  `cancelled_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_orders_number` (`order_number`),
  KEY `idx_orders_user` (`user_id`),
  KEY `idx_orders_status` (`status`),
  KEY `idx_orders_created` (`created_at`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_orders_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `order_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED DEFAULT NULL,
  `product_name` VARCHAR(150) NOT NULL,
  `product_image` VARCHAR(255) DEFAULT NULL,
  `unit_price` DECIMAL(12,2) NOT NULL,
  `quantity` INT UNSIGNED NOT NULL,
  `line_total` DECIMAL(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_order_items_order` (`order_id`),
  KEY `idx_order_items_product` (`product_id`),
  CONSTRAINT `fk_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_items_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `payments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `method` ENUM('cod','manual_transfer') NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `status` ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  `reference` VARCHAR(120) DEFAULT NULL,
  `notes` VARCHAR(255) DEFAULT NULL,
  `paid_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_payments_order` (`order_id`),
  CONSTRAINT `fk_payments_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `reviews` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `order_id` INT UNSIGNED DEFAULT NULL,
  `rating` TINYINT UNSIGNED NOT NULL,
  `comment` TEXT DEFAULT NULL,
  `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_review_user_product_order` (`user_id`,`product_id`,`order_id`),
  KEY `idx_reviews_product` (`product_id`,`status`),
  CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reviews_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reviews_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `notifications` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `message` VARCHAR(500) NOT NULL,
  `link` VARCHAR(255) DEFAULT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notifications_user` (`user_id`,`is_read`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `banners` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(150) NOT NULL,
  `subtitle` VARCHAR(255) DEFAULT NULL,
  `highlight` VARCHAR(100) DEFAULT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `button_text` VARCHAR(60) DEFAULT 'Order Now',
  `button_link` VARCHAR(255) DEFAULT 'menu.php',
  `position` ENUM('hero','offer') NOT NULL DEFAULT 'hero',
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `contact_messages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(190) NOT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `subject` VARCHAR(150) DEFAULT NULL,
  `message` TEXT NOT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `settings` (
  `setting_key` VARCHAR(80) NOT NULL,
  `setting_value` TEXT DEFAULT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `login_attempts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `identifier` VARCHAR(190) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `attempted_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_attempts` (`identifier`,`ip_address`,`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_resets` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `token_hash` CHAR(64) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reset_token` (`token_hash`),
  CONSTRAINT `fk_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `newsletter_subscribers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(190) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_newsletter_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- SEED DATA
-- =====================================================================
INSERT INTO `categories` (`id`,`name`,`slug`,`icon`,`description`,`sort_order`) VALUES
(1,'Burgers','burgers','bi-shop','Juicy flame-grilled burgers',1),
(2,'Pizza','pizza','bi-pie-chart','Hand-tossed, wood-fired pizzas',2),
(3,'Pasta','pasta','bi-egg-fried','Creamy & saucy Italian pasta',3),
(4,'Fries','fries','bi-basket','Crispy golden fries & sides',4),
(5,'Drinks','drinks','bi-cup-straw','Cold drinks, coffee & shakes',5),
(6,'Desserts','desserts','bi-cake2','Sweet treats to finish',6);

INSERT INTO `restaurants` (`id`,`name`,`slug`,`description`,`image`,`rating`,`rating_count`,`delivery_time`,`distance_km`,`phone`,`is_featured`) VALUES
(1,'Burger Hub','burger-hub','Flame-grilled burgers made with 100% fresh beef and house-baked buns.','assets/images/classic-burger.jpg',4.5,1200,'20-30 min',0.5,'0967505304',1),
(2,'Pizza Planet','pizza-planet','Wood-fired pizzas with hand-stretched dough and premium toppings.','assets/images/pepperoni-pizza.jpg',4.6,850,'25-35 min',1.2,'0967505304',1),
(3,'Pasta House','pasta-house','Authentic Italian pasta cooked fresh to order.','assets/images/creamy-pasta.jpg',4.4,620,'30-40 min',2.0,'0967505304',1),
(4,'Chai & More','chai-and-more','Iced coffee, teas, shakes and quick bites.','assets/images/iced-coffee.jpg',4.3,430,'15-25 min',2.5,'0967505304',1);

INSERT INTO `products` (`id`,`category_id`,`restaurant_id`,`name`,`slug`,`description`,`price`,`image`,`is_available`,`is_featured`,`sold_count`) VALUES
(1,1,1,'Classic Burger','classic-burger','Flame-grilled beef patty with crisp lettuce, tomato, pickles and our signature sauce in a toasted sesame bun.',12000.00,'assets/images/classic-burger.jpg',1,1,320),
(2,1,1,'Cheese Burger','cheese-burger','Double beef patty with melted cheddar, caramelized onions and smoky mayo.',14000.00,'assets/images/cheese-burger.jpg',1,1,280),
(3,2,2,'Chicken Tikka Pizza','chicken-tikka-pizza','Spiced chicken tikka, onions, green peppers and mozzarella on a wood-fired crust.',28000.00,'assets/images/chicken-tikka-pizza.jpg',1,1,410),
(4,2,2,'Pepperoni Pizza','pepperoni-pizza','Classic pepperoni with bubbling mozzarella and rich tomato sauce.',26000.00,'assets/images/pepperoni-pizza.jpg',1,1,365),
(5,3,3,'Creamy Pasta','creamy-pasta','Fettuccine tossed in a creamy parmesan alfredo sauce with grilled chicken.',18000.00,'assets/images/creamy-pasta.jpg',1,1,190),
(6,4,1,'Masala Fries','masala-fries','Golden crispy fries dusted with our secret masala spice mix.',8000.00,'assets/images/masala-fries.jpg',1,1,520),
(7,5,4,'Iced Coffee','iced-coffee','Smooth cold-brewed coffee with fresh milk over ice.',7000.00,'assets/images/iced-coffee.jpg',1,1,610),
(8,6,4,'Chocolate Lava Cake','chocolate-lava-cake','Warm chocolate cake with a molten center, served with vanilla cream.',12000.00,'assets/images/chocolate-lava-cake.jpg',1,1,240);

INSERT INTO `coupons` (`code`,`description`,`type`,`value`,`min_order`,`max_discount`,`usage_limit`,`per_user_limit`,`first_order_only`,`expires_at`,`is_active`) VALUES
('WELCOME20','20% off your first order','percent',20.00,20000.00,20000.00,NULL,1,1,DATE_ADD(NOW(), INTERVAL 1 YEAR),1),
('PIZZA5K','5,000 KHR off orders over 40,000 KHR','fixed',5000.00,40000.00,NULL,500,NULL,0,DATE_ADD(NOW(), INTERVAL 6 MONTH),1);

INSERT INTO `banners` (`title`,`subtitle`,`highlight`,`image`,`button_text`,`button_link`,`position`,`sort_order`) VALUES
('Delicious Food Delivered Fresh','Hot • Fresh • On Time','Delicious Food','assets/images/hero.jpg','Order Now','menu.php','hero',1),
('Get 20% OFF','On Your First Order with code WELCOME20','Special Offer','assets/images/offer.jpg','Order Now','menu.php','offer',1);

INSERT INTO `settings` (`setting_key`,`setting_value`) VALUES
('site_name','67pizza'),
('site_tagline','Good Food, Happy Mood'),
('currency_code','KHR'),
('currency_symbol','៛'),
('delivery_fee','4000'),
('free_delivery_over','60000'),
('min_order_amount','10000'),
('contact_phone','0967505304'),
('contact_email','somnangyom84@gmail.com'),
('telegram_username','yomsomnang'),
('facebook_url','https://www.facebook.com/share/19VTbRJJao/?mibextid=wwXIfr'),
('opening_hours','Daily 9:00 AM – 10:00 PM'),
('cod_enabled','1'),
('manual_payment_enabled','1'),
('manual_payment_label','Manual Bank Transfer (ABA) – verified by staff'),
('manual_payment_instructions','Transfer the order total to our ABA account and send the receipt to Telegram @yomsomnang. Your order will be confirmed after our staff verifies the payment manually.'),
('manual_payment_qr',''),
('allow_guest_cart','1'),
('about_text','67pizza started with a simple idea: deliver hot, fresh, delicious food fast. From flame-grilled burgers to wood-fired pizzas, everything is made to order.');

SET FOREIGN_KEY_CHECKS = 1;
