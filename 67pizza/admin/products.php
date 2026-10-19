<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/config.php';
require_admin();
require __DIR__ . '/includes/helpers.php';
$pageTitle = 'Products';
$pdo = db();
$categories = get_categories(false);
$restaurants = $pdo->query('SELECT id, name FROM restaurants ORDER BY name')->fetchAll();

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $id = to_int($_POST['id'] ?? 0);
    if ($action === 'save') {
        $data = [
            'name' => clean_string($_POST['name'] ?? '', 150),
            'slug' => slugify(clean_string($_POST['slug'] ?? '', 170) ?: clean_string($_POST['name'] ?? '', 150)),
            'category_id' => to_int($_POST['category_id'] ?? 0),
            'restaurant_id' => to_int($_POST['restaurant_id'] ?? 0) ?: null,
            'description' => clean_string($_POST['description'] ?? '', 3000),
            'price' => to_decimal($_POST['price'] ?? 0, -1),
            'is_available' => isset($_POST['is_available']) ? 1 : 0,
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
        ];
        $v = new Validator(['name' => $data['name'], 'category_id' => $data['category_id'], 'price' => $_POST['price'] ?? '']);
        $ok = $v->validate(['name' => 'required|min:2|max:150', 'category_id' => 'required|integer|min_value:1', 'price' => 'required|numeric|min_value:0']);
        $errors = $v->firstErrors();
        if ($data['price'] < 0) { $errors['price'] = 'Price must be zero or more.'; $ok = false; }
        $catCheck = $pdo->prepare('SELECT id FROM categories WHERE id = ?'); $catCheck->execute([$data['category_id']]);
        if (!$catCheck->fetch()) { $errors['category_id'] = 'Select a valid category.'; $ok = false; }
        $imagePath = null;
        try { $imagePath = handle_image_upload($_FILES['image'] ?? [], 'products'); } catch (RuntimeException $ex) { $errors['image'] = $ex->getMessage(); $ok = false; }
        if (!$ok) {
            set_form_errors($errors); remember_old($_POST); flash('danger', 'Please correct the errors below.');
            redirect(url('admin/products.php?action=' . ($id ? 'edit&id=' . $id : 'add')));
        }
        $data['slug'] = unique_slug('products', $data['slug'], $id);
        if ($id) {
            $old = get_product($id);
            if (!$old) { flash('danger', 'Product not found.'); redirect(url('admin/products.php')); }
            $pdo->prepare('UPDATE products SET name=?, slug=?, category_id=?, restaurant_id=?, description=?, price=?, is_available=?, is_featured=?' . ($imagePath ? ', image=?' : '') . ' WHERE id=?')
                ->execute(array_merge([$data['name'], $data['slug'], $data['category_id'], $data['restaurant_id'], $data['description'], $data['price'], $data['is_available'], $data['is_featured']], $imagePath ? [$imagePath] : [], [$id]));
            if ($imagePath && $old['image'] !== $imagePath) delete_upload($old['image']);
            flash('success', 'Product updated.');
        } else {
            $pdo->prepare('INSERT INTO products (name, slug, category_id, restaurant_id, description, price, image, is_available, is_featured) VALUES (?,?,?,?,?,?,?,?,?)')
                ->execute([$data['name'], $data['slug'], $data['category_id'], $data['restaurant_id'], $data['description'], $data['price'], $imagePath, $data['is_available'], $data['is_featured']]);
            flash('success', 'Product created.');
        }
        redirect(url('admin/products.php'));
    }
    if ($action === 'toggle' && $id) {
        $field = ($_POST['field'] ?? '') === 'is_featured' ? 'is_featured' : 'is_available';
        $pdo->prepare("UPDATE products SET $field = 1 - $field WHERE id = ?")->execute([$id]);
        flash('success', 'Product updated.');
        redirect($_SERVER['HTTP_REFERER'] ?? url('admin/products.php'));
    }
    if ($action === 'delete' && $id) {
        $p = get_product($id);
        if ($p) {
            // Keep order history intact (order_items.product_id becomes NULL via FK); delete product, cart & favorites cascade.
            $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
            delete_upload($p['image']);
            flash('success', 'Product "' . $p['name'] . '" deleted. Past orders keep their item history.');
        }
        redirect(url('admin/products.php'));
    }
}

