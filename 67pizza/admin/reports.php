<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/config.php';
require_admin();
$pageTitle = 'Reports';
$pdo = db();
$from = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['from'] ?? '') ? $_GET['from'] : date('Y-m-01');
$to = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['to'] ?? '') ? $_GET['to'] : date('Y-m-d');
if ($from > $to) [$from, $to] = [$to, $from];
$range = [$from . ' 00:00:00', $to . ' 23:59:59'];
$sum = $pdo->prepare('SELECT COUNT(*) orders, SUM(status = "completed") completed, SUM(status = "cancelled") cancelled, COALESCE(SUM(CASE WHEN status = "completed" THEN total END),0) revenue, COALESCE(SUM(CASE WHEN status <> "cancelled" THEN discount END),0) discounts, COALESCE(SUM(CASE WHEN status <> "cancelled" THEN delivery_fee END),0) fees, COALESCE(AVG(CASE WHEN status <> "cancelled" THEN total END),0) avg_order FROM orders WHERE created_at BETWEEN ? AND ?');
$sum->execute($range); $s = $sum->fetch();
$daily = $pdo->prepare('SELECT DATE(created_at) d, COUNT(*) c, COALESCE(SUM(CASE WHEN status <> "cancelled" THEN total END),0) rev FROM orders WHERE created_at BETWEEN ? AND ? GROUP BY DATE(created_at) ORDER BY d'); $daily->execute($range); $daily = $daily->fetchAll();
$top = $pdo->prepare('SELECT oi.product_name, SUM(oi.quantity) qty, SUM(oi.line_total) rev FROM order_items oi INNER JOIN orders o ON o.id = oi.order_id WHERE o.status <> "cancelled" AND o.created_at BETWEEN ? AND ? GROUP BY oi.product_name ORDER BY qty DESC LIMIT 10'); $top->execute($range); $top = $top->fetchAll();
$byCat = $pdo->prepare('SELECT c.name, SUM(oi.quantity) qty, SUM(oi.line_total) rev FROM order_items oi INNER JOIN orders o ON o.id = oi.order_id LEFT JOIN products p ON p.id = oi.product_id LEFT JOIN categories c ON c.id = p.category_id WHERE o.status <> "cancelled" AND o.created_at BETWEEN ? AND ? GROUP BY c.name ORDER BY rev DESC'); $byCat->execute($range); $byCat = $byCat->fetchAll();
$byPay = $pdo->prepare('SELECT payment_method, payment_status, COUNT(*) c, COALESCE(SUM(total),0) amt FROM orders WHERE status <> "cancelled" AND created_at BETWEEN ? AND ? GROUP BY payment_method, payment_status'); $byPay->execute($range); $byPay = $byPay->fetchAll();
$topCustomers = $pdo->prepare('SELECT u.name, u.email, COUNT(*) c, SUM(o.total) amt FROM orders o INNER JOIN users u ON u.id = o.user_id WHERE o.status <> "cancelled" AND o.created_at BETWEEN ? AND ? GROUP BY u.id ORDER BY amt DESC LIMIT 5'); $topCustomers->execute($range); $topCustomers = $topCustomers->fetchAll();
require __DIR__ . '/includes/header.php'; ?>
<form class="toolbar mb-3" method="get"><label class="text-muted small">From</label><input type="date" name="from" class="form-control" value="<?= e($from) ?>"><label class="text-muted small">To</label><input type="date" name="to" class="form-control" value="<?= e($to) ?>"><button class="btn btn-sm btn-brand">Apply</button><a class="btn btn-sm btn-dark-soft" href="<?= url('admin/reports.php?from=' . date('Y-m-d', strtotime('-30 days')) . '&to=' . date('Y-m-d')) ?>">Last 30 days</a><a class="btn btn-sm btn-dark-soft" href="<?= url('admin/reports.php?from=' . date('Y-01-01') . '&to=' . date('Y-m-d')) ?>">This year</a></form>
<div class="row g-3 mb-3">
  <?php foreach ([['bi-cash-coin', format_price($s['revenue']), 'Completed revenue'], ['bi-receipt', (int) $s['orders'], 'Orders placed'], ['bi-check2-circle', (int) $s['completed'], 'Completed'], ['bi-x-circle', (int) $s['cancelled'], 'Cancelled'], ['bi-ticket-perforated', format_price($s['discounts']), 'Discounts given'], ['bi-calculator', format_price($s['avg_order']), 'Avg order value']] as $c): ?>
  <div class="col-6 col-xl-2"><div class="stat-card"><div class="stat-icon"><i class="bi <?= $c[0] ?>"></i></div><div><div class="stat-value" style="font-size:1.1rem"><?= $c[1] ?></div><div class="stat-label"><?= $c[2] ?></div></div></div></div>
  <?php endforeach; ?>
