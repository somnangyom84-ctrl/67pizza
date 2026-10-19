<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
require_login();
$pageTitle = 'My Orders';
$user = current_user();
$status = clean_string($_GET['status'] ?? '', 30);
$where = 'WHERE o.user_id = ?';
$params = [$user['id']];
if ($status !== '' && array_key_exists($status, order_statuses())) { $where .= ' AND o.status = ?'; $params[] = $status; }
$cnt = db()->prepare("SELECT COUNT(*) FROM orders o $where");
$cnt->execute($params);
$pg = paginate((int) $cnt->fetchColumn(), 10, max(1, to_int($_GET['page'] ?? 1, 1)));
$stmt = db()->prepare("SELECT o.*, (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS item_count FROM orders o $where ORDER BY o.created_at DESC LIMIT {$pg['per_page']} OFFSET {$pg['offset']}");
$stmt->execute($params);
$orders = $stmt->fetchAll();
require ROOT_PATH . '/includes/header.php';
?>
<div class="container">
  <h1 class="page-title"><i class="bi bi-receipt"></i> My Orders</h1>
  <div class="filter-chips mb-3">
    <a href="<?= url('orders.php') ?>" class="<?= $status === '' ? 'active' : '' ?>">All</a>
    <?php foreach (order_statuses() as $k => $label): ?><a href="<?= url('orders.php?status=' . $k) ?>" class="<?= $status === $k ? 'active' : '' ?>"><?= e($label) ?></a><?php endforeach; ?>
  </div>
  <?php if (!$orders): ?>
  <div class="empty-state card-dark"><i class="bi bi-receipt-cutoff"></i><h4>No orders yet</h4><p>When you place an order it will show up here.</p><a class="btn btn-brand" href="<?= url('menu.php') ?>">Order something tasty</a></div>
  <?php else: ?>
  <div class="table-responsive card-dark">
    <table class="table table-dark-custom mb-0 align-middle">
      <thead><tr><th>Order</th><th>Date</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($orders as $o): ?>
        <tr>
          <td><strong>#<?= e($o['order_number']) ?></strong></td>
          <td><?= date('M j, Y H:i', strtotime($o['created_at'])) ?></td>
          <td><?= (int) $o['item_count'] ?></td>
          <td class="text-brand fw-bold"><?= format_price($o['total']) ?></td>
          <td><span class="badge-<?= $o['payment_status'] === 'paid' ? 'paid' : 'unpaid' ?> badge-status-pending"><?= e(ucfirst($o['payment_status'])) ?></span></td>
          <td><span class="<?= status_badge_class($o['status']) ?>"><?= e(status_label($o['status'])) ?></span></td>
          <td class="text-end"><a href="<?= url('order-details.php?id=' . $o['id']) ?>" class="btn btn-sm btn-dark-soft">View</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= pagination_links($pg, ['status' => $status ?: null]) ?>
  <?php endif; ?>
</div>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
