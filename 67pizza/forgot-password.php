<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
$pageTitle = 'Forgot Password';
$resetLink = $_SESSION['dev_reset_link'] ?? null;
unset($_SESSION['dev_reset_link']);
require ROOT_PATH . '/includes/header.php';
?>
<div class="container"><div class="auth-wrap"><div class="auth-card">
  <h1 class="h4 mb-1">Reset your password</h1>
  <p class="text-muted small">Enter your account email. If it exists, we will generate a secure reset link (valid for 30 minutes).</p>
  <?php if ($resetLink): ?>
  <div class="alert alert-info small"><strong>Local development mode:</strong> no mail server is configured, so here is your reset link:<br><a href="<?= e($resetLink) ?>" class="text-break"><?= e($resetLink) ?></a></div>
  <?php endif; ?>
  <form method="post" action="<?= url('actions/forgot-password.php') ?>" class="needs-validation" novalidate>
    <?= csrf_field() ?>
    <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
    <button class="btn btn-brand w-100 justify-content-center">Send reset link</button>
  </form>
  <p class="text-center small mt-3 mb-0"><a href="<?= url('login.php') ?>">Back to login</a></p>
</div></div></div>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