</div>
<div class="row g-3">
  <div class="col-lg-8"><div class="panel"><div class="panel-head">Daily sales</div><div class="panel-body"><div class="chart-wrap"><canvas id="dailyChart"></canvas></div>
    <div id="dailyFallback" class="d-none"></div>
    <div class="table-responsive mt-3"><table class="table table-admin"><thead><tr><th>Date</th><th>Orders</th><th class="text-end">Revenue</th></tr></thead><tbody><?php if (!$daily): ?><tr><td colspan="3" class="text-center text-muted">No orders in this period.</td></tr><?php endif; ?><?php foreach ($daily as $d): ?><tr><td><?= e($d['d']) ?></td><td><?= (int) $d['c'] ?></td><td class="text-end"><?= format_price($d['rev']) ?></td></tr><?php endforeach; ?></tbody></table></div></div></div></div>
  <div class="col-lg-4">
    <div class="panel mb-3"><div class="panel-head">Top products</div><div class="table-responsive"><table class="table table-admin"><tbody><?php if (!$top): ?><tr><td class="text-muted text-center">No data.</td></tr><?php endif; ?><?php foreach ($top as $t): ?><tr><td><?= e($t['product_name']) ?></td><td><?= (int) $t['qty'] ?>×</td><td class="text-end"><?= format_price($t['rev']) ?></td></tr><?php endforeach; ?></tbody></table></div></div>
    <div class="panel mb-3"><div class="panel-head">Sales by category</div><div class="table-responsive"><table class="table table-admin"><tbody><?php if (!$byCat): ?><tr><td class="text-muted text-center">No data.</td></tr><?php endif; ?><?php foreach ($byCat as $c): ?><tr><td><?= e($c['name'] ?? 'Deleted products') ?></td><td><?= (int) $c['qty'] ?>×</td><td class="text-end"><?= format_price($c['rev']) ?></td></tr><?php endforeach; ?></tbody></table></div></div>
    <div class="panel mb-3"><div class="panel-head">Payments</div><div class="table-responsive"><table class="table table-admin"><tbody><?php if (!$byPay): ?><tr><td class="text-muted text-center">No data.</td></tr><?php endif; ?><?php foreach ($byPay as $p): ?><tr><td><?= $p['payment_method'] === 'cod' ? 'COD' : 'Manual transfer' ?></td><td><span class="badge-status-pending badge-<?= $p['payment_status'] === 'paid' ? 'paid' : 'unpaid' ?>"><?= e(ucfirst($p['payment_status'])) ?></span></td><td><?= (int) $p['c'] ?></td><td class="text-end"><?= format_price($p['amt']) ?></td></tr><?php endforeach; ?></tbody></table></div></div>
    <div class="panel"><div class="panel-head">Top customers</div><div class="table-responsive"><table class="table table-admin"><tbody><?php if (!$topCustomers): ?><tr><td class="text-muted text-center">No data.</td></tr><?php endif; ?><?php foreach ($topCustomers as $c): ?><tr><td><?= e($c['name']) ?><div class="text-muted small"><?= e($c['email']) ?></div></td><td><?= (int) $c['c'] ?> orders</td><td class="text-end"><?= format_price($c['amt']) ?></td></tr><?php endforeach; ?></tbody></table></div></div>
  </div>
</div>
<script>document.addEventListener('DOMContentLoaded', function () {
  renderChart('dailyChart', 'line', <?= json_encode(array_column($daily, 'd')) ?>, [{ label: 'Revenue', data: <?= json_encode(array_map('floatval', array_column($daily, 'rev'))) ?>, borderColor: '#FFD629', backgroundColor: 'rgba(255,214,41,.2)', fill: true, tension: .3 }], 'dailyFallback');
});</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
