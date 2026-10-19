<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
$pageTitle = 'Your Cart';
$items = cart_items();
$coupon = null;
$couponMsg = '';
if (!empty($_SESSION['coupon_code']) && $items) {
    $v = validate_coupon($_SESSION['coupon_code'], cart_totals($items)['subtotal'], cart_user_id());
    if ($v['valid']) { $coupon = $v['coupon']; } else { $couponMsg = $v['message']; unset($_SESSION['coupon_code']); }
}
$totals = cart_totals($items, $coupon);
$minOrder = (float) setting('min_order_amount', '0');
require ROOT_PATH . '/includes/header.php';
?>
<div class="container">
  <h1 class="page-title"><i class="bi bi-bag"></i> Your Cart</h1>
  <?php if (!$items): ?>
  <div class="empty-state card-dark"><i class="bi bi-bag-x"></i><h4>Your cart is empty</h4><p>Looks like you haven't added anything yet.</p><a class="btn btn-brand" href="<?= url('menu.php') ?>">Browse the menu</a></div>
  <?php else: ?>
  <div class="row g-4">
    <div class="col-lg-8">
      <div class="card-dark p-3 p-md-4">
        <?php foreach ($items as $it): ?>
        <div class="cart-item">
          <img src="<?= e(image_url($it['image'])) ?>" alt="<?= e($it['name']) ?>" onerror="this.onerror=null;this.src='<?= placeholder_url() ?>'">
          <div>
            <div class="text-muted small"><?= e($it['category']) ?></div>
            <a href="<?= url('product.php?slug=' . urlencode($it['slug'])) ?>" class="text-white fw-semibold"><?= e($it['name']) ?></a>
            <div class="text-brand fw-bold mt-1"><?= format_price($it['price']) ?> <span class="text-muted fw-normal small">× <?= $it['quantity'] ?> = <?= format_price($it['line_total']) ?></span></div>
          </div>
          <div class="cart-actions d-flex align-items-center gap-2">
            <form method="post" action="<?= url('actions/cart.php') ?>" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="update"><input type="hidden" name="product_id" value="<?= $it['product_id'] ?>">
              <div class="qty-control" data-live="1" data-id="<?= $it['product_id'] ?>">
                <button type="button" class="js-qty" data-dir="down" aria-label="Decrease"><i class="bi bi-dash"></i></button>
                <input type="number" name="quantity" value="<?= $it['quantity'] ?>" min="1" max="50" aria-label="Quantity">
                <button type="button" class="js-qty" data-dir="up" aria-label="Increase"><i class="bi bi-plus"></i></button>
              </div>
              <noscript><button class="btn btn-sm btn-dark-soft ms-1">Update</button></noscript>
            </form>
            <form method="post" action="<?= url('actions/cart.php') ?>" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="remove"><input type="hidden" name="product_id" value="<?= $it['product_id'] ?>">
              <button class="icon-btn text-danger" title="Remove" aria-label="Remove"><i class="bi bi-trash"></i></button>
            </form>
          </div>
        </div>
        <?php endforeach; ?>
        <div class="d-flex justify-content-between align-items-center pt-3 flex-wrap gap-2">
          <a href="<?= url('menu.php') ?>" class="btn btn-dark-soft"><i class="bi bi-arrow-left"></i> Continue shopping</a>
          <form method="post" action="<?= url('actions/cart.php') ?>" data-confirm="Remove all items from your cart?"><?= csrf_field() ?><input type="hidden" name="action" value="clear"><button class="btn btn-link text-danger text-decoration-none">Clear cart</button></form>
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card-dark p-4 sticky-top" style="top:90px">
        <h5 class="mb-3">Order Summary</h5>
        <form method="post" action="<?= url('actions/cart.php') ?>" class="mb-3"><?= csrf_field() ?>
          <?php if ($coupon): ?>
          <input type="hidden" name="action" value="remove_coupon">
          <div class="d-flex justify-content-between align-items-center alert alert-success py-2 mb-0"><span><i class="bi bi-ticket-perforated"></i> <strong><?= e($coupon['code']) ?></strong> applied</span><button class="btn btn-sm btn-link text-danger p-0">Remove</button></div>
          <?php else: ?>
          <input type="hidden" name="action" value="apply_coupon">
          <label class="form-label">Coupon code</label>
          <div class="input-group"><input name="coupon_code" class="form-control" placeholder="WELCOME20" style="text-transform:uppercase"><button class="btn btn-brand" style="border-radius:0 12px 12px 0">Apply</button></div>
          <?php if ($couponMsg): ?><div class="text-danger small mt-1"><?= e($couponMsg) ?></div><?php endif; ?>
          <?php endif; ?>
        </form>
        <div class="summary-row"><span>Subtotal</span><span><?= format_price($totals['subtotal']) ?></span></div>
        <?php if ($totals['discount'] > 0): ?><div class="summary-row text-success"><span>Discount</span><span>− <?= format_price($totals['discount']) ?></span></div><?php endif; ?>
        <div class="summary-row"><span>Delivery fee</span><span><?= $totals['delivery_fee'] > 0 ? format_price($totals['delivery_fee']) : '<span class="text-success">Free</span>' ?></span></div>
        <div class="summary-row total"><span>Total</span><span><?= format_price($totals['total']) ?></span></div>
        <?php if ($minOrder > 0 && $totals['subtotal'] < $minOrder): ?>
        <div class="alert alert-warning small mt-3 mb-0">Minimum order is <?= format_price($minOrder) ?>. Add <?= format_price($minOrder - $totals['subtotal']) ?> more to checkout.</div>
        <?php else: ?>
        <a href="<?= url('checkout.php') ?>" class="btn btn-brand w-100 justify-content-center mt-3 py-2">Proceed to Checkout <i class="bi bi-arrow-right"></i></a>
        <?php endif; ?>
        <?php if ((float) setting('free_delivery_over', '0') > 0 && $totals['subtotal'] < (float) setting('free_delivery_over')): ?>
        <p class="text-muted small mt-2 mb-0"><i class="bi bi-truck"></i> Free delivery on orders over <?= format_price(setting('free_delivery_over')) ?>.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
