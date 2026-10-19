<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
$slug = clean_string($_GET['slug'] ?? '', 170);
$product = $slug !== '' ? get_product_by_slug($slug) : (to_int($_GET['id'] ?? 0) ? get_product(to_int($_GET['id'])) : null);
if (!$product) {
    http_response_code(404);
    $pageTitle = 'Product not found';
    require ROOT_PATH . '/includes/header.php';
    echo '<div class="container"><div class="empty-state"><i class="bi bi-emoji-frown"></i><h4>Product not found</h4><a class="btn btn-brand mt-2" href="' . url('menu.php') . '">Browse menu</a></div></div>';
    require ROOT_PATH . '/includes/footer.php';
    exit;
}
$pageTitle = $product['name'];
$metaDescription = mb_substr((string) $product['description'], 0, 150);
$rating = product_rating((int) $product['id']);
$stmt = db()->prepare('SELECT rv.*, u.name AS user_name FROM reviews rv INNER JOIN users u ON u.id = rv.user_id WHERE rv.product_id = ? AND rv.status = "approved" ORDER BY rv.created_at DESC LIMIT 10');
$stmt->execute([$product['id']]);
$reviews = $stmt->fetchAll();
$related = array_filter(get_products(['category_id' => $product['category_id'], 'limit' => 5])['items'], fn($r) => $r['id'] !== $product['id']);
$related = array_slice($related, 0, 4);
$fav = is_favorite((int) $product['id']);
require ROOT_PATH . '/includes/header.php';
?>
<div class="container">
  <nav aria-label="breadcrumb" class="mt-3"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= url('index.php') ?>">Home</a></li><li class="breadcrumb-item"><a href="<?= url('category.php?slug=' . urlencode($product['category_slug'])) ?>"><?= e($product['category_name']) ?></a></li><li class="breadcrumb-item active"><?= e($product['name']) ?></li></ol></nav>
  <div class="row g-4 align-items-start">
    <div class="col-md-6">
      <div class="product-detail-img"><img src="<?= e(image_url($product['image'])) ?>" alt="<?= e($product['name']) ?>" onerror="this.onerror=null;this.src='<?= placeholder_url() ?>'"></div>
    </div>
    <div class="col-md-6">
      <div class="d-flex align-items-center gap-2 mb-2">
        <span class="badge bg-brand"><?= e($product['category_name']) ?></span>
        <?php if ($product['restaurant_name']): ?><a class="text-muted small" href="<?= url('restaurant.php?slug=' . urlencode($product['restaurant_slug'])) ?>"><i class="bi bi-shop"></i> <?= e($product['restaurant_name']) ?></a><?php endif; ?>
      </div>
      <h1 class="fw-bold mb-2"><?= e($product['name']) ?></h1>
      <div class="d-flex align-items-center gap-2 mb-3">
        <?= render_stars($rating['avg']) ?>
        <span class="text-muted small"><?= $rating['count'] ? $rating['avg'] . ' · ' . $rating['count'] . ' review' . ($rating['count'] === 1 ? '' : 's') : 'No reviews yet' ?></span>
        <span class="text-muted small">· <?= number_format((int) $product['sold_count']) ?> sold</span>
      </div>
      <p class="text-muted"><?= nl2br(e($product['description'])) ?></p>
      <div class="price-lg mb-3"><?= format_price($product['price']) ?></div>
      <?php if ((int) $product['is_available'] === 1): ?>
      <form method="post" action="<?= url('actions/cart.php') ?>" class="d-flex flex-wrap align-items-center gap-3">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add"><input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
        <div class="qty-control">
          <button type="button" class="js-qty" data-dir="down"><i class="bi bi-dash"></i></button>
          <input type="number" id="qtyInput" name="quantity" value="1" min="1" max="50" aria-label="Quantity">
          <button type="button" class="js-qty" data-dir="up"><i class="bi bi-plus"></i></button>
        </div>
        <button type="submit" class="btn btn-brand"><i class="bi bi-bag-plus"></i> Add to Cart</button>
        <button type="button" class="btn btn-dark-soft js-fav <?= $fav ? 'active text-danger' : '' ?>" data-id="<?= (int) $product['id'] ?>"><i class="bi <?= $fav ? 'bi-heart-fill' : 'bi-heart' ?>"></i> <?= $fav ? 'Saved' : 'Save' ?></button>
      </form>
      <?php else: ?>
      <div class="alert alert-warning"><i class="bi bi-exclamation-circle"></i> This item is currently unavailable.</div>
      <?php endif; ?>
      <ul class="list-unstyled text-muted small mt-4">
        <li><i class="bi bi-clock text-brand me-2"></i>Estimated delivery 25–40 min</li>
        <li><i class="bi bi-truck text-brand me-2"></i>Free delivery on orders over <?= format_price(setting('free_delivery_over', '0')) ?></li>
        <li><i class="bi bi-shield-check text-brand me-2"></i>Freshly prepared after you order</li>
      </ul>
    </div>
  </div>

  <section class="section">
    <div class="section-head"><h2><i class="bi bi-chat-square-text"></i> Reviews</h2></div>
    <?php if ($reviews): ?>
    <div class="row g-3">
      <?php foreach ($reviews as $rv): ?>
      <div class="col-md-6"><div class="review-card"><div class="d-flex justify-content-between"><strong><?= e($rv['user_name']) ?></strong><span class="text-muted small"><?= time_ago($rv['created_at']) ?></span></div><?= render_stars((float) $rv['rating']) ?><p class="mb-0 mt-2"><?= e($rv['comment']) ?></p></div></div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="card-dark p-4 text-muted">No approved reviews yet. Order this item and share your experience from your order details page.</div>
    <?php endif; ?>
  </section>

  <?php if ($related): ?>
  <section class="section">
    <div class="section-head"><h2><i class="bi bi-stars"></i> You may also like</h2></div>
    <div class="product-grid"><?php foreach ($related as $p) render_product_card($p); ?></div>
  </section>
  <?php endif; ?>
</div>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
