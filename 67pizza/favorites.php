<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
require_login();
$pageTitle = 'Favorites';
$user = current_user();
$stmt = db()->prepare('SELECT p.*, c.name AS category_name FROM favorites f INNER JOIN products p ON p.id = f.product_id INNER JOIN categories c ON c.id = p.category_id WHERE f.user_id = ? ORDER BY f.created_at DESC');
$stmt->execute([$user['id']]);
$products = $stmt->fetchAll();
require ROOT_PATH . '/includes/header.php';
?>
<div class="container js-fav-page">
  <h1 class="page-title"><i class="bi bi-heart-fill"></i> My Favorites</h1>
  <?php if ($products): ?>
  <div class="product-grid"><?php foreach ($products as $p) render_product_card($p); ?></div>
  <?php else: ?>
  <div class="empty-state card-dark"><i class="bi bi-heart"></i><h4>No favorites yet</h4><p>Tap the heart on any dish to save it here.</p><a class="btn btn-brand" href="<?= url('menu.php') ?>">Explore menu</a></div>
  <?php endif; ?>
</div>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
