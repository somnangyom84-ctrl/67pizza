<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/config.php';
require_admin();
require __DIR__ . '/includes/helpers.php';
$pageTitle = 'Banners & Offers';
$pdo = db();
if (is_post()) {
    verify_csrf();
    $id = to_int($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $title = clean_string($_POST['title'] ?? '', 150);
        $subtitle = clean_string($_POST['subtitle'] ?? '', 255);
        $highlight = clean_string($_POST['highlight'] ?? '', 100);
        $btnText = clean_string($_POST['button_text'] ?? 'Order Now', 60);
        $btnLink = clean_string($_POST['button_link'] ?? 'menu.php', 255);
        $position = ($_POST['position'] ?? 'hero') === 'offer' ? 'offer' : 'hero';
        $sort = to_int($_POST['sort_order'] ?? 0);
        $active = isset($_POST['is_active']) ? 1 : 0;
        if (mb_strlen($title) < 2) { flash('danger', 'Title is required.'); redirect(url('admin/banners.php')); }
        if (preg_match('#^(https?:)?//#i', $btnLink) || str_contains($btnLink, '..')) { flash('danger', 'Button link must be a relative page like menu.php.'); redirect(url('admin/banners.php')); }
        try { $img = handle_image_upload($_FILES['image'] ?? [], 'banners'); } catch (RuntimeException $ex) { flash('danger', $ex->getMessage()); redirect(url('admin/banners.php' . ($id ? '?edit=' . $id : ''))); }
        if ($id) {
            $old = $pdo->prepare('SELECT image FROM banners WHERE id = ?'); $old->execute([$id]); $oldImg = $old->fetchColumn();
            $pdo->prepare('UPDATE banners SET title=?, subtitle=?, highlight=?, button_text=?, button_link=?, position=?, sort_order=?, is_active=?' . ($img ? ', image=?' : '') . ' WHERE id=?')
                ->execute(array_merge([$title, $subtitle, $highlight, $btnText, $btnLink, $position, $sort, $active], $img ? [$img] : [], [$id]));
            if ($img && $oldImg) delete_upload($oldImg);
            flash('success', 'Banner updated.');
        } else {
            $pdo->prepare('INSERT INTO banners (title, subtitle, highlight, image, button_text, button_link, position, sort_order, is_active) VALUES (?,?,?,?,?,?,?,?,?)')->execute([$title, $subtitle, $highlight, $img, $btnText, $btnLink, $position, $sort, $active]);
            flash('success', 'Banner created.');
        }
    } elseif ($action === 'toggle' && $id) { $pdo->prepare('UPDATE banners SET is_active = 1 - is_active WHERE id = ?')->execute([$id]); flash('success', 'Banner status updated.'); }
    elseif ($action === 'delete' && $id) { $r = $pdo->prepare('SELECT image FROM banners WHERE id = ?'); $r->execute([$id]); $img = $r->fetchColumn(); $pdo->prepare('DELETE FROM banners WHERE id = ?')->execute([$id]); delete_upload($img ?: null); flash('success', 'Banner deleted.'); }
    redirect(url('admin/banners.php'));
}
$edit = null;
if (to_int($_GET['edit'] ?? 0)) { $s = $pdo->prepare('SELECT * FROM banners WHERE id = ?'); $s->execute([to_int($_GET['edit'])]); $edit = $s->fetch(); }
$rows = $pdo->query('SELECT * FROM banners ORDER BY position, sort_order, id DESC')->fetchAll();
require __DIR__ . '/includes/header.php'; ?>
<div class="row g-3">
  <div class="col-lg-4"><div class="panel"><div class="panel-head"><?= $edit ? 'Edit banner' : 'Add banner' ?><?php if ($edit): ?><a href="<?= url('admin/banners.php') ?>" class="btn btn-sm btn-dark-soft">Cancel</a><?php endif; ?></div><div class="panel-body">
    <form method="post" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
      <div class="mb-2"><label class="form-label">Position</label><select name="position" class="form-select"><option value="hero" <?= ($edit['position'] ?? '') === 'hero' ? 'selected' : '' ?>>Hero (top banner)</option><option value="offer" <?= ($edit['position'] ?? '') === 'offer' ? 'selected' : '' ?>>Special offer</option></select></div>
      <div class="mb-2"><label class="form-label">Highlight (script text)</label><input name="highlight" class="form-control" value="<?= e($edit['highlight'] ?? '') ?>" placeholder="Delicious Food / Special Offer"></div>
      <div class="mb-2"><label class="form-label">Title *</label><input name="title" class="form-control" required value="<?= e($edit['title'] ?? '') ?>" placeholder="Get 20% OFF"></div>
      <div class="mb-2"><label class="form-label">Subtitle</label><input name="subtitle" class="form-control" value="<?= e($edit['subtitle'] ?? '') ?>"></div>
      <div class="row g-2 mb-2"><div class="col-6"><label class="form-label">Button text</label><input name="button_text" class="form-control" value="<?= e($edit['button_text'] ?? 'Order Now') ?>"></div><div class="col-6"><label class="form-label">Button link</label><input name="button_link" class="form-control" value="<?= e($edit['button_link'] ?? 'menu.php') ?>"></div></div>
      <div class="mb-2"><label class="form-label">Image</label><input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp" data-preview="#bPrev"><img id="bPrev" class="img-preview" src="<?= $edit && $edit['image'] ? e(image_url($edit['image'])) : '' ?>" alt="" <?= $edit && $edit['image'] ? 'style="display:block"' : '' ?>></div>
      <div class="row g-2 mb-3"><div class="col-6"><label class="form-label">Sort order</label><input type="number" name="sort_order" class="form-control" value="<?= (int) ($edit['sort_order'] ?? 0) ?>"></div><div class="col-6 d-flex align-items-end"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" id="ba" <?= ($edit['is_active'] ?? 1) ? 'checked' : '' ?>><label class="form-check-label" for="ba">Active</label></div></div></div>
      <button class="btn btn-brand w-100 justify-content-center"><i class="bi bi-save"></i> Save banner</button>
    </form></div></div></div>
  <div class="col-lg-8"><div class="panel"><div class="panel-head">Banners (<?= count($rows) ?>)<span class="text-muted small fw-normal">The first active banner per position is shown on the homepage.</span></div><div class="table-responsive"><table class="table table-admin"><thead><tr><th>Image</th><th>Title</th><th>Position</th><th>Order</th><th>Status</th><th class="text-end">Actions</th></tr></thead><tbody>
    <?php if (!$rows): ?><tr><td colspan="6" class="text-center text-muted py-4">No banners yet.</td></tr><?php endif; ?>
    <?php foreach ($rows as $b): ?><tr><td><img class="thumb" style="width:80px" src="<?= e(image_url($b['image'])) ?>" alt=""></td><td><strong><?= e($b['title']) ?></strong><div class="text-muted small"><?= e($b['subtitle']) ?></div></td><td><span class="badge bg-brand"><?= e($b['position']) ?></span></td><td><?= (int) $b['sort_order'] ?></td><td><form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= $b['id'] ?>"><button class="btn btn-link p-0 text-decoration-none"><?= bool_badge($b['is_active']) ?></button></form></td><td class="text-end text-nowrap"><a class="btn btn-sm btn-outline-brand" href="<?= url('admin/banners.php?edit=' . $b['id']) ?>"><i class="bi bi-pencil"></i></a> <?= admin_delete_form('delete', (int) $b['id'], 'Delete this banner?') ?></td></tr><?php endforeach; ?>
  </tbody></table></div></div></div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
