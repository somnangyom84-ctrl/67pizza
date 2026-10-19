<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/config.php';
require_admin();
require __DIR__ . '/includes/helpers.php';
$pageTitle = 'Categories';
$pdo = db();
if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $id = to_int($_POST['id'] ?? 0);
    if ($action === 'save') {
        $name = clean_string($_POST['name'] ?? '', 100);
        $icon = clean_string($_POST['icon'] ?? 'bi-egg-fried', 60);
        $desc = clean_string($_POST['description'] ?? '', 255);
        $sort = to_int($_POST['sort_order'] ?? 0);
        $active = isset($_POST['is_active']) ? 1 : 0;
        if (mb_strlen($name) < 2) { flash('danger', 'Category name must be at least 2 characters.'); redirect(url('admin/categories.php')); }
        if (!preg_match('/^bi-[a-z0-9-]+$/', $icon)) $icon = 'bi-egg-fried';
        $slug = unique_slug('categories', slugify($name), $id);
        if ($id) {
            $pdo->prepare('UPDATE categories SET name=?, slug=?, icon=?, description=?, sort_order=?, is_active=? WHERE id=?')->execute([$name, $slug, $icon, $desc, $sort, $active, $id]);
            flash('success', 'Category updated.');
        } else {
            $pdo->prepare('INSERT INTO categories (name, slug, icon, description, sort_order, is_active) VALUES (?,?,?,?,?,?)')->execute([$name, $slug, $icon, $desc, $sort, $active]);
            flash('success', 'Category created.');
        }
    } elseif ($action === 'delete' && $id) {
        $c = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?'); $c->execute([$id]);
        if ((int) $c->fetchColumn() > 0) {
            flash('danger', 'Cannot delete a category that still has products. Move or delete its products first.');
        } else {
            $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
            flash('success', 'Category deleted.');
        }
    }
    redirect(url('admin/categories.php'));
}
$edit = null;
if (to_int($_GET['edit'] ?? 0)) { $s = $pdo->prepare('SELECT * FROM categories WHERE id = ?'); $s->execute([to_int($_GET['edit'])]); $edit = $s->fetch(); }
$rows = get_categories(false);
require __DIR__ . '/includes/header.php'; ?>
<div class="row g-3">
  <div class="col-lg-4"><div class="panel"><div class="panel-head"><?= $edit ? 'Edit category' : 'Add category' ?><?php if ($edit): ?><a href="<?= url('admin/categories.php') ?>" class="btn btn-sm btn-dark-soft">Cancel</a><?php endif; ?></div><div class="panel-body">
    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
      <div class="mb-3"><label class="form-label">Name *</label><input name="name" class="form-control" required value="<?= e($edit['name'] ?? '') ?>"></div>
      <div class="mb-3"><label class="form-label">Bootstrap icon class</label><input name="icon" class="form-control" value="<?= e($edit['icon'] ?? 'bi-egg-fried') ?>" placeholder="bi-cup-straw"><div class="form-text text-muted">e.g. bi-shop, bi-pie-chart, bi-cup-straw, bi-cake2 (<a href="https://icons.getbootstrap.com/" target="_blank" rel="noopener">browse icons</a>)</div></div>
      <div class="mb-3"><label class="form-label">Description</label><input name="description" class="form-control" value="<?= e($edit['description'] ?? '') ?>"></div>
      <div class="row g-2 mb-3"><div class="col-6"><label class="form-label">Sort order</label><input type="number" name="sort_order" class="form-control" value="<?= (int) ($edit['sort_order'] ?? 0) ?>"></div><div class="col-6 d-flex align-items-end"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" id="cact" <?= ($edit['is_active'] ?? 1) ? 'checked' : '' ?>><label class="form-check-label" for="cact">Active</label></div></div></div>
      <button class="btn btn-brand w-100 justify-content-center"><i class="bi bi-save"></i> Save</button>
    </form></div></div></div>
  <div class="col-lg-8"><div class="panel"><div class="panel-head">All categories (<?= count($rows) ?>)</div>
    <div class="table-responsive"><table class="table table-admin"><thead><tr><th>Icon</th><th>Name</th><th>Slug</th><th>Products</th><th>Order</th><th>Status</th><th class="text-end">Actions</th></tr></thead><tbody>
      <?php if (!$rows): ?><tr><td colspan="7" class="text-center text-muted py-4">No categories yet.</td></tr><?php endif; ?>
      <?php foreach ($rows as $c): ?><tr><td><i class="bi <?= e($c['icon']) ?> text-brand fs-4"></i></td><td><strong><?= e($c['name']) ?></strong><div class="text-muted small"><?= e($c['description']) ?></div></td><td class="text-muted"><?= e($c['slug']) ?></td><td><?= (int) $c['product_count'] ?></td><td><?= (int) $c['sort_order'] ?></td><td><?= bool_badge($c['is_active']) ?></td><td class="text-end text-nowrap"><a class="btn btn-sm btn-outline-brand" href="<?= url('admin/categories.php?edit=' . $c['id']) ?>"><i class="bi bi-pencil"></i></a> <?= admin_delete_form('delete', (int) $c['id'], 'Delete category "' . $c['name'] . '"?') ?></td></tr><?php endforeach; ?>
    </tbody></table></div></div></div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
