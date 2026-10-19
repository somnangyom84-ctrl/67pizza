<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
if (is_logged_in()) { redirect(is_admin() ? url('admin/index.php') : url('index.php')); }
$pageTitle = 'Login';
$errors = form_errors();
require ROOT_PATH . '/includes/header.php';
?>
<div class="container"><div class="auth-wrap"><div class="auth-card">
  <div class="text-center mb-4"><span class="brand"><span class="brand-mark">67</span><span class="brand-text">pizza</span></span><h1 class="h4 mt-3 mb-1">Welcome back</h1><p class="text-muted small mb-0">Log in to order faster and track deliveries.</p></div>
  <form method="post" action="<?= url('actions/login.php') ?>" class="needs-validation" novalidate>
    <?= csrf_field() ?>
    <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" value="<?= e(old('email')) ?>" required autofocus autocomplete="email"><?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= e($errors['email']) ?></div><?php endif; ?></div>
    <div class="mb-3"><label class="form-label d-flex justify-content-between">Password <a href="<?= url('forgot-password.php') ?>" class="small fw-normal">Forgot password?</a></label><input type="password" name="password" class="form-control" required autocomplete="current-password"></div>
    <button class="btn btn-brand w-100 justify-content-center py-2"><i class="bi bi-box-arrow-in-right"></i> Log In</button>
  </form>
  <p class="text-center text-muted small mt-4 mb-0">Don't have an account? <a href="<?= url('register.php') ?>">Create one</a></p>
</div></div></div>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
