<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/config.php';
if (!is_post()) redirect(url('cart.php'));
verify_csrf();
if (is_admin()) {
    $r = ['success' => false, 'message' => 'Admin accounts cannot place orders. Please use a customer account.'];
    if (is_ajax()) json_response($r, 403);
    flash('warning', $r['message']);
    redirect(url('cart.php'));
}
$action = $_POST['action'] ?? '';
$pid = to_int($_POST['product_id'] ?? 0);
$qty = to_int($_POST['quantity'] ?? 1, 1);

switch ($action) {
    case 'add':
        $r = cart_add($pid, $qty);
        break;
    case 'update':
        $r = cart_update($pid, $qty);
        break;
    case 'remove':
        $r = cart_remove($pid);
        break;
    case 'clear':
        cart_clear();
        $r = ['success' => true, 'message' => 'Cart cleared.'];
        break;
    case 'apply_coupon':
        $code = strtoupper(clean_string($_POST['coupon_code'] ?? '', 40));
        $items = cart_items();
        $subtotal = cart_totals($items)['subtotal'];
        $v = validate_coupon($code, $subtotal, cart_user_id());
        if ($v['valid']) {
            $_SESSION['coupon_code'] = $v['coupon']['code'];
            $r = ['success' => true, 'message' => $v['message']];
        } else {
            unset($_SESSION['coupon_code']);
            $r = ['success' => false, 'message' => $v['message']];
        }
        break;
    case 'remove_coupon':
        unset($_SESSION['coupon_code']);
        $r = ['success' => true, 'message' => 'Coupon removed.'];
        break;
    default:
        $r = ['success' => false, 'message' => 'Unknown cart action.'];
}
$r['cart_count'] = cart_count();
if (is_ajax()) json_response($r, $r['success'] ? 200 : 422);
flash($r['success'] ? 'success' : 'danger', $r['message']);
redirect($action === 'add' && !empty($_POST['stay']) ? ($_SERVER['HTTP_REFERER'] ?? url('cart.php')) : url('cart.php'));
