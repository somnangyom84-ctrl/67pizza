<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/config.php';
require_login();
if (!is_post()) redirect(url('checkout.php'));
verify_csrf();
if (is_admin()) { flash('warning', 'Admin accounts cannot place orders.'); redirect(url('admin/index.php')); }
$user = current_user();
$data = [
    'customer_name' => clean_string($_POST['customer_name'] ?? '', 120),
    'customer_phone' => clean_string($_POST['customer_phone'] ?? '', 30),
    'delivery_address' => clean_string($_POST['delivery_address'] ?? '', 255),
    'delivery_instructions' => clean_string($_POST['delivery_instructions'] ?? '', 255),
    'order_notes' => clean_string($_POST['order_notes'] ?? '', 500),
    'payment_method' => clean_string($_POST['payment_method'] ?? '', 20),
];
$allowedMethods = [];
if (setting('cod_enabled', '1') === '1') $allowedMethods[] = 'cod';
if (setting('manual_payment_enabled', '0') === '1') $allowedMethods[] = 'manual_transfer';
$v = new Validator($data);
$ok = $v->validate([
    'customer_name' => 'required|min:2|max:120',
    'customer_phone' => 'required|phone',
    'delivery_address' => 'required|min:5|max:255',
    'payment_method' => 'required|in:' . implode(',', $allowedMethods ?: ['none']),
], ['payment_method' => 'Payment method']);
if (!$ok) {
    set_form_errors($v->firstErrors());
    remember_old($data);
    flash('danger', 'Please check the highlighted fields.');
    redirect(url('checkout.php'));
}
$result = create_order((int) $user['id'], $data, $_SESSION['coupon_code'] ?? null);
if (!$result['success']) {
    remember_old($data);
    flash('danger', $result['message']);
    redirect(url('checkout.php'));
}
// Save address for next time if the profile has none
if (empty($user['address']) || empty($user['phone'])) {
    db()->prepare('UPDATE users SET phone = COALESCE(NULLIF(phone,""), ?), address = COALESCE(NULLIF(address,""), ?) WHERE id = ?')->execute([$data['customer_phone'], $data['delivery_address'], $user['id']]);
}
flash('success', 'Order #' . $result['order_number'] . ' placed successfully! We will confirm it shortly.');
redirect(url('order-details.php?id=' . $result['order_id'] . '&placed=1'));
