<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/config.php';
require_admin();
$pageTitle = 'Dashboard';
$pdo = db();
$stats = [
    'revenue'   => (float) $pdo->query('SELECT COALESCE(SUM(total),0) FROM orders WHERE status = "completed"')->fetchColumn(),
    'sales_today' => (float) $pdo->query('SELECT COALESCE(SUM(total),0) FROM orders WHERE status <> "cancelled" AND DATE(created_at) = CURDATE()')->fetchColumn(),
    'orders'    => (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
    'pending'   => (int) $pdo->query('SELECT COUNT(*) FROM orders WHERE status = "pending"')->fetchColumn(),
    'customers' => (int) $pdo->query('SELECT COUNT(*) FROM users WHERE role = "customer"')->fetchColumn(),
    'products'  => (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn(),
    'avg_order' => (float) $pdo->query('SELECT COALESCE(AVG(total),0) FROM orders WHERE status <> "cancelled"')->fetchColumn(),
    'unpaid'    => (int) $pdo->query('SELECT COUNT(*) FROM orders WHERE payment_status = "unpaid" AND status NOT IN ("cancelled","completed")')->fetchColumn(),
];
$statusCounts = [];
foreach ($pdo->query('SELECT status, COUNT(*) c FROM orders GROUP BY status') as $r) $statusCounts[$r['status']] = (int) $r['c'];
// Last 14 days revenue/orders
$days = [];
for ($i = 13; $i >= 0; $i--) { $d = date('Y-m-d', strtotime("-$i days")); $days[$d] = ['revenue' => 0.0, 'orders' => 0]; }
foreach ($pdo->query('SELECT DATE(created_at) d, SUM(CASE WHEN status <> "cancelled" THEN total ELSE 0 END) rev, COUNT(*) c FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY) GROUP BY DATE(created_at)') as $r) {
    if (isset($days[$r['d']])) $days[$r['d']] = ['revenue' => (float) $r['rev'], 'orders' => (int) $r['c']];
}
$recent = $pdo->query('SELECT o.*, u.name AS user_name FROM orders o INNER JOIN users u ON u.id = o.user_id ORDER BY o.created_at DESC LIMIT 8')->fetchAll();
$popular = $pdo->query('SELECT p.id, p.name, p.image, p.price, p.sold_count, COALESCE(SUM(oi.quantity),0) qty FROM products p LEFT JOIN order_items oi ON oi.product_id = p.id LEFT JOIN orders o ON o.id = oi.order_id AND o.status <> "cancelled" GROUP BY p.id ORDER BY qty DESC, p.sold_count DESC LIMIT 6')->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<div class="row g-3 mb-4">
  <?php foreach ([
      ['bi-cash-coin', format_price($stats['revenue']), 'Total revenue (completed)'],
      ['bi-graph-up-arrow', format_price($stats['sales_today']), 'Sales today'],
      ['bi-receipt', number_format($stats['orders']), 'Total orders · ' . $stats['pending'] . ' pending'],
      ['bi-people', number_format($stats['customers']), 'Customers'],
      ['bi-basket', number_format($stats['products']), 'Products'],
      ['bi-calculator', format_price($stats['avg_order']), 'Average order value'],
  ] as $s): ?>
  <div class="col-sm-6 col-xl-4"><div class="stat-card"><div class="stat-icon"><i class="bi <?= $s[0] ?>"></i></div><div><div class="stat-value"><?= $s[1] ?></div><div class="stat-label"><?= e($s[2]) ?></div></div></div></div>
  <?php endforeach; ?>
</div>
<div class="row g-3 mb-4">
  <div class="col-lg-8"><div class="panel h-100"><div class="panel-head">Revenue – last 14 days<span class="text-muted small fw-normal">Excludes cancelled</span></div><div class="panel-body">
    <div class="chart-wrap"><canvas id="revenueChart"></canvas></div>
    <div id="revenueFallback" class="d-none table-responsive"><table class="table table-admin"><thead><tr><th>Date</th><th>Orders</th><th>Revenue</th></tr></thead><tbody><?php foreach ($days as $d => $v): ?><tr><td><?= e($d) ?></td><td><?= $v['orders'] ?></td><td><?= format_price($v['revenue']) ?></td></tr><?php endforeach; ?></tbody></table></div>
  </div></div></div>
  <div class="col-lg-4"><div class="panel h-100"><div class="panel-head">Orders by status</div><div class="panel-body">
    <div class="chart-wrap" style="height:220px"><canvas id="statusChart"></canvas></div>
    <ul class="list-unstyled mt-3 mb-0 small"><?php foreach (order_statuses() as $k => $l): ?><li class="d-flex justify-content-between py-1 border-bottom border-secondary-subtle"><span class="<?= status_badge_class($k) ?>"><?= e($l) ?></span><strong><?= $statusCounts[$k] ?? 0 ?></strong></li><?php endforeach; ?></ul>
  </div></div></div>
</div>
<div class="row g-3">
  <div class="col-lg-8"><div class="panel"><div class="panel-head">Recent orders<a class="btn btn-sm btn-outline-brand" href="<?= url('admin/orders.php') ?>">All orders</a></div>
    <div class="table-responsive"><table class="table table-admin"><thead><tr><th>Order</th><th>Customer</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th><th></th></tr></thead><tbody>
      <?php if (!$recent): ?><tr><td colspan="7" class="text-center text-muted py-4">No orders yet.</td></tr><?php endif; ?>
      <?php foreach ($recent as $o): ?><tr><td><strong>#<?= e($o['order_number']) ?></strong></td><td><?= e($o['user_name']) ?></td><td class="text-brand fw-bold"><?= format_price($o['total']) ?></td><td><span class="badge-status-pending badge-<?= $o['payment_status'] === 'paid' ? 'paid' : 'unpaid' ?>"><?= e(ucfirst($o['payment_status'])) ?></span></td><td><span class="<?= status_badge_class($o['status']) ?>"><?= e(status_label($o['status'])) ?></span></td><td class="text-muted small"><?= time_ago($o['created_at']) ?></td><td><a class="btn btn-sm btn-dark-soft" href="<?= url('admin/orders.php?view=' . $o['id']) ?>">View</a></td></tr><?php endforeach; ?>
    </tbody></table></div></div></div>
  <div class="col-lg-4"><div class="panel"><div class="panel-head">Popular products</div><div class="panel-body p-0">
    <?php if (!$popular): ?><div class="text-muted text-center py-4">No products.</div><?php endif; ?>
    <?php foreach ($popular as $p): ?><div class="d-flex align-items-center gap-3 px-3 py-2 border-bottom border-secondary-subtle"><img class="thumb" src="<?= e(image_url($p['image'])) ?>" alt=""><div class="flex-grow-1"><div class="fw-semibold small"><?= e($p['name']) ?></div><div class="text-muted small"><?= format_price($p['price']) ?></div></div><span class="badge bg-brand"><?= (int) $p['qty'] ?> sold</span></div><?php endforeach; ?>
  </div></div></div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  renderChart('revenueChart', 'bar', <?= json_encode(array_map(fn($d) => date('M j', strtotime($d)), array_keys($days))) ?>, [
    { label: 'Revenue (<?= e(setting('currency_code', 'KHR')) ?>)', data: <?= json_encode(array_values(array_map(fn($v) => $v['revenue'], $days))) ?>, backgroundColor: 'rgba(255,214,41,.85)', borderRadius: 6 },
    { label: 'Orders', data: <?= json_encode(array_values(array_map(fn($v) => $v['orders'], $days))) ?>, type: 'line', borderColor: '#6fb1ff', backgroundColor: '#6fb1ff', yAxisID: 'y1', tension: .3 }
  ], 'revenueFallback');
  renderChart('statusChart', 'doughnut', <?= json_encode(array_values(order_statuses())) ?>, [{ data: <?= json_encode(array_map(fn($k) => $statusCounts[$k] ?? 0, array_keys(order_statuses()))) ?>, backgroundColor: ['#FFD629','#6fb1ff','#ffa05c','#c79bff','#5cd4ff','#3ddc84','#ff5c5c'], borderWidth: 0 }], 'statusChart');
});
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