$mode = $_GET['action'] ?? 'list';
if ($mode === 'add' || $mode === 'edit') {
    $product = $mode === 'edit' ? get_product(to_int($_GET['id'] ?? 0)) : null;
    if ($mode === 'edit' && !$product) { flash('danger', 'Product not found.'); redirect(url('admin/products.php')); }
    $errors = form_errors();
    $pageTitle = $product ? 'Edit Product' : 'Add Product';
    require __DIR__ . '/includes/header.php'; ?>
    <div class="panel" style="max-width:960px"><div class="panel-head"><?= e($pageTitle) ?><a href="<?= url('admin/products.php') ?>" class="btn btn-sm btn-dark-soft"><i class="bi bi-arrow-left"></i> Back</a></div>
    <div class="panel-body"><form method="post" enctype="multipart/form-data" class="needs-validation" novalidate><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int) ($product['id'] ?? 0) ?>">
      <div class="row g-3">
        <div class="col-md-8">
          <div class="row g-3">
            <div class="col-md-7"><label class="form-label">Product name *</label><input name="name" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" value="<?= e(old('name', $product['name'] ?? '')) ?>" required data-slug-source><?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= e($errors['name']) ?></div><?php endif; ?></div>
            <div class="col-md-5"><label class="form-label">Slug</label><input name="slug" class="form-control" value="<?= e(old('slug', $product['slug'] ?? '')) ?>" data-slug-target <?= $product ? 'data-touched="1"' : '' ?>></div>
            <div class="col-md-6"><label class="form-label">Category *</label><select name="category_id" class="form-select <?= isset($errors['category_id']) ? 'is-invalid' : '' ?>" required><option value="">Select…</option><?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>" <?= (int) old('category_id', (string) ($product['category_id'] ?? '')) === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select><?php if (isset($errors['category_id'])): ?><div class="invalid-feedback"><?= e($errors['category_id']) ?></div><?php endif; ?></div>
            <div class="col-md-6"><label class="form-label">Restaurant</label><select name="restaurant_id" class="form-select"><option value="">— None —</option><?php foreach ($restaurants as $r): ?><option value="<?= $r['id'] ?>" <?= (int) old('restaurant_id', (string) ($product['restaurant_id'] ?? '')) === (int) $r['id'] ? 'selected' : '' ?>><?= e($r['name']) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-6"><label class="form-label">Price (<?= e(setting('currency_code', 'KHR')) ?>) *</label><input type="number" step="0.01" min="0" name="price" class="form-control <?= isset($errors['price']) ? 'is-invalid' : '' ?>" value="<?= e(old('price', isset($product['price']) ? (string) (float) $product['price'] : '')) ?>" required><?php if (isset($errors['price'])): ?><div class="invalid-feedback"><?= e($errors['price']) ?></div><?php endif; ?></div>
            <div class="col-md-6 d-flex align-items-end gap-4"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_available" id="avail" <?= old('is_available', isset($product) ? (string) $product['is_available'] : '1') ? 'checked' : '' ?>><label class="form-check-label" for="avail">Available</label></div><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_featured" id="feat" <?= old('is_featured', (string) ($product['is_featured'] ?? '0')) ? 'checked' : '' ?>><label class="form-check-label" for="feat">Featured</label></div></div>
            <div class="col-12"><label class="form-label">Description</label><textarea name="description" rows="5" class="form-control"><?= e(old('description', $product['description'] ?? '')) ?></textarea></div>
          </div>
        </div>
        <div class="col-md-4">
          <label class="form-label">Product image</label>
          <img class="thumb-lg d-block mb-2" id="imgPreview" src="<?= e(image_url($product['image'] ?? null)) ?>" alt="">
          <input type="file" name="image" class="form-control <?= isset($errors['image']) ? 'is-invalid' : '' ?>" accept="image/jpeg,image/png,image/webp" data-preview="#imgPreview"><?php if (isset($errors['image'])): ?><div class="invalid-feedback"><?= e($errors['image']) ?></div><?php endif; ?>
          <div class="form-text text-muted">JPG, PNG or WEBP, max 2 MB. Leave empty to keep the current image.</div>
        </div>
        <div class="col-12"><button class="btn btn-brand"><i class="bi bi-save"></i> Save product</button></div>
      </div></form></div></div>
    <?php require __DIR__ . '/includes/footer.php'; exit;
}

