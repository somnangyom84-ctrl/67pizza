<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/config.php';
require_admin();
require __DIR__ . '/includes/helpers.php';
$pageTitle = 'Restaurants';
$pdo = db();
if (is_post()) {
    verify_csrf();
    $id = to_int($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $name = clean_string($_POST['name'] ?? '', 120);
        $desc = clean_string($_POST['description'] ?? '', 2000);
        $rating = min(5, max(0, to_decimal($_POST['rating'] ?? 0)));
        $ratingCount = max(0, to_int($_POST['rating_count'] ?? 0));
        $time = clean_string($_POST['delivery_time'] ?? '25-35 min', 40);
        $dist = ($_POST['distance_km'] ?? '') !== '' ? max(0, to_decimal($_POST['distance_km'])) : null;
        $phone = clean_string($_POST['phone'] ?? '', 30);
        $active = isset($_POST['is_active']) ? 1 : 0;
        $featured = isset($_POST['is_featured']) ? 1 : 0;
        if (mb_strlen($name) < 2) { flash('danger', 'Name must be at least 2 characters.'); redirect(url('admin/restaurants.php')); }
        if ($phone !== '' && !valid_phone($phone)) { flash('danger', 'Invalid phone number.'); redirect(url('admin/restaurants.php')); }
        try { $img = handle_image_upload($_FILES['image'] ?? [], 'banners'); } catch (RuntimeException $ex) { flash('danger', $ex->getMessage()); redirect(url('admin/restaurants.php' . ($id ? '?edit=' . $id : ''))); }
        $slug = unique_slug('restaurants', slugify($name), $id);
        if ($id) {
            $old = $pdo->prepare('SELECT image FROM restaurants WHERE id = ?'); $old->execute([$id]); $oldImg = $old->fetchColumn();
            $pdo->prepare('UPDATE restaurants SET name=?, slug=?, description=?, rating=?, rating_count=?, delivery_time=?, distance_km=?, phone=?, is_active=?, is_featured=?' . ($img ? ', image=?' : '') . ' WHERE id=?')
                ->execute(array_merge([$name, $slug, $desc, $rating, $ratingCount, $time, $dist, $phone ?: null, $active, $featured], $img ? [$img] : [], [$id]));
            if ($img && $oldImg) delete_upload($oldImg);
            flash('success', 'Restaurant updated.');
        } else {
            $pdo->prepare('INSERT INTO restaurants (name, slug, description, image, rating, rating_count, delivery_time, distance_km, phone, is_active, is_featured) VALUES (?,?,?,?,?,?,?,?,?,?,?)')->execute([$name, $slug, $desc, $img, $rating, $ratingCount, $time, $dist, $phone ?: null, $active, $featured]);
            flash('success', 'Restaurant created.');
        }
    } elseif ($action === 'delete' && $id) {
        $r = $pdo->prepare('SELECT image FROM restaurants WHERE id = ?'); $r->execute([$id]); $img = $r->fetchColumn();
        $pdo->prepare('DELETE FROM restaurants WHERE id = ?')->execute([$id]); // products.restaurant_id -> NULL
        delete_upload($img ?: null);
        flash('success', 'Restaurant deleted. Its products remain and are now unassigned.');
    }
    redirect(url('admin/restaurants.php'));
}
$edit = null;
if (to_int($_GET['edit'] ?? 0)) { $s = $pdo->prepare('SELECT * FROM restaurants WHERE id = ?'); $s->execute([to_int($_GET['edit'])]); $edit = $s->fetch(); }
$rows = $pdo->query('SELECT r.*, (SELECT COUNT(*) FROM products p WHERE p.restaurant_id = r.id) product_count FROM restaurants r ORDER BY r.is_featured DESC, r.name')->fetchAll();
require __DIR__ . '/includes/header.php'; ?>
<div class="row g-3">
  <div class="col-lg-4"><div class="panel"><div class="panel-head"><?= $edit ? 'Edit restaurant' : 'Add restaurant' ?><?php if ($edit): ?><a href="<?= url('admin/restaurants.php') ?>" class="btn btn-sm btn-dark-soft">Cancel</a><?php endif; ?></div><div class="panel-body">
    <form method="post" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
      <div class="mb-2"><label class="form-label">Name *</label><input name="name" class="form-control" required value="<?= e($edit['name'] ?? '') ?>"></div>
      <div class="mb-2"><label class="form-label">Description</label><textarea name="description" rows="3" class="form-control"><?= e($edit['description'] ?? '') ?></textarea></div>
      <div class="row g-2 mb-2"><div class="col-6"><label class="form-label">Rating (0–5)</label><input type="number" step="0.1" min="0" max="5" name="rating" class="form-control" value="<?= e((string) ($edit['rating'] ?? '4.5')) ?>"></div><div class="col-6"><label class="form-label">Rating count</label><input type="number" min="0" name="rating_count" class="form-control" value="<?= (int) ($edit['rating_count'] ?? 0) ?>"></div></div>
      <div class="row g-2 mb-2"><div class="col-6"><label class="form-label">Delivery time</label><input name="delivery_time" class="form-control" value="<?= e($edit['delivery_time'] ?? '25-35 min') ?>"></div><div class="col-6"><label class="form-label">Distance (km)</label><input type="number" step="0.1" min="0" name="distance_km" class="form-control" value="<?= e((string) ($edit['distance_km'] ?? '')) ?>"></div></div>
      <div class="mb-2"><label class="form-label">Phone</label><input name="phone" class="form-control" value="<?= e($edit['phone'] ?? '') ?>"></div>
      <div class="mb-2"><label class="form-label">Image</label><input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp" data-preview="#rPrev"><img id="rPrev" class="img-preview" src="<?= $edit && $edit['image'] ? e(image_url($edit['image'])) : '' ?>" alt="" <?= $edit && $edit['image'] ? 'style="display:block"' : '' ?>></div>
      <div class="d-flex gap-4 mb-3"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" id="ra" <?= ($edit['is_active'] ?? 1) ? 'checked' : '' ?>><label class="form-check-label" for="ra">Active</label></div><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_featured" id="rf" <?= ($edit['is_featured'] ?? 0) ? 'checked' : '' ?>><label class="form-check-label" for="rf">Featured</label></div></div>
      <button class="btn btn-brand w-100 justify-content-center"><i class="bi bi-save"></i> Save</button>
    </form></div></div></div>
  <div class="col-lg-8"><div class="panel"><div class="panel-head">Restaurants (<?= count($rows) ?>)</div><div class="table-responsive"><table class="table table-admin"><thead><tr><th>Image</th><th>Name</th><th>Rating</th><th>Delivery</th><th>Products</th><th>Status</th><th class="text-end">Actions</th></tr></thead><tbody>
    <?php if (!$rows): ?><tr><td colspan="7" class="text-center text-muted py-4">No restaurants yet.</td></tr><?php endif; ?>
    <?php foreach ($rows as $r): ?><tr><td><img class="thumb" src="<?= e(image_url($r['image'])) ?>" alt=""></td><td><strong><?= e($r['name']) ?></strong> <?= $r['is_featured'] ? '<i class="bi bi-star-fill text-brand small"></i>' : '' ?><div class="text-muted small"><?= e($r['slug']) ?></div></td><td><i class="bi bi-star-fill text-brand"></i> <?= e($r['rating']) ?> (<?= (int) $r['rating_count'] ?>)</td><td class="small"><?= e($r['delivery_time']) ?><?= $r['distance_km'] !== null ? ' · ' . e($r['distance_km']) . ' km' : '' ?></td><td><?= (int) $r['product_count'] ?></td><td><?= bool_badge($r['is_active']) ?></td><td class="text-end text-nowrap"><a class="btn btn-sm btn-outline-brand" href="<?= url('admin/restaurants.php?edit=' . $r['id']) ?>"><i class="bi bi-pencil"></i></a> <?= admin_delete_form('delete', (int) $r['id'], 'Delete "' . $r['name'] . '"?') ?></td></tr><?php endforeach; ?>
  </tbody></table></div></div></div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
