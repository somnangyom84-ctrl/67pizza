<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
if (is_logged_in()) { redirect(url('index.php')); }
$pageTitle = 'Create Account';
$errors = form_errors();
require ROOT_PATH . '/includes/header.php';
?>
<div class="container"><div class="auth-wrap" style="max-width:600px"><div class="auth-card">
  <div class="text-center mb-4"><span class="brand"><span class="brand-mark">67</span><span class="brand-text">pizza</span></span><h1 class="h4 mt-3 mb-1">Create your account</h1><p class="text-muted small mb-0">Get 20% off your first order with code <strong class="text-brand">WELCOME20</strong>.</p></div>
  <form method="post" action="<?= url('actions/register.php') ?>" class="needs-validation" novalidate>
    <?= csrf_field() ?>
    <div class="row g-3">
      <div class="col-md-6"><label class="form-label">Full name *</label><input name="name" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" value="<?= e(old('name')) ?>" required minlength="2" maxlength="120"><?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= e($errors['name']) ?></div><?php endif; ?></div>
      <div class="col-md-6"><label class="form-label">Email *</label><input type="email" name="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" value="<?= e(old('email')) ?>" required><?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= e($errors['email']) ?></div><?php endif; ?></div>
      <div class="col-md-6"><label class="form-label">Phone *</label><input name="phone" class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>" value="<?= e(old('phone')) ?>" required placeholder="0967505304"><?php if (isset($errors['phone'])): ?><div class="invalid-feedback"><?= e($errors['phone']) ?></div><?php endif; ?></div>
      <div class="col-md-6"><label class="form-label">Delivery address *</label><input name="address" class="form-control <?= isset($errors['address']) ? 'is-invalid' : '' ?>" value="<?= e(old('address')) ?>" required placeholder="Street, Sangkat, Khan, City"><?php if (isset($errors['address'])): ?><div class="invalid-feedback"><?= e($errors['address']) ?></div><?php endif; ?></div>
      <div class="col-md-6"><label class="form-label">Password *</label><input type="password" name="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" required minlength="8" autocomplete="new-password"><div class="form-text text-muted">Min. 8 characters with letters and numbers.</div><?php if (isset($errors['password'])): ?><div class="invalid-feedback"><?= e($errors['password']) ?></div><?php endif; ?></div>
      <div class="col-md-6"><label class="form-label">Confirm password *</label><input type="password" name="password_confirmation" class="form-control" required autocomplete="new-password"></div>
      <div class="col-12"><button class="btn btn-brand w-100 justify-content-center py-2"><i class="bi bi-person-plus"></i> Create Account</button></div>
    </div>
  </form>
  <p class="text-center text-muted small mt-4 mb-0">Already registered? <a href="<?= url('login.php') ?>">Log in</a></p>
</div></div></div>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
