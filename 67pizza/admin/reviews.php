<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/config.php';
require_admin();
require __DIR__ . '/includes/helpers.php';
$pageTitle = 'Reviews';
$pdo = db();
if (is_post()) {
    verify_csrf();
    $id = to_int($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if (in_array($action, ['approved', 'rejected', 'pending'], true) && $id) { $pdo->prepare('UPDATE reviews SET status = ? WHERE id = ?')->execute([$action, $id]); flash('success', 'Review marked as ' . $action . '.'); }
    elseif ($action === 'delete' && $id) { $pdo->prepare('DELETE FROM reviews WHERE id = ?')->execute([$id]); flash('success', 'Review deleted.'); }
    redirect($_SERVER['HTTP_REFERER'] ?? url('admin/reviews.php'));
}
$status = in_array($_GET['status'] ?? '', ['pending', 'approved', 'rejected'], true) ? $_GET['status'] : '';
$w = $status ? 'WHERE r.status = ?' : ''; $params = $status ? [$status] : [];
$cnt = $pdo->prepare("SELECT COUNT(*) FROM reviews r $w"); $cnt->execute($params);
$pg = paginate((int) $cnt->fetchColumn(), 20, max(1, to_int($_GET['page'] ?? 1, 1)));
$stmt = $pdo->prepare("SELECT r.*, u.name user_name, p.name product_name, o.order_number FROM reviews r INNER JOIN users u ON u.id = r.user_id INNER JOIN products p ON p.id = r.product_id LEFT JOIN orders o ON o.id = r.order_id $w ORDER BY FIELD(r.status,'pending','approved','rejected'), r.created_at DESC LIMIT {$pg['per_page']} OFFSET {$pg['offset']}");
$stmt->execute($params); $rows = $stmt->fetchAll();
require __DIR__ . '/includes/header.php'; ?>
<div class="panel"><div class="panel-head"><div class="filter-chips"><a href="<?= url('admin/reviews.php') ?>" class="<?= $status === '' ? 'active' : '' ?>">All</a><?php foreach (['pending', 'approved', 'rejected'] as $s): ?><a href="<?= url('admin/reviews.php?status=' . $s) ?>" class="<?= $status === $s ? 'active' : '' ?>"><?= ucfirst($s) ?></a><?php endforeach; ?></div><span class="text-muted small"><?= $pg['total'] ?> reviews</span></div>
  <div class="table-responsive"><table class="table table-admin"><thead><tr><th>Customer</th><th>Product</th><th>Rating</th><th>Comment</th><th>Order</th><th>Status</th><th>Date</th><th class="text-end">Actions</th></tr></thead><tbody>
    <?php if (!$rows): ?><tr><td colspan="8" class="text-center text-muted py-5">No reviews.</td></tr><?php endif; ?>
    <?php foreach ($rows as $r): ?><tr><td><?= e($r['user_name']) ?></td><td><?= e($r['product_name']) ?></td><td><?= render_stars((float) $r['rating']) ?></td><td class="small" style="max-width:280px"><?= e($r['comment'] ?: '—') ?></td><td class="small text-muted"><?= $r['order_number'] ? '#' . e($r['order_number']) : '—' ?></td><td><span class="<?= $r['status'] === 'approved' ? 'badge-status-completed' : ($r['status'] === 'rejected' ? 'badge-status-cancelled' : 'badge-status-pending') ?>"><?= ucfirst($r['status']) ?></span></td><td class="small text-muted"><?= date('M j, Y', strtotime($r['created_at'])) ?></td>
      <td class="text-end text-nowrap"><?php if ($r['status'] !== 'approved'): ?><form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="approved"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-sm btn-brand" title="Approve"><i class="bi bi-check-lg"></i></button></form> <?php endif; ?><?php if ($r['status'] !== 'rejected'): ?><form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="rejected"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-sm btn-dark-soft" title="Reject"><i class="bi bi-x-lg"></i></button></form> <?php endif; ?><?= admin_delete_form('delete', (int) $r['id'], 'Delete this review?') ?></td></tr><?php endforeach; ?>
  </tbody></table></div><div class="px-3"><?= pagination_links($pg, ['status' => $status ?: null]) ?></div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
