<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
$slug = clean_string($_GET['slug'] ?? '', 120);
$category = $slug !== '' ? get_category_by_slug($slug) : null;
if (!$category) {
    http_response_code(404);
    $pageTitle = 'Category not found';
    require ROOT_PATH . '/includes/header.php';
    echo '<div class="container"><div class="empty-state"><i class="bi bi-emoji-frown"></i><h4>Category not found</h4><a class="btn btn-brand mt-2" href="' . url('menu.php') . '">Browse menu</a></div></div>';
    require ROOT_PATH . '/includes/footer.php';
    exit;
}
$pageTitle = $category['name'];
$sort = in_array($_GET['sort'] ?? '', ['popular', 'newest', 'price_asc', 'price_desc', 'name'], true) ? $_GET['sort'] : 'popular';
$page = max(1, to_int($_GET['page'] ?? 1, 1));
$perPage = 12;
$total = get_products(['category_id' => $category['id'], 'limit' => 1])['total'];
$pg = paginate($total, $perPage, $page);
$products = get_products(['category_id' => $category['id'], 'sort' => $sort, 'limit' => $perPage, 'offset' => $pg['offset']])['items'];
$categories = get_categories();
require ROOT_PATH . '/includes/header.php';
?>
<div class="container">
  <nav aria-label="breadcrumb" class="mt-3"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= url('index.php') ?>">Home</a></li><li class="breadcrumb-item"><a href="<?= url('menu.php') ?>">Menu</a></li><li class="breadcrumb-item active"><?= e($category['name']) ?></li></ol></nav>
  <h1 class="page-title mt-0"><i class="bi <?= e($category['icon']) ?>"></i> <?= e($category['name']) ?></h1>
  <?php if ($category['description']): ?><p class="text-muted"><?= e($category['description']) ?></p><?php endif; ?>
  <div class="filter-chips mb-4">
    <?php foreach ($categories as $c): ?>
    <a href="<?= url('category.php?slug=' . urlencode($c['slug'])) ?>" class="<?= $c['id'] === $category['id'] ? 'active' : '' ?>"><?= e($c['name']) ?></a>
    <?php endforeach; ?>
  </div>
  <?php if ($products): ?>
  <div class="product-grid"><?php foreach ($products as $p) render_product_card($p); ?></div>
  <?= pagination_links($pg, ['slug' => $slug, 'sort' => $sort]) ?>
  <?php else: ?>
  <div class="empty-state card-dark"><i class="bi bi-basket"></i><h4>Nothing here yet</h4><p>Items in this category will appear here soon.</p></div>
  <?php endif; ?>
</div>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
