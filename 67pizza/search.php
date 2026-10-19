<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
$q = clean_string($_GET['q'] ?? '', 100);
$pageTitle = $q !== '' ? 'Search: ' . $q : 'Search';
$page = max(1, to_int($_GET['page'] ?? 1, 1));
$perPage = 12;
$products = [];
$restaurants = [];
$pg = paginate(0, $perPage, 1);
if ($q !== '') {
    $total = get_products(['search' => $q, 'limit' => 1])['total'];
    $pg = paginate($total, $perPage, $page);
    $products = get_products(['search' => $q, 'limit' => $perPage, 'offset' => $pg['offset']])['items'];
    $stmt = db()->prepare('SELECT * FROM restaurants WHERE is_active = 1 AND (name LIKE ? OR description LIKE ?) LIMIT 8');
    $stmt->execute(['%' . $q . '%', '%' . $q . '%']);
    $restaurants = $stmt->fetchAll();
}
require ROOT_PATH . '/includes/header.php';
?>
<div class="container">
  <form class="search-form search-hero" action="<?= url('search.php') ?>" method="get" role="search">
    <i class="bi bi-search"></i>
    <input type="search" name="q" class="form-control" placeholder="Search for food, restaurant or items..." value="<?= e($q) ?>" autofocus>
    <button class="btn btn-brand btn-sm" type="submit">Search</button>
  </form>
  <?php if ($q === ''): ?>
  <div class="empty-state"><i class="bi bi-search"></i><h4>What are you craving?</h4><p>Search by dish, category, or restaurant name.</p></div>
  <?php else: ?>
  <h1 class="page-title">Results for “<?= e($q) ?>”</h1>
  <?php if ($restaurants): ?>
  <h5 class="mb-3"><i class="bi bi-shop text-brand"></i> Restaurants</h5>
  <div class="restaurant-grid mb-4">
    <?php foreach ($restaurants as $r): ?>
    <a class="restaurant-card" href="<?= url('restaurant.php?slug=' . urlencode($r['slug'])) ?>"><img src="<?= e(image_url($r['image'])) ?>" alt="" onerror="this.onerror=null;this.src='<?= placeholder_url() ?>'"><div class="rb"><h3><?= e($r['name']) ?></h3><div class="info"><span><i class="bi bi-star-fill text-brand"></i> <?= e($r['rating']) ?></span><span><?= e($r['delivery_time']) ?></span></div></div></a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  <h5 class="mb-3"><i class="bi bi-basket text-brand"></i> Food items (<?= $pg['total'] ?>)</h5>
  <?php if ($products): ?>
  <div class="product-grid"><?php foreach ($products as $p) render_product_card($p); ?></div>
  <?= pagination_links($pg, ['q' => $q]) ?>
  <?php else: ?>
  <div class="empty-state card-dark"><i class="bi bi-emoji-neutral"></i><h4>No matches found</h4><p>Try another keyword like “pizza” or “burger”.</p></div>
  <?php endif; ?>
  <?php endif; ?>
</div>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
