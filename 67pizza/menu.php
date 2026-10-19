<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
$pageTitle = 'Menu';
$categories = get_categories();
$catId = to_int($_GET['category'] ?? 0);
$sort = in_array($_GET['sort'] ?? '', ['popular', 'newest', 'price_asc', 'price_desc', 'name'], true) ? $_GET['sort'] : 'popular';
$maxPrice = to_decimal($_GET['max_price'] ?? 0) ?: null;
$page = max(1, to_int($_GET['page'] ?? 1, 1));
$perPage = 12;
$count = get_products(['category_id' => $catId, 'sort' => $sort, 'max_price' => $maxPrice, 'limit' => 1])['total'];
$pg = paginate($count, $perPage, $page);
$products = get_products(['category_id' => $catId, 'sort' => $sort, 'max_price' => $maxPrice, 'limit' => $perPage, 'offset' => $pg['offset']])['items'];
require ROOT_PATH . '/includes/header.php';
?>
<div class="container">
  <h1 class="page-title"><i class="bi bi-journal-text"></i> Our Menu</h1>
  <div class="filter-chips mb-3">
    <a href="<?= url('menu.php?sort=' . $sort) ?>" class="<?= $catId === 0 ? 'active' : '' ?>">All</a>
    <?php foreach ($categories as $c): ?>
    <a href="<?= url('menu.php?category=' . $c['id'] . '&sort=' . $sort) ?>" class="<?= $catId === (int) $c['id'] ? 'active' : '' ?>"><i class="bi <?= e($c['icon']) ?>"></i> <?= e($c['name']) ?></a>
    <?php endforeach; ?>
  </div>
  <form class="filter-bar mb-4" method="get">
    <?php if ($catId): ?><input type="hidden" name="category" value="<?= $catId ?>"><?php endif; ?>
    <span class="text-muted small"><?= $count ?> item<?= $count === 1 ? '' : 's' ?></span>
    <div class="ms-auto d-flex gap-2 flex-wrap">
      <input type="number" name="max_price" class="form-control form-control-sm" style="width:150px" placeholder="Max price" value="<?= $maxPrice ? (int) $maxPrice : '' ?>" min="0" step="1000">
      <select name="sort" class="form-select form-select-sm" style="width:170px" onchange="this.form.submit()">
        <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>Most popular</option>
        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
        <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: low to high</option>
        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: high to low</option>
        <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name A–Z</option>
      </select>
      <button class="btn btn-brand btn-sm">Apply</button>
    </div>
  </form>
  <?php if ($products): ?>
  <div class="product-grid"><?php foreach ($products as $p) render_product_card($p); ?></div>
  <?= pagination_links($pg, ['category' => $catId ?: null, 'sort' => $sort, 'max_price' => $maxPrice]) ?>
  <?php else: ?>
  <div class="empty-state card-dark"><i class="bi bi-search"></i><h4>No items found</h4><p>Try a different category or clear the filters.</p><a class="btn btn-brand" href="<?= url('menu.php') ?>">Clear filters</a></div>
  <?php endif; ?>
</div>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
