<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/config.php';
require_admin();
require __DIR__ . '/includes/helpers.php';
$pageTitle = 'Customers';
$pdo = db();
$me = current_user();
if (is_post()) {
    verify_csrf();
    $id = to_int($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($id === (int) $me['id']) { flash('danger', 'You cannot modify your own account here.'); redirect(url('admin/customers.php')); }
    if ($action === 'toggle') {
        $pdo->prepare('UPDATE users SET is_active = 1 - is_active WHERE id = ?')->execute([$id]);
        flash('success', 'Account status updated.');
    } elseif ($action === 'role') {
        $role = ($_POST['role'] ?? '') === 'admin' ? 'admin' : 'customer';
        $pdo->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$role, $id]);
        flash('success', 'Role updated to ' . $role . '.');
    } elseif ($action === 'delete') {
        $c = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE user_id = ?'); $c->execute([$id]);
        if ((int) $c->fetchColumn() > 0) { flash('danger', 'This customer has orders and cannot be deleted. Deactivate the account instead.'); }
        else { $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]); flash('success', 'Account deleted.'); }
    }
    redirect($_SERVER['HTTP_REFERER'] ?? url('admin/customers.php'));
}
$viewId = to_int($_GET['view'] ?? 0);
if ($viewId) {
    $s = $pdo->prepare('SELECT * FROM users WHERE id = ?'); $s->execute([$viewId]); $u = $s->fetch();
    if (!$u) { flash('danger', 'User not found.'); redirect(url('admin/customers.php')); }
    $orders = $pdo->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 50'); $orders->execute([$viewId]); $orders = $orders->fetchAll();
    $sum = $pdo->prepare('SELECT COUNT(*) c, COALESCE(SUM(CASE WHEN status="completed" THEN total END),0) s FROM orders WHERE user_id = ?'); $sum->execute([$viewId]); $sum = $sum->fetch();
    $pageTitle = 'Customer: ' . $u['name'];
    require __DIR__ . '/includes/header.php'; ?>
    <a href="<?= url('admin/customers.php') ?>" class="text-muted small"><i class="bi bi-arrow-left"></i> All customers</a>
    <div class="row g-3 mt-1"><div class="col-lg-4"><div class="panel"><div class="panel-body">
      <div class="d-flex align-items-center gap-3 mb-3"><span class="avatar" style="width:56px;height:56px;font-size:1.4rem"><?= e(mb_strtoupper(mb_substr($u['name'], 0, 1))) ?></span><div><h5 class="mb-0"><?= e($u['name']) ?></h5><span class="badge bg-brand"><?= e($u['role']) ?></span> <?= bool_badge($u['is_active']) ?></div></div>
      <dl class="row small mb-0"><dt class="col-4 text-muted">Email</dt><dd class="col-8"><?= e($u['email']) ?></dd><dt class="col-4 text-muted">Phone</dt><dd class="col-8"><?= e($u['phone'] ?: '—') ?></dd><dt class="col-4 text-muted">Address</dt><dd class="col-8"><?= e($u['address'] ?: '—') ?></dd><dt class="col-4 text-muted">Joined</dt><dd class="col-8"><?= e($u['created_at']) ?></dd><dt class="col-4 text-muted">Last login</dt><dd class="col-8"><?= e($u['last_login_at'] ?: 'Never') ?></dd><dt class="col-4 text-muted">Orders</dt><dd class="col-8"><?= (int) $sum['c'] ?> · <?= format_price($sum['s']) ?> completed</dd></dl>
      <?php if ((int) $u['id'] !== (int) $me['id']): ?><hr class="border-secondary"><div class="d-flex flex-wrap gap-2">
        <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= $u['id'] ?>"><button class="btn btn-sm <?= $u['is_active'] ? 'btn-outline-danger' : 'btn-brand' ?>"><?= $u['is_active'] ? 'Deactivate' : 'Activate' ?></button></form>
        <form method="post" data-confirm="Change this user's role?"><?= csrf_field() ?><input type="hidden" name="action" value="role"><input type="hidden" name="id" value="<?= $u['id'] ?>"><input type="hidden" name="role" value="<?= $u['role'] === 'admin' ? 'customer' : 'admin' ?>"><button class="btn btn-sm btn-dark-soft"><?= $u['role'] === 'admin' ? 'Make customer' : 'Make admin' ?></button></form>
      </div><?php endif; ?>
    </div></div></div>
    <div class="col-lg-8"><div class="panel"><div class="panel-head">Order history</div><div class="table-responsive"><table class="table table-admin"><thead><tr><th>Order</th><th>Total</th><th>Status</th><th>Date</th><th></th></tr></thead><tbody>
      <?php if (!$orders): ?><tr><td colspan="5" class="text-center text-muted py-4">No orders.</td></tr><?php endif; ?>
      <?php foreach ($orders as $o): ?><tr><td>#<?= e($o['order_number']) ?></td><td><?= format_price($o['total']) ?></td><td><span class="<?= status_badge_class($o['status']) ?>"><?= e(status_label($o['status'])) ?></span></td><td class="small text-muted"><?= e($o['created_at']) ?></td><td class="text-end"><a class="btn btn-sm btn-outline-brand" href="<?= url('admin/orders.php?view=' . $o['id']) ?>">View</a></td></tr><?php endforeach; ?>
    </tbody></table></div></div></div></div>
    <?php require __DIR__ . '/includes/footer.php'; exit;
}
$q = clean_string($_GET['q'] ?? '', 100);
$role = in_array($_GET['role'] ?? '', ['customer', 'admin'], true) ? $_GET['role'] : '';
$sort = in_array($_GET['sort'] ?? '', ['name', 'email', 'created_at', 'is_active'], true) ? $_GET['sort'] : 'created_at';
$dir = ($_GET['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
$where = []; $params = [];
if ($q !== '') { $where[] = '(u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)'; array_push($params, "%$q%", "%$q%", "%$q%"); }
if ($role) { $where[] = 'u.role = ?'; $params[] = $role; }
$w = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$cnt = $pdo->prepare("SELECT COUNT(*) FROM users u $w"); $cnt->execute($params);
$pg = paginate((int) $cnt->fetchColumn(), 20, max(1, to_int($_GET['page'] ?? 1, 1)));
$stmt = $pdo->prepare("SELECT u.*, (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) order_count, (SELECT COALESCE(SUM(total),0) FROM orders o WHERE o.user_id = u.id AND o.status = 'completed') spent FROM users u $w ORDER BY u.$sort $dir LIMIT {$pg['per_page']} OFFSET {$pg['offset']}");
$stmt->execute($params); $rows = $stmt->fetchAll();
$query = ['q' => $q ?: null, 'role' => $role ?: null, 'sort' => $sort, 'dir' => $dir];
require __DIR__ . '/includes/header.php'; ?>
<div class="panel"><div class="panel-head"><form class="toolbar" method="get"><input name="q" class="form-control" placeholder="Name, email, phone" value="<?= e($q) ?>"><select name="role" class="form-select" onchange="this.form.submit()"><option value="">All roles</option><option value="customer" <?= $role === 'customer' ? 'selected' : '' ?>>Customers</option><option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admins</option></select><button class="btn btn-sm btn-outline-brand">Search</button></form><span class="text-muted small"><?= $pg['total'] ?> accounts</span></div>
  <div class="table-responsive"><table class="table table-admin"><thead><tr><th><?= sort_link('Name', 'name', $sort, $dir, $query) ?></th><th><?= sort_link('Email', 'email', $sort, $dir, $query) ?></th><th>Phone</th><th>Role</th><th>Orders</th><th>Spent</th><th><?= sort_link('Status', 'is_active', $sort, $dir, $query) ?></th><th><?= sort_link('Joined', 'created_at', $sort, $dir, $query) ?></th><th class="text-end">Actions</th></tr></thead><tbody>
    <?php if (!$rows): ?><tr><td colspan="9" class="text-center text-muted py-5">No accounts found.</td></tr><?php endif; ?>
    <?php foreach ($rows as $u): ?><tr><td><strong><?= e($u['name']) ?></strong></td><td><?= e($u['email']) ?></td><td><?= e($u['phone'] ?: '—') ?></td><td><span class="badge <?= $u['role'] === 'admin' ? 'bg-brand' : 'bg-secondary' ?>"><?= e($u['role']) ?></span></td><td><?= (int) $u['order_count'] ?></td><td><?= format_price($u['spent']) ?></td><td><?= bool_badge($u['is_active']) ?></td><td class="small text-muted"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
      <td class="text-end text-nowrap"><a class="btn btn-sm btn-outline-brand" href="<?= url('admin/customers.php?view=' . $u['id']) ?>"><i class="bi bi-eye"></i></a>
        <?php if ((int) $u['id'] !== (int) $me['id']): ?> <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= $u['id'] ?>"><button class="btn btn-sm <?= $u['is_active'] ? 'btn-outline-danger' : 'btn-brand' ?>" title="<?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>"><i class="bi <?= $u['is_active'] ? 'bi-person-x' : 'bi-person-check' ?>"></i></button></form> <?= admin_delete_form('delete', (int) $u['id'], 'Delete this account permanently?') ?><?php endif; ?></td></tr><?php endforeach; ?>
  </tbody></table></div><div class="px-3"><?= pagination_links($pg, $query) ?></div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
