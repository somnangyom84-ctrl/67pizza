<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/config.php';
require_admin();
require __DIR__ . '/includes/helpers.php';
$pageTitle = 'Orders';
$pdo = db();
if (is_post()) {
    verify_csrf();
    $id = to_int($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($action === 'status' && $id) {
        $new = clean_string($_POST['status'] ?? '', 30);
        if (!array_key_exists($new, order_statuses())) { flash('danger', 'Invalid status.'); redirect(url('admin/orders.php?view=' . $id)); }
        $r = update_order_status($id, $new, clean_string($_POST['reason'] ?? '', 255) ?: null);
        flash($r['success'] ? 'success' : 'danger', $r['message']);
    } elseif ($action === 'payment' && $id) {
        $ps = in_array($_POST['payment_status'] ?? '', ['unpaid', 'paid', 'refunded'], true) ? $_POST['payment_status'] : 'unpaid';
        $ref = clean_string($_POST['reference'] ?? '', 120);
        $pdo->beginTransaction();
        $pdo->prepare('UPDATE orders SET payment_status = ? WHERE id = ?')->execute([$ps, $id]);
        $map = ['unpaid' => 'pending', 'paid' => 'paid', 'refunded' => 'refunded'];
        $pdo->prepare('UPDATE payments SET status = ?, reference = NULLIF(?, ""), paid_at = IF(? = "paid", NOW(), paid_at) WHERE order_id = ?')->execute([$map[$ps], $ref, $ps, $id]);
        $pdo->commit();
        flash('success', 'Payment record updated.');
    }
    redirect(url('admin/orders.php?view=' . $id));
}

$viewId = to_int($_GET['view'] ?? 0);
if ($viewId) {
    $s = $pdo->prepare('SELECT o.*, u.email AS user_email, u.name AS user_name FROM orders o INNER JOIN users u ON u.id = o.user_id WHERE o.id = ?'); $s->execute([$viewId]);
    $order = $s->fetch();
    if (!$order) { flash('danger', 'Order not found.'); redirect(url('admin/orders.php')); }
    $items = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?'); $items->execute([$viewId]); $items = $items->fetchAll();
    $payments = $pdo->prepare('SELECT * FROM payments WHERE order_id = ? ORDER BY id DESC'); $payments->execute([$viewId]); $payments = $payments->fetchAll();
    $next = allowed_transitions($order['status']);
    $pageTitle = 'Order #' . $order['order_number'];
    require __DIR__ . '/includes/header.php'; ?>
    <a href="<?= url('admin/orders.php') ?>" class="text-muted small"><i class="bi bi-arrow-left"></i> All orders</a>
    <div class="row g-3 mt-1">
      <div class="col-lg-8">
        <div class="panel mb-3"><div class="panel-head">Items<span class="<?= status_badge_class($order['status']) ?>"><?= e(status_label($order['status'])) ?></span></div>
          <div class="table-responsive"><table class="table table-admin"><thead><tr><th>Product</th><th>Unit price</th><th>Qty</th><th class="text-end">Total</th></tr></thead><tbody>
            <?php foreach ($items as $it): ?><tr><td><div class="d-flex align-items-center gap-2"><img class="thumb" src="<?= e(image_url($it['product_image'])) ?>" alt=""><span><?= e($it['product_name']) ?></span></div></td><td><?= format_price($it['unit_price']) ?></td><td><?= (int) $it['quantity'] ?></td><td class="text-end"><?= format_price($it['line_total']) ?></td></tr><?php endforeach; ?>
            <tr><td colspan="3" class="text-end text-muted">Subtotal</td><td class="text-end"><?= format_price($order['subtotal']) ?></td></tr>
            <?php if ((float) $order['discount'] > 0): ?><tr><td colspan="3" class="text-end text-muted">Discount <?= $order['coupon_code'] ? '(' . e($order['coupon_code']) . ')' : '' ?></td><td class="text-end text-success">− <?= format_price($order['discount']) ?></td></tr><?php endif; ?>
            <tr><td colspan="3" class="text-end text-muted">Delivery fee</td><td class="text-end"><?= format_price($order['delivery_fee']) ?></td></tr>
            <tr><td colspan="3" class="text-end fw-bold">Total</td><td class="text-end fw-bold text-brand fs-5"><?= format_price($order['total']) ?></td></tr>
          </tbody></table></div></div>
        <div class="panel"><div class="panel-head">Customer & delivery</div><div class="panel-body row g-3 small">
          <div class="col-md-6"><div class="text-muted">Account</div><div class="fw-semibold"><?= e($order['user_name']) ?></div><div><?= e($order['user_email']) ?></div><a href="<?= url('admin/customers.php?view=' . $order['user_id']) ?>">View customer</a></div>
          <div class="col-md-6"><div class="text-muted">Recipient</div><div class="fw-semibold"><?= e($order['customer_name']) ?></div><a href="tel:<?= e($order['customer_phone']) ?>"><?= e($order['customer_phone']) ?></a></div>
          <div class="col-md-6"><div class="text-muted">Address</div><div><?= e($order['delivery_address']) ?></div><?php if ($order['delivery_instructions']): ?><div class="text-muted"><?= e($order['delivery_instructions']) ?></div><?php endif; ?></div>
          <div class="col-md-6"><div class="text-muted">Notes</div><div><?= e($order['order_notes'] ?: '—') ?></div></div>
          <div class="col-md-6"><div class="text-muted">Placed</div><div><?= e($order['created_at']) ?></div></div>
          <?php if ($order['cancel_reason']): ?><div class="col-md-6"><div class="text-muted">Cancel reason</div><div><?= e($order['cancel_reason']) ?></div></div><?php endif; ?>
        </div></div>
      </div>
      <div class="col-lg-4">
        <div class="panel mb-3"><div class="panel-head">Update status</div><div class="panel-body">
          <?php if ($next): ?>
          <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?= $viewId ?>">
            <label class="form-label">Next status</label><select name="status" class="form-select mb-2"><?php foreach ($next as $n): ?><option value="<?= $n ?>"><?= e(status_label($n)) ?></option><?php endforeach; ?></select>
            <input name="reason" class="form-control mb-2" placeholder="Reason (for cancellation)">
            <button class="btn btn-brand w-100 justify-content-center">Update status</button>
          </form>
          <p class="text-muted small mt-2 mb-0">Allowed: <?= e(implode(' → ', array_map('status_label', $next))) ?></p>
          <?php else: ?><p class="text-muted mb-0">This order is <?= e(status_label($order['status'])) ?>. No further changes allowed.</p><?php endif; ?>
        </div></div>
        <div class="panel"><div class="panel-head">Payment</div><div class="panel-body">
          <div class="mb-2"><span class="text-muted small">Method:</span> <strong><?= $order['payment_method'] === 'cod' ? 'Cash on Delivery' : e(setting('manual_payment_label', 'Manual transfer')) ?></strong></div>
          <div class="mb-3"><span class="text-muted small">Status:</span> <span class="badge-status-pending badge-<?= $order['payment_status'] === 'paid' ? 'paid' : 'unpaid' ?>"><?= e(ucfirst($order['payment_status'])) ?></span></div>
          <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="payment"><input type="hidden" name="id" value="<?= $viewId ?>">
            <select name="payment_status" class="form-select mb-2"><?php foreach (['unpaid', 'paid', 'refunded'] as $ps): ?><option value="<?= $ps ?>" <?= $order['payment_status'] === $ps ? 'selected' : '' ?>><?= ucfirst($ps) ?></option><?php endforeach; ?></select>
            <input name="reference" class="form-control mb-2" placeholder="Transfer reference / receipt no." value="<?= e($payments[0]['reference'] ?? '') ?>">
            <button class="btn btn-outline-brand w-100 justify-content-center">Save payment</button>
          </form>
          <?php if ($payments): ?><hr class="border-secondary"><div class="small text-muted">Payment records</div><?php foreach ($payments as $p): ?><div class="small d-flex justify-content-between"><span><?= e(ucfirst($p['status'])) ?> · <?= format_price($p['amount']) ?></span><span><?= e($p['paid_at'] ?: $p['created_at']) ?></span></div><?php endforeach; ?><?php endif; ?>
        </div></div>
      </div>
    </div>
    <?php require __DIR__ . '/includes/footer.php'; exit;
}

$q = clean_string($_GET['q'] ?? '', 100);
$status = clean_string($_GET['status'] ?? '', 30);
$pay = clean_string($_GET['payment'] ?? '', 20);
$sort = in_array($_GET['sort'] ?? '', ['created_at', 'total', 'status', 'order_number'], true) ? $_GET['sort'] : 'created_at';
$dir = ($_GET['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
$where = []; $params = [];
if ($q !== '') { $where[] = '(o.order_number LIKE ? OR o.customer_name LIKE ? OR o.customer_phone LIKE ? OR u.email LIKE ?)'; array_push($params, "%$q%", "%$q%", "%$q%", "%$q%"); }
if ($status !== '' && array_key_exists($status, order_statuses())) { $where[] = 'o.status = ?'; $params[] = $status; }
if (in_array($pay, ['unpaid', 'paid', 'refunded'], true)) { $where[] = 'o.payment_status = ?'; $params[] = $pay; }
$w = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$cnt = $pdo->prepare("SELECT COUNT(*) FROM orders o INNER JOIN users u ON u.id = o.user_id $w"); $cnt->execute($params);
$pg = paginate((int) $cnt->fetchColumn(), 20, max(1, to_int($_GET['page'] ?? 1, 1)));
$stmt = $pdo->prepare("SELECT o.*, u.name AS user_name, (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) item_count FROM orders o INNER JOIN users u ON u.id = o.user_id $w ORDER BY o.$sort $dir LIMIT {$pg['per_page']} OFFSET {$pg['offset']}");
$stmt->execute($params);
$rows = $stmt->fetchAll();
$query = ['q' => $q ?: null, 'status' => $status ?: null, 'payment' => $pay ?: null, 'sort' => $sort, 'dir' => $dir];
require __DIR__ . '/includes/header.php'; ?>
<div class="panel">
  <div class="panel-head"><form class="toolbar" method="get"><input name="q" class="form-control" placeholder="Order #, name, phone, email" value="<?= e($q) ?>">
    <select name="status" class="form-select" onchange="this.form.submit()"><option value="">All statuses</option><?php foreach (order_statuses() as $k => $l): ?><option value="<?= $k ?>" <?= $status === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select>
    <select name="payment" class="form-select" onchange="this.form.submit()"><option value="">All payments</option><?php foreach (['unpaid', 'paid', 'refunded'] as $ps): ?><option value="<?= $ps ?>" <?= $pay === $ps ? 'selected' : '' ?>><?= ucfirst($ps) ?></option><?php endforeach; ?></select>
    <button class="btn btn-sm btn-outline-brand">Filter</button></form><span class="text-muted small"><?= $pg['total'] ?> orders</span></div>
  <div class="table-responsive"><table class="table table-admin"><thead><tr><th><?= sort_link('Order', 'order_number', $sort, $dir, $query) ?></th><th>Customer</th><th>Items</th><th><?= sort_link('Total', 'total', $sort, $dir, $query) ?></th><th>Payment</th><th><?= sort_link('Status', 'status', $sort, $dir, $query) ?></th><th><?= sort_link('Date', 'created_at', $sort, $dir, $query) ?></th><th></th></tr></thead><tbody>
    <?php if (!$rows): ?><tr><td colspan="8" class="text-center text-muted py-5">No orders found.</td></tr><?php endif; ?>
    <?php foreach ($rows as $o): ?><tr><td><strong>#<?= e($o['order_number']) ?></strong></td><td><?= e($o['customer_name']) ?><div class="text-muted small"><?= e($o['customer_phone']) ?></div></td><td><?= (int) $o['item_count'] ?></td><td class="text-brand fw-semibold"><?= format_price($o['total']) ?></td><td><span class="badge-status-pending badge-<?= $o['payment_status'] === 'paid' ? 'paid' : 'unpaid' ?>"><?= e(ucfirst($o['payment_status'])) ?></span><div class="text-muted small"><?= $o['payment_method'] === 'cod' ? 'COD' : 'Transfer' ?></div></td><td><span class="<?= status_badge_class($o['status']) ?>"><?= e(status_label($o['status'])) ?></span></td><td class="small text-muted"><?= date('M j, H:i', strtotime($o['created_at'])) ?></td><td class="text-end"><a class="btn btn-sm btn-outline-brand" href="<?= url('admin/orders.php?view=' . $o['id']) ?>">Manage</a></td></tr><?php endforeach; ?>
  </tbody></table></div>
  <div class="px-3"><?= pagination_links($pg, $query) ?></div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
