<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
$pageTitle = 'Set New Password';
$token = clean_string($_GET['token'] ?? ($_POST['token'] ?? ''), 128);
$valid = false;
$userId = 0;
if ($token !== '' && preg_match('/^[a-f0-9]{64}$/', $token)) {
    $stmt = db()->prepare('SELECT * FROM password_resets WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW() LIMIT 1');
    $stmt->execute([hash('sha256', $token)]);
    $row = $stmt->fetch();
    if ($row) { $valid = true; $userId = (int) $row['user_id']; }
}
$errors = [];
if (is_post() && $valid) {
    verify_csrf();
    $v = new Validator($_POST);
    if ($v->validate(['password' => 'required|password|confirmed'])) {
        $pdo = db();
        $pdo->beginTransaction();
        $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($_POST['password'], PASSWORD_DEFAULT), $userId]);
        $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE user_id = ?')->execute([$userId]);
        $pdo->commit();
        flash('success', 'Your password has been updated. Please log in.');
        redirect(url('login.php'));
    }
    $errors = $v->firstErrors();
}
require ROOT_PATH . '/includes/header.php';
?>
<div class="container"><div class="auth-wrap"><div class="auth-card">
  <h1 class="h4 mb-3">Choose a new password</h1>
  <?php if (!$valid): ?>
  <div class="alert alert-danger">This reset link is invalid or has expired.</div><a href="<?= url('forgot-password.php') ?>" class="btn btn-brand">Request a new link</a>
  <?php else: ?>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="token" value="<?= e($token) ?>">
    <div class="mb-3"><label class="form-label">New password</label><input type="password" name="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" required minlength="8"><?php if (isset($errors['password'])): ?><div class="invalid-feedback"><?= e($errors['password']) ?></div><?php endif; ?></div>
    <div class="mb-3"><label class="form-label">Confirm password</label><input type="password" name="password_confirmation" class="form-control" required></div>
    <button class="btn btn-brand w-100 justify-content-center">Update password</button>
  </form>
  <?php endif; ?>
</div></div></div>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
