<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/config.php';
require_admin();
require __DIR__ . '/includes/helpers.php';
$pageTitle = 'Coupons';
$pdo = db();
if (is_post()) {
    verify_csrf();
    $id = to_int($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $d = [
            'code' => strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', clean_string($_POST['code'] ?? '', 40)) ?? ''),
            'description' => clean_string($_POST['description'] ?? '', 255),
            'type' => ($_POST['type'] ?? 'percent') === 'fixed' ? 'fixed' : 'percent',
            'value' => to_decimal($_POST['value'] ?? 0),
            'min_order' => to_decimal($_POST['min_order'] ?? 0),
            'max_discount' => to_decimal($_POST['max_discount'] ?? 0) ?: null,
            'usage_limit' => to_int($_POST['usage_limit'] ?? 0) ?: null,
            'per_user_limit' => to_int($_POST['per_user_limit'] ?? 0) ?: null,
            'first_order_only' => isset($_POST['first_order_only']) ? 1 : 0,
            'starts_at' => clean_string($_POST['starts_at'] ?? '', 20) ?: null,
            'expires_at' => clean_string($_POST['expires_at'] ?? '', 20) ?: null,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];
        $err = '';
        if (strlen($d['code']) < 3) $err = 'Coupon code must be at least 3 characters (letters/numbers).';
        elseif ($d['value'] <= 0) $err = 'Discount value must be greater than zero.';
        elseif ($d['type'] === 'percent' && $d['value'] > 100) $err = 'Percentage cannot exceed 100.';
        elseif ($d['min_order'] < 0) $err = 'Minimum order cannot be negative.';
        else {
            $dup = $pdo->prepare('SELECT id FROM coupons WHERE code = ? AND id <> ?'); $dup->execute([$d['code'], $id]);
            if ($dup->fetch()) $err = 'That coupon code already exists.';
        }
        if ($err) { flash('danger', $err); remember_old($_POST); redirect(url('admin/coupons.php' . ($id ? '?edit=' . $id : ''))); }
        $vals = [$d['code'], $d['description'], $d['type'], $d['value'], $d['min_order'], $d['max_discount'], $d['usage_limit'], $d['per_user_limit'], $d['first_order_only'], $d['starts_at'], $d['expires_at'], $d['is_active']];
        if ($id) { $pdo->prepare('UPDATE coupons SET code=?, description=?, type=?, value=?, min_order=?, max_discount=?, usage_limit=?, per_user_limit=?, first_order_only=?, starts_at=?, expires_at=?, is_active=? WHERE id=?')->execute([...$vals, $id]); flash('success', 'Coupon updated.'); }
        else { $pdo->prepare('INSERT INTO coupons (code, description, type, value, min_order, max_discount, usage_limit, per_user_limit, first_order_only, starts_at, expires_at, is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')->execute($vals); flash('success', 'Coupon created.'); }
    } elseif ($action === 'toggle' && $id) { $pdo->prepare('UPDATE coupons SET is_active = 1 - is_active WHERE id = ?')->execute([$id]); flash('success', 'Coupon status updated.'); }
    elseif ($action === 'delete' && $id) { $pdo->prepare('DELETE FROM coupons WHERE id = ?')->execute([$id]); flash('success', 'Coupon deleted. Existing orders keep their recorded discount.'); }
    redirect(url('admin/coupons.php'));
}
$edit = null;
if (to_int($_GET['edit'] ?? 0)) { $s = $pdo->prepare('SELECT * FROM coupons WHERE id = ?'); $s->execute([to_int($_GET['edit'])]); $edit = $s->fetch(); }
$rows = $pdo->query('SELECT * FROM coupons ORDER BY created_at DESC')->fetchAll();
$dt = fn(?string $v) => $v ? date('Y-m-d\TH:i', strtotime($v)) : '';
require __DIR__ . '/includes/header.php'; ?>
<div class="row g-3">
  <div class="col-lg-4"><div class="panel"><div class="panel-head"><?= $edit ? 'Edit coupon' : 'New coupon' ?><?php if ($edit): ?><a href="<?= url('admin/coupons.php') ?>" class="btn btn-sm btn-dark-soft">Cancel</a><?php endif; ?></div><div class="panel-body">
    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
      <div class="mb-2"><label class="form-label">Code *</label><input name="code" class="form-control text-uppercase" required value="<?= e(old('code', $edit['code'] ?? '')) ?>"></div>
      <div class="mb-2"><label class="form-label">Description</label><input name="description" class="form-control" value="<?= e(old('description', $edit['description'] ?? '')) ?>"></div>
      <div class="row g-2 mb-2"><div class="col-6"><label class="form-label">Type</label><select name="type" class="form-select"><option value="percent" <?= old('type', $edit['type'] ?? '') === 'percent' ? 'selected' : '' ?>>Percent %</option><option value="fixed" <?= old('type', $edit['type'] ?? '') === 'fixed' ? 'selected' : '' ?>>Fixed amount</option></select></div><div class="col-6"><label class="form-label">Value *</label><input type="number" step="0.01" min="0" name="value" class="form-control" required value="<?= e(old('value', isset($edit['value']) ? (string) (float) $edit['value'] : '')) ?>"></div></div>
      <div class="row g-2 mb-2"><div class="col-6"><label class="form-label">Min order</label><input type="number" step="1" min="0" name="min_order" class="form-control" value="<?= e(old('min_order', isset($edit['min_order']) ? (string) (float) $edit['min_order'] : '0')) ?>"></div><div class="col-6"><label class="form-label">Max discount</label><input type="number" step="1" min="0" name="max_discount" class="form-control" value="<?= e(old('max_discount', isset($edit['max_discount']) ? (string) (float) $edit['max_discount'] : '')) ?>" placeholder="No cap"></div></div>
      <div class="row g-2 mb-2"><div class="col-6"><label class="form-label">Usage limit</label><input type="number" min="0" name="usage_limit" class="form-control" value="<?= e(old('usage_limit', (string) ($edit['usage_limit'] ?? ''))) ?>" placeholder="Unlimited"></div><div class="col-6"><label class="form-label">Per user limit</label><input type="number" min="0" name="per_user_limit" class="form-control" value="<?= e(old('per_user_limit', (string) ($edit['per_user_limit'] ?? ''))) ?>" placeholder="Unlimited"></div></div>
      <div class="row g-2 mb-2"><div class="col-6"><label class="form-label">Starts</label><input type="datetime-local" name="starts_at" class="form-control" value="<?= e(old('starts_at', $dt($edit['starts_at'] ?? null))) ?>"></div><div class="col-6"><label class="form-label">Expires</label><input type="datetime-local" name="expires_at" class="form-control" value="<?= e(old('expires_at', $dt($edit['expires_at'] ?? null))) ?>"></div></div>
      <div class="d-flex gap-4 mb-3"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="first_order_only" id="fo" <?= ($edit['first_order_only'] ?? 0) ? 'checked' : '' ?>><label class="form-check-label" for="fo">First order only</label></div><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" id="ca" <?= ($edit['is_active'] ?? 1) ? 'checked' : '' ?>><label class="form-check-label" for="ca">Active</label></div></div>
      <button class="btn btn-brand w-100 justify-content-center"><i class="bi bi-save"></i> Save coupon</button>
    </form></div></div></div>
  <div class="col-lg-8"><div class="panel"><div class="panel-head">Coupons (<?= count($rows) ?>)</div><div class="table-responsive"><table class="table table-admin"><thead><tr><th>Code</th><th>Discount</th><th>Rules</th><th>Used</th><th>Validity</th><th>Status</th><th class="text-end">Actions</th></tr></thead><tbody>
    <?php if (!$rows): ?><tr><td colspan="7" class="text-center text-muted py-4">No coupons yet.</td></tr><?php endif; ?>
    <?php foreach ($rows as $c): ?><tr><td><strong class="text-brand"><?= e($c['code']) ?></strong><div class="text-muted small"><?= e($c['description']) ?></div></td><td><?= $c['type'] === 'percent' ? (float) $c['value'] . '%' . ($c['max_discount'] ? ' (max ' . format_price($c['max_discount']) . ')' : '') : format_price($c['value']) ?></td><td class="small text-muted">Min <?= format_price($c['min_order']) ?><?= $c['first_order_only'] ? '<br>First order only' : '' ?><?= $c['per_user_limit'] ? '<br>' . (int) $c['per_user_limit'] . '/user' : '' ?></td><td><?= (int) $c['used_count'] ?><?= $c['usage_limit'] ? ' / ' . (int) $c['usage_limit'] : '' ?></td><td class="small text-muted"><?= $c['starts_at'] ? 'From ' . date('M j, Y', strtotime($c['starts_at'])) . '<br>' : '' ?><?= $c['expires_at'] ? 'Until ' . date('M j, Y', strtotime($c['expires_at'])) : 'No expiry' ?></td><td><form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= $c['id'] ?>"><button class="btn btn-link p-0 text-decoration-none"><?= bool_badge($c['is_active']) ?></button></form></td><td class="text-end text-nowrap"><a class="btn btn-sm btn-outline-brand" href="<?= url('admin/coupons.php?edit=' . $c['id']) ?>"><i class="bi bi-pencil"></i></a> <?= admin_delete_form('delete', (int) $c['id'], 'Delete coupon ' . $c['code'] . '?') ?></td></tr><?php endforeach; ?>
  </tbody></table></div></div></div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
