<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
require_login();
$user = current_user();
$id = to_int($_GET['id'] ?? 0);
// Ownership check: customers may only see their own orders
$stmt = db()->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ? LIMIT 1');
$stmt->execute([$id, $user['id']]);
$order = $stmt->fetch();
if (!$order) {
    http_response_code(404);
    $pageTitle = 'Order not found';
    require ROOT_PATH . '/includes/header.php';
    echo '<div class="container"><div class="empty-state"><i class="bi bi-emoji-frown"></i><h4>Order not found</h4><a class="btn btn-brand mt-2" href="' . url('orders.php') . '">My orders</a></div></div>';
    require ROOT_PATH . '/includes/footer.php';
    exit;
}
$pageTitle = 'Order #' . $order['order_number'];
$items = db()->prepare('SELECT * FROM order_items WHERE order_id = ?');
$items->execute([$id]);
$items = $items->fetchAll();
$reviewed = [];
if ($order['status'] === 'completed') {
    $r = db()->prepare('SELECT product_id FROM reviews WHERE order_id = ? AND user_id = ?');
    $r->execute([$id, $user['id']]);
    $reviewed = array_map('intval', $r->fetchAll(PDO::FETCH_COLUMN));
}
$flow = ['pending', 'confirmed', 'preparing', 'ready_for_delivery', 'delivering', 'completed'];
$currentIdx = array_search($order['status'], $flow, true);
$isCancelled = $order['status'] === 'cancelled';
$justPlaced = !empty($_GET['placed']);
require ROOT_PATH . '/includes/header.php';
?>
<div class="container">
  <?php if ($justPlaced): ?>
  <div class="card-dark p-4 mt-4 text-center border-success"><i class="bi bi-check-circle-fill text-success fs-1"></i><h2 class="mt-2">Thank you! Your order has been received.</h2><p class="text-muted mb-0">Order <strong class="text-brand">#<?= e($order['order_number']) ?></strong> · We'll notify you as it progresses.</p>
  <?php if ($order['payment_method'] === 'manual_transfer'): ?><div class="alert alert-info text-start mt-3 mb-0 small"><strong><?= e(setting('manual_payment_label')) ?>:</strong><br><?= nl2br(e(setting('manual_payment_instructions'))) ?><?php if (setting('manual_payment_qr')): ?><div class="mt-2"><img class="img-qr" src="<?= e(image_url(setting('manual_payment_qr'))) ?>" alt="Payment QR"></div><?php endif; ?></div><?php endif; ?>
  </div>
  <?php endif; ?>
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-4 mb-3">
    <div><a href="<?= url('orders.php') ?>" class="text-muted small"><i class="bi bi-arrow-left"></i> Back to orders</a><h1 class="h3 fw-bold mb-0 mt-1">Order #<?= e($order['order_number']) ?></h1><div class="text-muted small">Placed <?= date('M j, Y \a\t H:i', strtotime($order['created_at'])) ?></div></div>
    <div class="d-flex gap-2 align-items-center"><span class="<?= status_badge_class($order['status']) ?> fs-6"><?= e(status_label($order['status'])) ?></span>
      <?php if (customer_can_cancel($order)): ?>
      <form method="post" action="<?= url('actions/order-cancel.php') ?>" data-confirm="Cancel this order?"><?= csrf_field() ?><input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>"><button class="btn btn-sm btn-outline-danger rounded-pill">Cancel order</button></form>
      <?php endif; ?>
    </div>
  </div>
  <div class="row g-4">
    <div class="col-lg-8">
      <div class="card-dark p-4 mb-4">
        <h5 class="mb-3">Items</h5>
        <?php foreach ($items as $it): ?>
        <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-secondary-subtle gap-2 flex-wrap">
          <div class="d-flex align-items-center gap-3"><img src="<?= e(image_url($it['product_image'])) ?>" alt="" width="56" height="56" style="object-fit:cover;border-radius:12px" onerror="this.onerror=null;this.src='<?= placeholder_url() ?>'"><div><div class="fw-semibold"><?= e($it['product_name']) ?></div><div class="text-muted small"><?= format_price($it['unit_price']) ?> × <?= (int) $it['quantity'] ?></div></div></div>
          <div class="d-flex align-items-center gap-3"><span class="fw-bold"><?= format_price($it['line_total']) ?></span>
            <?php if ($order['status'] === 'completed' && $it['product_id'] && !in_array((int) $it['product_id'], $reviewed, true)): ?>
            <button class="btn btn-sm btn-outline-brand" data-bs-toggle="collapse" data-bs-target="#rev<?= (int) $it['id'] ?>">Review</button>
            <?php elseif (in_array((int) ($it['product_id'] ?? 0), $reviewed, true)): ?><span class="text-success small"><i class="bi bi-check2"></i> Reviewed</span><?php endif; ?>
          </div>
          <?php if ($order['status'] === 'completed' && $it['product_id'] && !in_array((int) $it['product_id'], $reviewed, true)): ?>
          <div class="collapse w-100" id="rev<?= (int) $it['id'] ?>">
            <form method="post" action="<?= url('actions/review.php') ?>" class="card-dark p-3 mt-2 bg-transparent"><?= csrf_field() ?><input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>"><input type="hidden" name="product_id" value="<?= (int) $it['product_id'] ?>">
              <div class="row g-2 align-items-end"><div class="col-sm-3"><label class="form-label">Rating</label><select name="rating" class="form-select"><?php for ($s = 5; $s >= 1; $s--): ?><option value="<?= $s ?>"><?= $s ?> ★</option><?php endfor; ?></select></div><div class="col-sm-7"><label class="form-label">Comment</label><input name="comment" class="form-control" maxlength="1000" placeholder="How was it?"></div><div class="col-sm-2"><button class="btn btn-brand w-100 justify-content-center">Send</button></div></div>
            </form>
          </div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <div class="mt-3">
          <div class="summary-row"><span>Subtotal</span><span><?= format_price($order['subtotal']) ?></span></div>
          <?php if ((float) $order['discount'] > 0): ?><div class="summary-row text-success"><span>Discount<?= $order['coupon_code'] ? ' (' . e($order['coupon_code']) . ')' : '' ?></span><span>− <?= format_price($order['discount']) ?></span></div><?php endif; ?>
          <div class="summary-row"><span>Delivery fee</span><span><?= (float) $order['delivery_fee'] > 0 ? format_price($order['delivery_fee']) : 'Free' ?></span></div>
          <div class="summary-row total"><span>Total</span><span><?= format_price($order['total']) ?></span></div>
        </div>
      </div>
      <div class="card-dark p-4">
        <h5 class="mb-3">Delivery & payment</h5>
        <div class="row g-3 small">
          <div class="col-md-6"><div class="text-muted">Recipient</div><div class="fw-semibold"><?= e($order['customer_name']) ?></div><div><a href="tel:<?= e($order['customer_phone']) ?>" class="text-white"><?= e($order['customer_phone']) ?></a></div></div>
          <div class="col-md-6"><div class="text-muted">Address</div><div class="fw-semibold"><?= e($order['delivery_address']) ?></div><?php if ($order['delivery_instructions']): ?><div class="text-muted"><?= e($order['delivery_instructions']) ?></div><?php endif; ?></div>
          <div class="col-md-6"><div class="text-muted">Payment</div><div class="fw-semibold"><?= $order['payment_method'] === 'cod' ? 'Cash on Delivery' : e(setting('manual_payment_label', 'Manual Transfer')) ?></div><span class="badge-<?= $order['payment_status'] === 'paid' ? 'paid' : 'unpaid' ?> badge-status-pending"><?= e(ucfirst($order['payment_status'])) ?></span></div>
          <?php if ($order['order_notes']): ?><div class="col-md-6"><div class="text-muted">Notes</div><div><?= e($order['order_notes']) ?></div></div><?php endif; ?>
          <?php if ($isCancelled && $order['cancel_reason']): ?><div class="col-12"><div class="text-muted">Cancellation reason</div><div><?= e($order['cancel_reason']) ?></div></div><?php endif; ?>
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card-dark p-4">
        <h5 class="mb-3">Order status</h5>
        <ul class="timeline">
          <?php foreach ($flow as $i => $st):
            $done = !$isCancelled && $currentIdx !== false && $i <= $currentIdx;
            $current = !$isCancelled && $i === $currentIdx; ?>
          <li class="<?= $done ? 'done' : '' ?> <?= $current ? 'current' : '' ?>"><span class="dot"><i class="bi bi-check"></i></span><div class="tl-title"><?= e(status_label($st)) ?></div><div class="tl-sub">
            <?php if ($st === 'pending') echo date('M j, H:i', strtotime($order['created_at']));
                  elseif ($st === 'confirmed' && $order['confirmed_at']) echo date('M j, H:i', strtotime($order['confirmed_at']));
                  elseif ($st === 'completed' && $order['completed_at']) echo date('M j, H:i', strtotime($order['completed_at']));
                  elseif ($current) echo 'In progress';
                  elseif (!$done) echo 'Pending'; ?></div></li>
          <?php endforeach; ?>
          <?php if ($isCancelled): ?><li class="cancelled"><span class="dot"><i class="bi bi-x"></i></span><div class="tl-title text-danger">Cancelled</div><div class="tl-sub"><?= $order['cancelled_at'] ? date('M j, H:i', strtotime($order['cancelled_at'])) : '' ?></div></li><?php endif; ?>
        </ul>
        <p class="text-muted small mb-0">Need help? <a href="https://t.me/<?= e(setting('telegram_username')) ?>" target="_blank" rel="noopener">Message us on Telegram</a> or call <a href="tel:<?= e(setting('contact_phone')) ?>"><?= e(setting('contact_phone')) ?></a>.</p>
      </div>
    </div>
  </div>
</div>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
