</main>
<footer class="site-footer mt-5">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-4">
        <a class="brand d-inline-flex mb-3" href="<?= url('index.php') ?>"><span class="brand-mark">67</span><span class="brand-text">pizza</span></a>
        <p class="text-muted small"><?= e(setting('site_tagline', 'Good Food, Happy Mood')) ?>. Hot, fresh, delicious food delivered to your door across Cambodia.</p>
        <div class="social-links">
          <a href="<?= e(setting('facebook_url', '#')) ?>" target="_blank" rel="noopener" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
          <a href="https://t.me/<?= e(setting('telegram_username', 'yomsomnang')) ?>" target="_blank" rel="noopener" aria-label="Telegram"><i class="bi bi-telegram"></i></a>
          <a href="mailto:<?= e(setting('contact_email')) ?>" aria-label="Email"><i class="bi bi-envelope"></i></a>
          <a href="tel:<?= e(setting('contact_phone')) ?>" aria-label="Phone"><i class="bi bi-telephone"></i></a>
        </div>
      </div>
      <div class="col-6 col-lg-2">
        <h6 class="footer-title">Explore</h6>
        <ul class="footer-links">
          <li><a href="<?= url('menu.php') ?>">Menu</a></li>
          <li><a href="<?= url('restaurants.php') ?>">Restaurants</a></li>
          <li><a href="<?= url('about.php') ?>">About Us</a></li>
          <li><a href="<?= url('contact.php') ?>">Contact</a></li>
        </ul>
      </div>
      <div class="col-6 col-lg-2">
        <h6 class="footer-title">Account</h6>
        <ul class="footer-links">
          <li><a href="<?= url('profile.php') ?>">My Profile</a></li>
          <li><a href="<?= url('orders.php') ?>">My Orders</a></li>
          <li><a href="<?= url('favorites.php') ?>">Favorites</a></li>
          <li><a href="<?= url('cart.php') ?>">Cart</a></li>
        </ul>
      </div>
      <div class="col-lg-4">
        <h6 class="footer-title">Contact</h6>
        <ul class="footer-links contact-list">
          <li><i class="bi bi-telephone"></i><a href="tel:<?= e(setting('contact_phone')) ?>"><?= e(setting('contact_phone')) ?></a></li>
          <li><i class="bi bi-envelope"></i><a href="mailto:<?= e(setting('contact_email')) ?>"><?= e(setting('contact_email')) ?></a></li>
          <li><i class="bi bi-telegram"></i><a href="https://t.me/<?= e(setting('telegram_username')) ?>" target="_blank" rel="noopener">@<?= e(setting('telegram_username')) ?></a></li>
          <li><i class="bi bi-facebook"></i><a href="<?= e(setting('facebook_url')) ?>" target="_blank" rel="noopener">Facebook Page</a></li>
          <li><i class="bi bi-clock"></i><span><?= e(setting('opening_hours')) ?></span></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <span>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. All rights reserved.</span>
      <span class="text-muted">Made with <i class="bi bi-heart-fill text-brand"></i> in Cambodia</span>
    </div>
  </div>
</footer>
<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= url('assets/js/app.js') ?>?v=1.0"></script>
</body>
</html>
