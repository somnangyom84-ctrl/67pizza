<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/config.php';
require_login();
if (!is_post()) redirect(url('orders.php'));
verify_csrf();
$user = current_user();
$orderId = to_int($_POST['order_id'] ?? 0);
$productId = to_int($_POST['product_id'] ?? 0);
$rating = to_int($_POST['rating'] ?? 0);
$comment = clean_string($_POST['comment'] ?? '', 1000);
// Eligibility: completed order owned by user containing this product
$stmt = db()->prepare('SELECT o.id FROM orders o INNER JOIN order_items oi ON oi.order_id = o.id WHERE o.id = ? AND o.user_id = ? AND o.status = "completed" AND oi.product_id = ? LIMIT 1');
$stmt->execute([$orderId, $user['id'], $productId]);
if (!$stmt->fetch()) { flash('danger', 'You can only review items from your completed orders.'); redirect(url('orders.php')); }
if ($rating < 1 || $rating > 5) { flash('danger', 'Rating must be between 1 and 5.'); redirect(url('order-details.php?id=' . $orderId)); }
$dup = db()->prepare('SELECT id FROM reviews WHERE user_id = ? AND product_id = ? AND order_id = ?');
$dup->execute([$user['id'], $productId, $orderId]);
if ($dup->fetch()) { flash('warning', 'You already reviewed this item.'); redirect(url('order-details.php?id=' . $orderId)); }
db()->prepare('INSERT INTO reviews (user_id, product_id, order_id, rating, comment, status) VALUES (?,?,?,?,?,"pending")')->execute([$user['id'], $productId, $orderId, $rating, $comment ?: null]);
flash('success', 'Thanks for your review! It will appear after moderation.');
redirect(url('order-details.php?id=' . $orderId));