// ---- List ----
$q = clean_string($_GET['q'] ?? '', 100);
$cat = to_int($_GET['category'] ?? 0);
$sort = in_array($_GET['sort'] ?? '', ['name', 'price', 'created_at', 'sold_count', 'is_available'], true) ? $_GET['sort'] : 'created_at';
$dir = ($_GET['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
$where = []; $params = [];
if ($q !== '') { $where[] = '(p.name LIKE ? OR p.description LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
if ($cat) { $where[] = 'p.category_id = ?'; $params[] = $cat; }
$w = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$cnt = $pdo->prepare("SELECT COUNT(*) FROM products p $w"); $cnt->execute($params);
$pg = paginate((int) $cnt->fetchColumn(), 15, max(1, to_int($_GET['page'] ?? 1, 1)));
$stmt = $pdo->prepare("SELECT p.*, c.name AS category_name FROM products p INNER JOIN categories c ON c.id = p.category_id $w ORDER BY p.$sort $dir LIMIT {$pg['per_page']} OFFSET {$pg['offset']}");
$stmt->execute($params);
$rows = $stmt->fetchAll();
$query = ['q' => $q ?: null, 'category' => $cat ?: null, 'sort' => $sort, 'dir' => $dir];
require __DIR__ . '/includes/header.php'; ?>
<div class="panel">
  <div class="panel-head">
    <form class="toolbar" method="get"><input name="q" class="form-control" placeholder="Search products…" value="<?= e($q) ?>"><select name="category" class="form-select" onchange="this.form.submit()"><option value="">All categories</option><?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>" <?= $cat === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select><button class="btn btn-sm btn-outline-brand">Search</button></form>
    <a href="<?= url('admin/products.php?action=add') ?>" class="btn btn-sm btn-brand"><i class="bi bi-plus-lg"></i> Add product</a>
  </div>
  <div class="table-responsive"><table class="table table-admin"><thead><tr><th>Image</th><th><?= sort_link('Name', 'name', $sort, $dir, $query) ?></th><th>Category</th><th><?= sort_link('Price', 'price', $sort, $dir, $query) ?></th><th><?= sort_link('Sold', 'sold_count', $sort, $dir, $query) ?></th><th><?= sort_link('Status', 'is_available', $sort, $dir, $query) ?></th><th>Featured</th><th class="text-end">Actions</th></tr></thead><tbody>
    <?php if (!$rows): ?><tr><td colspan="8" class="text-center text-muted py-5">No products found.</td></tr><?php endif; ?>
    <?php foreach ($rows as $p): ?>
    <tr>
      <td><img class="thumb" src="<?= e(image_url($p['image'])) ?>" alt=""></td>
      <td><strong><?= e($p['name']) ?></strong><div class="text-muted small"><?= e($p['slug']) ?></div></td>
      <td><?= e($p['category_name']) ?></td>
      <td class="text-brand fw-semibold"><?= format_price($p['price']) ?></td>
      <td><?= (int) $p['sold_count'] ?></td>
      <td><form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="field" value="is_available"><input type="hidden" name="id" value="<?= $p['id'] ?>"><button class="btn btn-link p-0 text-decoration-none" title="Toggle availability"><?= bool_badge($p['is_available'], 'Available', 'Unavailable') ?></button></form></td>
      <td><form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="field" value="is_featured"><input type="hidden" name="id" value="<?= $p['id'] ?>"><button class="btn btn-link p-0 fs-5 <?= $p['is_featured'] ? 'text-brand' : 'text-muted' ?>" title="Toggle featured"><i class="bi <?= $p['is_featured'] ? 'bi-star-fill' : 'bi-star' ?>"></i></button></form></td>
      <td class="text-end text-nowrap"><a class="btn btn-sm btn-dark-soft" href="<?= url('product.php?slug=' . urlencode($p['slug'])) ?>" target="_blank" title="View"><i class="bi bi-eye"></i></a> <a class="btn btn-sm btn-outline-brand" href="<?= url('admin/products.php?action=edit&id=' . $p['id']) ?>" title="Edit"><i class="bi bi-pencil"></i></a> <?= admin_delete_form('delete', (int) $p['id'], 'Delete "' . $p['name'] . '"? It will be removed from carts and favorites; past orders are kept.') ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody></table></div>
  <div class="px-3"><?= pagination_links($pg, $query) ?></div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
