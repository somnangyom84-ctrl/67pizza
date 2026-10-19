<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
$slug = clean_string($_GET['slug'] ?? '', 140);
$stmt = db()->prepare('SELECT * FROM restaurants WHERE slug = ? AND is_active = 1 LIMIT 1');
$stmt->execute([$slug]);
$restaurant = $stmt->fetch();
if (!$restaurant) {
    http_response_code(404);
    $pageTitle = 'Restaurant not found';
    require ROOT_PATH . '/includes/header.php';
    echo '<div class="container"><div class="empty-state"><i class="bi bi-emoji-frown"></i><h4>Restaurant not found</h4><a class="btn btn-brand mt-2" href="' . url('restaurants.php') . '">All restaurants</a></div></div>';
    require ROOT_PATH . '/includes/footer.php';
    exit;
}
$pageTitle = $restaurant['name'];
$products = get_products(['restaurant_id' => $restaurant['id'], 'limit' => 24])['items'];
require ROOT_PATH . '/includes/header.php';
?>
<div class="container">
  <div class="hero mt-3" style="min-height:280px">
    <img class="hero-bg" src="<?= e(image_url($restaurant['image'])) ?>" alt="<?= e($restaurant['name']) ?>" onerror="this.style.display='none'">
    <div class="hero-content">
      <h1 class="mb-2" style="font-size:2.4rem"><?= e($restaurant['name']) ?></h1>
      <p class="text-muted mb-2"><?= e($restaurant['description']) ?></p>
      <div class="d-flex flex-wrap gap-3 small">
        <span><i class="bi bi-star-fill text-brand"></i> <?= e($restaurant['rating']) ?> (<?= number_format((int) $restaurant['rating_count']) ?> ratings)</span>
        <span><i class="bi bi-clock text-brand"></i> <?= e($restaurant['delivery_time']) ?></span>
        <?php if ($restaurant['distance_km'] !== null): ?><span><i class="bi bi-geo-alt text-brand"></i> <?= e($restaurant['distance_km']) ?> km</span><?php endif; ?>
        <?php if ($restaurant['phone']): ?><span><i class="bi bi-telephone text-brand"></i> <a href="tel:<?= e($restaurant['phone']) ?>" class="text-white"><?= e($restaurant['phone']) ?></a></span><?php endif; ?>
      </div>
    </div>
  </div>
  <section class="section">
    <div class="section-head"><h2><i class="bi bi-journal-text"></i> Menu</h2></div>
    <?php if ($products): ?>
    <div class="product-grid"><?php foreach ($products as $p) render_product_card($p); ?></div>
    <?php else: ?>
    <div class="empty-state card-dark"><i class="bi bi-basket"></i><h4>No items yet</h4></div>
    <?php endif; ?>
  </section>
</div>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
