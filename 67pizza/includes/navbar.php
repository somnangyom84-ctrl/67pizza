<?php
declare(strict_types=1);
$navUser = current_user();
$navCartCount = cart_count();
$navUnread = unread_notifications_count();
$navPage = current_page();
?>
<header class="site-header sticky-top">
  <nav class="navbar navbar-expand-lg">
    <div class="container">
      <a class="navbar-brand brand" href="<?= url('index.php') ?>">
        <span class="brand-mark">67</span><span class="brand-text">pizza</span>
        <small class="brand-tagline d-none d-md-inline"><?= e(setting('site_tagline', 'Good Food, Happy Mood')) ?></small>
      </a>

      <div class="d-flex align-items-center gap-1 order-lg-3">
        <a href="<?= url('cart.php') ?>" class="icon-btn position-relative" title="Cart" aria-label="Cart">
          <i class="bi bi-bag"></i>
          <span class="count-badge js-cart-count <?= $navCartCount ? '' : 'd-none' ?>"><?= $navCartCount ?></span>
        </a>
        <?php if ($navUser): ?>
        <a href="<?= url('notifications.php') ?>" class="icon-btn position-relative" title="Notifications" aria-label="Notifications">
          <i class="bi bi-bell"></i>
          <?php if ($navUnread): ?><span class="count-badge"><?= $navUnread ?></span><?php endif; ?>
        </a>
        <div class="dropdown">
          <button class="icon-btn dropdown-toggle no-caret" data-bs-toggle="dropdown" aria-expanded="false" title="Account"><i class="bi bi-person"></i></button>
          <ul class="dropdown-menu dropdown-menu-end shadow">
            <li><h6 class="dropdown-header text-truncate">Hi, <?= e($navUser['name']) ?></h6></li>
            <?php if ($navUser['role'] === 'admin'): ?>
            <li><a class="dropdown-item" href="<?= url('admin/index.php') ?>"><i class="bi bi-speedometer2 me-2"></i>Admin Dashboard</a></li>
            <?php else: ?>
            <li><a class="dropdown-item" href="<?= url('profile.php') ?>"><i class="bi bi-person-circle me-2"></i>My Profile</a></li>
            <li><a class="dropdown-item" href="<?= url('orders.php') ?>"><i class="bi bi-receipt me-2"></i>My Orders</a></li>
            <li><a class="dropdown-item" href="<?= url('favorites.php') ?>"><i class="bi bi-heart me-2"></i>Favorites</a></li>
            <li><a class="dropdown-item" href="<?= url('notifications.php') ?>"><i class="bi bi-bell me-2"></i>Notifications</a></li>
            <?php endif; ?>
            <li><hr class="dropdown-divider"></li>
            <li>
              <form method="post" action="<?= url('actions/logout.php') ?>"><?= csrf_field() ?>
                <button class="dropdown-item text-danger" type="submit"><i class="bi bi-box-arrow-right me-2"></i>Logout</button>
              </form>
            </li>
          </ul>
        </div>
        <?php else: ?>
        <a href="<?= url('login.php') ?>" class="icon-btn" title="Login" aria-label="Login"><i class="bi bi-person"></i></a>
        <a href="<?= url('register.php') ?>" class="btn btn-brand btn-sm d-none d-sm-inline-flex ms-1">Sign Up</a>
        <?php endif; ?>
        <button class="navbar-toggler ms-1" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation"><i class="bi bi-list"></i></button>
      </div>

      <div class="collapse navbar-collapse order-lg-2" id="mainNav">
        <ul class="navbar-nav mx-auto gap-lg-1">
          <?php
          $links = ['index.php' => 'Home', 'menu.php' => 'Menu', 'restaurants.php' => 'Restaurants', 'about.php' => 'About', 'contact.php' => 'Contact'];
          foreach ($links as $href => $label): ?>
          <li class="nav-item"><a class="nav-link <?= $navPage === $href ? 'active' : '' ?>" href="<?= url($href) ?>"><?= $label ?></a></li>
          <?php endforeach; ?>
        </ul>
        <form class="search-form d-lg-none mt-2" action="<?= url('search.php') ?>" method="get">
          <i class="bi bi-search"></i>
          <input type="search" name="q" class="form-control" placeholder="Search for food, restaurant or items..." value="<?= e($_GET['q'] ?? '') ?>">
        </form>
      </div>
    </div>
  </nav>
</header>
