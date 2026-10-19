<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
require_login();
if (is_admin()) { flash('warning', 'Admin accounts cannot place orders.'); redirect(url('admin/index.php')); }
$pageTitle = 'Checkout';
$user = current_user();
$items = cart_items();
if (!$items) { flash('warning', 'Your cart is empty.'); redirect(url('cart.php')); }
$coupon = null;
if (!empty($_SESSION['coupon_code'])) {
    $v = validate_coupon($_SESSION['coupon_code'], cart_totals($items)['subtotal'], (int) $user['id']);
    if ($v['valid']) { $coupon = $v['coupon']; } else { flash('warning', $v['message']); unset($_SESSION['coupon_code']); }
}
$totals = cart_totals($items, $coupon);
$minOrder = (float) setting('min_order_amount', '0');
if ($minOrder > 0 && $totals['subtotal'] < $minOrder) { flash('warning', 'Minimum order is ' . format_price($minOrder) . '.'); redirect(url('cart.php')); }
$codEnabled = setting('cod_enabled', '1') === '1';
$manualEnabled = setting('manual_payment_enabled', '0') === '1';
$errors = form_errors();
require ROOT_PATH . '/includes/header.php';
?>
<div class="container">
  <h1 class="page-title"><i class="bi bi-credit-card-2-front"></i> Checkout</h1>
  <form method="post" action="<?= url('actions/checkout.php') ?>" class="needs-validation" novalidate>
    <?= csrf_field() ?>
    <div class="row g-4">
      <div class="col-lg-7">
        <div class="card-dark p-4 mb-4">
          <h5 class="mb-3"><i class="bi bi-geo-alt text-brand"></i> Delivery details</h5>
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Full name *</label><input name="customer_name" class="form-control <?= isset($errors['customer_name']) ? 'is-invalid' : '' ?>" value="<?= e(old('customer_name', $user['name'])) ?>" required><?php if (isset($errors['customer_name'])): ?><div class="invalid-feedback"><?= e($errors['customer_name']) ?></div><?php endif; ?></div>
            <div class="col-md-6"><label class="form-label">Phone number *</label><input name="customer_phone" class="form-control <?= isset($errors['customer_phone']) ? 'is-invalid' : '' ?>" value="<?= e(old('customer_phone', $user['phone'] ?? '')) ?>" required><?php if (isset($errors['customer_phone'])): ?><div class="invalid-feedback"><?= e($errors['customer_phone']) ?></div><?php endif; ?></div>
            <div class="col-12"><label class="form-label">Delivery address *</label><input name="delivery_address" class="form-control <?= isset($errors['delivery_address']) ? 'is-invalid' : '' ?>" value="<?= e(old('delivery_address', $user['address'] ?? '')) ?>" required placeholder="House no., street, Sangkat, Khan, City"><?php if (isset($errors['delivery_address'])): ?><div class="invalid-feedback"><?= e($errors['delivery_address']) ?></div><?php endif; ?></div>
            <div class="col-md-6"><label class="form-label">Delivery instructions</label><input name="delivery_instructions" class="form-control" value="<?= e(old('delivery_instructions')) ?>" placeholder="e.g. Call when you arrive" maxlength="255"></div>
            <div class="col-md-6"><label class="form-label">Order notes</label><input name="order_notes" class="form-control" value="<?= e(old('order_notes')) ?>" placeholder="e.g. No onions, extra spicy" maxlength="500"></div>
          </div>
        </div>
        <div class="card-dark p-4">
          <h5 class="mb-3"><i class="bi bi-wallet2 text-brand"></i> Payment method</h5>
          <?php if (isset($errors['payment_method'])): ?><div class="text-danger small mb-2"><?= e($errors['payment_method']) ?></div><?php endif; ?>
          <div class="d-grid gap-2">
            <?php if ($codEnabled): ?>
            <label class="payment-option"><input type="radio" name="payment_method" value="cod" class="form-check-input me-2" <?= old('payment_method', 'cod') === 'cod' ? 'checked' : '' ?> required><strong>Cash on Delivery (COD)</strong><div class="text-muted small ms-4">Pay the rider in cash when your food arrives.</div></label>
            <?php endif; ?>
            <?php if ($manualEnabled): ?>
            <label class="payment-option"><input type="radio" name="payment_method" value="manual_transfer" class="form-check-input me-2" <?= old('payment_method') === 'manual_transfer' ? 'checked' : '' ?> required><strong><?= e(setting('manual_payment_label', 'Manual Bank Transfer')) ?></strong><div class="text-muted small ms-4">Manual transfer – our staff verify the payment before confirming the order. This is not an automatic online payment.</div></label>
            <div class="alert alert-info small <?= old('payment_method') === 'manual_transfer' ? '' : 'd-none' ?>" data-payment-info="manual_transfer">
              <?= nl2br(e(setting('manual_payment_instructions'))) ?>
              <?php if (setting('manual_payment_qr')): ?><div class="mt-2"><img class="img-qr" src="<?= e(image_url(setting('manual_payment_qr'))) ?>" alt="Payment QR"></div><?php endif; ?>
              <div class="mt-2 fw-semibold">Never share your banking password or PIN with anyone. We only need the transfer receipt.</div>
            </div>
            <?php endif; ?>
            <?php if (!$codEnabled && !$manualEnabled): ?><div class="alert alert-warning">No payment methods are currently available. Please contact us.</div><?php endif; ?>
          </div>
        </div>
      </div>
      <div class="col-lg-5">
        <div class="card-dark p-4 sticky-top" style="top:90px">
          <h5 class="mb-3">Your order</h5>
          <?php foreach ($items as $it): ?>
          <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-secondary-subtle">
            <div class="d-flex align-items-center gap-2"><img src="<?= e(image_url($it['image'])) ?>" alt="" width="46" height="46" style="object-fit:cover;border-radius:10px" onerror="this.onerror=null;this.src='<?= placeholder_url() ?>'"><div><div class="fw-semibold small"><?= e($it['name']) ?></div><div class="text-muted small">× <?= $it['quantity'] ?></div></div></div>
            <span class="fw-semibold"><?= format_price($it['line_total']) ?></span>
          </div>
          <?php endforeach; ?>
          <div class="mt-3">
            <div class="summary-row"><span>Subtotal</span><span><?= format_price($totals['subtotal']) ?></span></div>
            <?php if ($coupon): ?><div class="summary-row text-success"><span>Discount (<?= e($coupon['code']) ?>)</span><span>− <?= format_price($totals['discount']) ?></span></div><?php endif; ?>
            <div class="summary-row"><span>Delivery fee</span><span><?= $totals['delivery_fee'] > 0 ? format_price($totals['delivery_fee']) : 'Free' ?></span></div>
            <div class="summary-row total"><span>Total</span><span><?= format_price($totals['total']) ?></span></div>
          </div>
          <button class="btn btn-brand w-100 justify-content-center py-2 mt-3" <?= (!$codEnabled && !$manualEnabled) ? 'disabled' : '' ?>><i class="bi bi-bag-check"></i> Place Order</button>
          <p class="text-muted small text-center mt-2 mb-0">By placing this order you agree to our delivery terms.</p>
        </div>
      </div>
    </div>
  </form>
</div>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
