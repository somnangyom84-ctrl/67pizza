<?php
/**
 * One-time administrator setup.
 * 1. Set ADMIN_SETUP_KEY in config/config.php.
 * 2. Open http://localhost/67pizza/admin/setup-admin.php and enter the key.
 * 3. After creating the admin, set ADMIN_SETUP_ENABLED to false (or delete this file).
 */
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/config.php';
$adminCount = (int) db()->query('SELECT COUNT(*) FROM users WHERE role = "admin"')->fetchColumn();
$disabled = !ADMIN_SETUP_ENABLED || $adminCount > 0;
$errors = [];
$done = false;
if (is_post() && !$disabled) {
    verify_csrf();
    if (!hash_equals(ADMIN_SETUP_KEY, (string) ($_POST['setup_key'] ?? ''))) {
        $errors[] = 'Invalid setup key.';
    } else {
        $data = ['name' => clean_string($_POST['name'] ?? '', 120), 'email' => mb_strtolower(clean_string($_POST['email'] ?? '', 190)), 'password' => (string) ($_POST['password'] ?? ''), 'password_confirmation' => (string) ($_POST['password_confirmation'] ?? '')];
        $v = new Validator($data);
        if (!$v->validate(['name' => 'required|min:2', 'email' => 'required|email', 'password' => 'required|password|confirmed'])) {
            $errors = $v->allMessages();
        } else {
            $chk = db()->prepare('SELECT id FROM users WHERE email = ?');
            $chk->execute([$data['email']]);
            if ($chk->fetch()) {
                $errors[] = 'That email is already registered.';
            } else {
                db()->prepare('INSERT INTO users (name, email, password_hash, role, is_active) VALUES (?,?,?,"admin",1)')->execute([$data['name'], $data['email'], password_hash($data['password'], PASSWORD_DEFAULT)]);
                $done = true;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Admin Setup | 67pizza</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=Caveat:wght@700&display=swap" rel="stylesheet">
<link href="<?= url('assets/css/style.css') ?>" rel="stylesheet"></head>
<body><main class="site-main"><div class="container"><div class="auth-wrap" style="max-width:560px"><div class="auth-card">
  <h1 class="h4 mb-2"><i class="bi bi-shield-lock text-brand"></i> 67pizza Admin Setup</h1>
  <?php if ($done): ?>
    <div class="alert alert-success">Administrator account created. <strong>Now set <code>ADMIN_SETUP_ENABLED</code> to <code>false</code> in <code>config/config.php</code> or delete this file.</strong></div>
    <a class="btn btn-brand" href="<?= url('admin/login.php') ?>">Go to admin login</a>
  <?php elseif ($disabled): ?>
    <div class="alert alert-warning">Setup is disabled<?= $adminCount > 0 ? ' because an administrator already exists' : ' (ADMIN_SETUP_ENABLED is false)' ?>. To add more admins, log in and use the Customers page, or re-enable setup temporarily in <code>config/config.php</code>.</div>
    <a class="btn btn-brand" href="<?= url('admin/login.php') ?>">Admin login</a>
  <?php else: ?>
    <p class="text-muted small">Create the first administrator. You need the setup key from <code>config/config.php</code>.</p>
    <?php foreach ($errors as $er): ?><div class="alert alert-danger py-2"><?= e($er) ?></div><?php endforeach; ?>
    <form method="post"><?= csrf_field() ?>
      <div class="mb-3"><label class="form-label">Setup key</label><input type="password" name="setup_key" class="form-control" required></div>
      <div class="mb-3"><label class="form-label">Admin name</label><input name="name" class="form-control" required value="<?= e($_POST['name'] ?? '') ?>"></div>
      <div class="mb-3"><label class="form-label">Admin email</label><input type="email" name="email" class="form-control" required value="<?= e($_POST['email'] ?? '') ?>"></div>
      <div class="row g-3 mb-3"><div class="col-6"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required minlength="8"></div><div class="col-6"><label class="form-label">Confirm</label><input type="password" name="password_confirmation" class="form-control" required></div></div>
      <button class="btn btn-brand w-100 justify-content-center">Create administrator</button>
    </form>
  <?php endif; ?>
</div></div></div></main></body></html>
