<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
$pageTitle = 'Contact Us';
$errors = form_errors();
$u = current_user();
require ROOT_PATH . '/includes/header.php';
?>
<div class="container">
  <h1 class="page-title"><i class="bi bi-chat-dots-fill"></i> Contact 67pizza</h1>
  <div class="row g-4">
    <div class="col-lg-5">
      <div class="card-dark p-4 h-100">
        <h5 class="mb-3">Get in touch</h5>
        <ul class="footer-links contact-list fs-6">
          <li><i class="bi bi-telephone-fill"></i><a href="tel:<?= e(setting('contact_phone')) ?>"><?= e(setting('contact_phone')) ?></a></li>
          <li><i class="bi bi-envelope-fill"></i><a href="mailto:<?= e(setting('contact_email')) ?>"><?= e(setting('contact_email')) ?></a></li>
          <li><i class="bi bi-telegram"></i><a href="https://t.me/<?= e(setting('telegram_username')) ?>" target="_blank" rel="noopener">@<?= e(setting('telegram_username')) ?></a></li>
          <li><i class="bi bi-facebook"></i><a href="<?= e(setting('facebook_url')) ?>" target="_blank" rel="noopener">Facebook Page</a></li>
          <li><i class="bi bi-clock-fill"></i><span><?= e(setting('opening_hours')) ?></span></li>
        </ul>
        <hr class="border-secondary">
        <p class="text-muted small mb-0">We usually reply within a few hours during opening times. For urgent order issues, Telegram or phone is fastest.</p>
      </div>
    </div>
    <div class="col-lg-7">
      <div class="card-dark p-4">
        <h5 class="mb-3">Send us a message</h5>
        <form method="post" action="<?= url('actions/contact.php') ?>" class="needs-validation" novalidate>
          <?= csrf_field() ?>
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Your name *</label><input name="name" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" value="<?= e(old('name', $u['name'] ?? '')) ?>" required maxlength="120"><?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= e($errors['name']) ?></div><?php endif; ?></div>
            <div class="col-md-6"><label class="form-label">Email *</label><input type="email" name="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" value="<?= e(old('email', $u['email'] ?? '')) ?>" required><?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= e($errors['email']) ?></div><?php endif; ?></div>
            <div class="col-md-6"><label class="form-label">Phone</label><input name="phone" class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>" value="<?= e(old('phone', $u['phone'] ?? '')) ?>"><?php if (isset($errors['phone'])): ?><div class="invalid-feedback"><?= e($errors['phone']) ?></div><?php endif; ?></div>
            <div class="col-md-6"><label class="form-label">Subject</label><input name="subject" class="form-control" value="<?= e(old('subject')) ?>" maxlength="150"></div>
            <div class="col-12"><label class="form-label">Message *</label><textarea name="message" rows="5" class="form-control <?= isset($errors['message']) ? 'is-invalid' : '' ?>" required minlength="10"><?= e(old('message')) ?></textarea><?php if (isset($errors['message'])): ?><div class="invalid-feedback"><?= e($errors['message']) ?></div><?php endif; ?></div>
            <div class="col-12 d-none"><label>Leave blank<input name="website" class="form-control" tabindex="-1" autocomplete="off"></label></div>
            <div class="col-12"><button class="btn btn-brand"><i class="bi bi-send"></i> Send Message</button></div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
