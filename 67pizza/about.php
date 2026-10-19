<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
$pageTitle = 'About Us';
$stats = [
    'products' => (int) db()->query('SELECT COUNT(*) FROM products WHERE is_available = 1')->fetchColumn(),
    'orders'   => (int) db()->query('SELECT COUNT(*) FROM orders WHERE status = "completed"')->fetchColumn(),
    'customers'=> (int) db()->query('SELECT COUNT(*) FROM users WHERE role = "customer"')->fetchColumn(),
    'restaurants' => (int) db()->query('SELECT COUNT(*) FROM restaurants WHERE is_active = 1')->fetchColumn(),
];
require ROOT_PATH . '/includes/header.php';
?>
<div class="container">
  <div class="hero mt-4" style="min-height:300px">
    <img class="hero-bg" src="<?= url('assets/images/hero.jpg') ?>" alt="" onerror="this.style.display='none'">
    <div class="hero-content"><div class="script-line">About</div><h1 style="font-size:2.6rem">67<span class="yl">pizza</span></h1><p class="sub"><?= e(setting('site_tagline')) ?></p></div>
  </div>
  <div class="row g-4 mt-2 align-items-center">
    <div class="col-lg-7">
      <h2 class="fw-bold">Our Story</h2>
      <p class="text-muted"><?= nl2br(e(setting('about_text'))) ?></p>
      <p class="text-muted">We partner with trusted local kitchens and deliver across the city – hot, fresh and on time. Every order is prepared after you place it, packed carefully and tracked from our kitchen to your door.</p>
      <div class="d-flex gap-2 flex-wrap"><a href="<?= url('menu.php') ?>" class="btn btn-brand">Explore Menu</a><a href="<?= url('contact.php') ?>" class="btn btn-outline-brand">Contact Us</a></div>
    </div>
    <div class="col-lg-5">
      <div class="row g-3">
        <?php foreach ([['bi-basket', $stats['products'], 'Menu items'], ['bi-bag-check', $stats['orders'], 'Orders delivered'], ['bi-people', $stats['customers'], 'Happy customers'], ['bi-shop', $stats['restaurants'], 'Partner kitchens']] as $s): ?>
        <div class="col-6"><div class="card-dark p-3 text-center"><i class="bi <?= $s[0] ?> text-brand fs-2"></i><div class="fs-3 fw-bold"><?= number_format($s[1]) ?></div><div class="text-muted small"><?= $s[2] ?></div></div></div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <div class="row g-3 mt-4">
    <?php foreach ([['bi-lightning-charge', 'Fast delivery', 'Average delivery under 35 minutes within the city.'], ['bi-fire', 'Always fresh', 'Prepared to order with quality ingredients.'], ['bi-shield-check', 'Secure ordering', 'Your data and orders are protected.'], ['bi-headset', 'Friendly support', 'Reach us on Telegram or phone any time.']] as $f): ?>
    <div class="col-md-6 col-lg-3"><div class="card-dark p-4 h-100"><i class="bi <?= $f[0] ?> text-brand fs-2"></i><h5 class="mt-2"><?= $f[1] ?></h5><p class="text-muted small mb-0"><?= $f[2] ?></p></div></div>
    <?php endforeach; ?>
  </div>
</div>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
