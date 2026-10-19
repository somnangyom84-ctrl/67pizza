<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
$pageTitle = 'Restaurants';
$restaurants = get_restaurants(false, 100);
require ROOT_PATH . '/includes/header.php';
?>
<div class="container">
  <h1 class="page-title"><i class="bi bi-geo-alt-fill"></i> Restaurants Near You</h1>
  <?php if ($restaurants): ?>
  <div class="restaurant-grid">
    <?php foreach ($restaurants as $r): ?>
    <a class="restaurant-card" href="<?= url('restaurant.php?slug=' . urlencode($r['slug'])) ?>">
      <img src="<?= e(image_url($r['image'])) ?>" alt="<?= e($r['name']) ?>" loading="lazy" onerror="this.onerror=null;this.src='<?= placeholder_url() ?>'">
      <div class="rb">
        <h3><?= e($r['name']) ?> <i class="bi bi-chevron-right text-muted"></i></h3>
        <div class="info"><span><i class="bi bi-star-fill text-brand"></i> <?= e($r['rating']) ?> (<?= number_format((int) $r['rating_count']) ?>)</span><span><i class="bi bi-clock"></i> <?= e($r['delivery_time']) ?></span></div>
        <div class="info mt-1"><span><?= (int) $r['product_count'] ?> items</span><?php if ($r['distance_km'] !== null): ?><span><?= e($r['distance_km']) ?> km</span><?php endif; ?></div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <div class="empty-state card-dark"><i class="bi bi-shop"></i><h4>No restaurants yet</h4></div>
  <?php endif; ?>
</div>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
