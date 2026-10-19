<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/config.php';
require_login();
if (!is_post()) redirect(url('orders.php'));
verify_csrf();
$user = current_user();
$id = to_int($_POST['order_id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ? LIMIT 1');
$stmt->execute([$id, $user['id']]);
$order = $stmt->fetch();
if (!$order) { flash('danger', 'Order not found.'); redirect(url('orders.php')); }
if (!customer_can_cancel($order)) { flash('warning', 'This order can no longer be cancelled. Please contact us.'); redirect(url('order-details.php?id=' . $id)); }
$r = update_order_status($id, 'cancelled', 'Cancelled by customer');
flash($r['success'] ? 'success' : 'danger', $r['success'] ? 'Your order has been cancelled.' : $r['message']);
redirect(url('order-details.php?id=' . $id));
