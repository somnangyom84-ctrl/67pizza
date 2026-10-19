<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
$categories = get_categories();
$popular = get_products(['featured' => true, 'limit' => 8])['items'];
if (count($popular) < 4) { $popular = get_products(['limit' => 8, 'sort' => 'popular'])['items']; }
$restaurants = get_restaurants(true, 4);
if (!$restaurants) { $restaurants = get_restaurants(false, 4); }
$hero = get_banner('hero');
$offer = get_banner('offer');
$reviews = get_approved_reviews(3);
require ROOT_PATH . '/includes/header.php';
?>
<div class="container">

  <!-- Search -->
  <form class="search-form search-hero" action="<?= url('search.php') ?>" method="get" role="search">
    <i class="bi bi-search"></i>
    <input type="search" name="q" class="form-control" placeholder="Search for food, restaurant or items..." aria-label="Search">
    <button class="btn btn-brand btn-sm" type="submit"><i class="bi bi-sliders"></i> Search</button>
  </form>

  <!-- Hero -->
  <section class="hero">
    <img class="hero-bg" src="<?= e(image_url($hero['image'] ?? 'assets/images/hero.jpg')) ?>" alt="Fresh burger, fries and drink" onerror="this.style.display='none'">
    <div class="hero-content">
      <div class="script-line animate-up"><?= e($hero['highlight'] ?? 'Delicious Food') ?></div>
      <h1 class="animate-up d1">Delivered<br><span class="yl">Fresh</span></h1>
      <p class="sub animate-up d2"><?= e($hero['subtitle'] ?? 'Hot • Fresh • On Time') ?></p>
      <a href="<?= url($hero['button_link'] ?? 'menu.php') ?>" class="btn btn-brand btn-lg animate-up d3"><?= e($hero['button_text'] ?? 'Order Now') ?> <i class="bi bi-arrow-right"></i></a>
    </div>
  </section>

  <!-- Categories -->
  <section class="section">
    <div class="section-head"><h2><i class="bi bi-grid"></i> Categories</h2><a class="see-all" href="<?= url('menu.php') ?>">See All <i class="bi bi-arrow-right"></i></a></div>
    <?php if ($categories): ?>
    <div class="category-grid">
      <?php foreach ($categories as $c): ?>
      <a class="category-tile" href="<?= url('category.php?slug=' . urlencode($c['slug'])) ?>">
        <i class="bi <?= e($c['icon'] ?: 'bi-egg-fried') ?>"></i>
        <span><?= e($c['name']) ?></span>
        <small><?= (int) $c['product_count'] ?> items</small>
      </a>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state card-dark"><i class="bi bi-grid"></i><h4>No categories yet</h4><p>Add categories from the admin dashboard.</p></div>
    <?php endif; ?>
  </section>

  <!-- Popular -->
  <section class="section">
    <div class="section-head"><h2><i class="bi bi-fire"></i> Popular Right Now</h2><a class="see-all" href="<?= url('menu.php?sort=popular') ?>">See All <i class="bi bi-arrow-right"></i></a></div>
    <?php if ($popular): ?>
    <div class="product-grid">
      <?php foreach ($popular as $p) render_product_card($p); ?>
    </div>
    <?php else: ?>
    <div class="empty-state card-dark"><i class="bi bi-basket"></i><h4>Menu coming soon</h4><p>No products have been added yet.</p></div>
    <?php endif; ?>
  </section>

  <!-- Offer -->
  <?php if ($offer): ?>
  <section class="section">
    <div class="offer-banner">
      <img src="<?= e(image_url($offer['image'])) ?>" alt="Special offer" onerror="this.style.display='none'">
      <div class="offer-content">
        <span class="offer-tag"><?= e($offer['highlight'] ?: 'Special Offer') ?></span>
        <h2><?= preg_replace('/(\d+%\s*OFF)/i', '<span>$1</span>', e($offer['title'])) ?></h2>
        <p><?= e($offer['subtitle']) ?></p>
        <a href="<?= url($offer['button_link'] ?: 'menu.php') ?>" class="btn btn-brand"><?= e($offer['button_text'] ?: 'Order Now') ?> <i class="bi bi-arrow-right"></i></a>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- Restaurants -->
  <section class="section">
    <div class="section-head"><h2><i class="bi bi-geo-alt-fill"></i> Top Restaurants Near You</h2><a class="see-all" href="<?= url('restaurants.php') ?>">See All <i class="bi bi-arrow-right"></i></a></div>
    <?php if ($restaurants): ?>
    <div class="restaurant-grid">
      <?php foreach ($restaurants as $r): ?>
      <a class="restaurant-card" href="<?= url('restaurant.php?slug=' . urlencode($r['slug'])) ?>">
        <img src="<?= e(image_url($r['image'])) ?>" alt="<?= e($r['name']) ?>" loading="lazy" onerror="this.onerror=null;this.src='<?= placeholder_url() ?>'">
        <div class="rb">
          <h3><?= e($r['name']) ?> <i class="bi bi-chevron-right text-muted"></i></h3>
          <div class="info">
            <span><i class="bi bi-star-fill text-brand"></i> <?= e($r['rating']) ?> (<?= number_format((int) $r['rating_count']) ?>)</span>
            <span><?= $r['distance_km'] !== null ? e($r['distance_km']) . ' km' : e($r['delivery_time']) ?></span>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state card-dark"><i class="bi bi-shop"></i><h4>No restaurants listed</h4></div>
    <?php endif; ?>
  </section>

  <!-- Reviews -->
  <section class="section">
    <div class="section-head"><h2><i class="bi bi-chat-heart-fill"></i> What Customers Say</h2></div>
    <?php if ($reviews): ?>
    <div class="row g-3">
      <?php foreach ($reviews as $rv): ?>
      <div class="col-md-4">
        <div class="review-card">
          <span class="quote">“</span>
          <?= render_stars((float) $rv['rating']) ?>
          <p class="mt-2 mb-3"><?= e($rv['comment'] ?: 'Loved it!') ?></p>
          <div class="d-flex align-items-center gap-2">
            <span class="avatar"><?= e(mb_strtoupper(mb_substr($rv['user_name'], 0, 1))) ?></span>
            <div><strong><?= e($rv['user_name']) ?></strong><div class="text-muted small">on <?= e($rv['product_name']) ?></div></div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="row g-3">
      <?php foreach ([['Sokha', 'The Chicken Tikka Pizza is incredible – arrived hot in 25 minutes!'], ['Dara', 'Best cheeseburger in town. Ordering again this weekend.'], ['Lina', 'Lava cake was warm and gooey. 10/10 would recommend.']] as $t): ?>
      <div class="col-md-4"><div class="review-card"><span class="quote">“</span><?= render_stars(5) ?><p class="mt-2 mb-3"><?= e($t[1]) ?></p><div class="d-flex align-items-center gap-2"><span class="avatar"><?= e($t[0][0]) ?></span><div><strong><?= e($t[0]) ?></strong><div class="text-muted small">Happy customer</div></div></div></div></div>
      <?php endforeach; ?>
    </div>
    <p class="text-muted small mt-2">Reviews from verified orders appear here once approved.</p>
    <?php endif; ?>
  </section>

  <!-- Newsletter -->
  <section class="section" id="newsletter">
    <div class="newsletter">
      <div><h2>Get exclusive deals</h2><p class="mb-0">Subscribe and be the first to hear about new menu items and discounts.</p></div>
      <form action="<?= url('actions/newsletter.php') ?>" method="post"><?= csrf_field() ?>
        <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
        <button class="btn" type="submit">Subscribe <i class="bi bi-send"></i></button>
      </form>
    </div>
  </section>
</div>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
