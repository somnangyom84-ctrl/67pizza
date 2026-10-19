<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/config.php';
if (is_admin()) redirect(url('admin/index.php'));
$hasAdmin = (int) db()->query('SELECT COUNT(*) FROM users WHERE role = "admin"')->fetchColumn() > 0;
$error = '';
if (is_post()) {
    verify_csrf();
    $r = attempt_login(clean_string($_POST['email'] ?? '', 190), (string) ($_POST['password'] ?? ''), true);
    if ($r['success']) { redirect(url('admin/index.php')); }
    $error = $r['message'];
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Admin Login | 67pizza</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=Caveat:wght@700&display=swap" rel="stylesheet">
<link href="<?= url('assets/css/style.css') ?>" rel="stylesheet"></head>
<body><main class="site-main"><div class="container"><div class="auth-wrap"><div class="auth-card">
  <div class="text-center mb-4"><span class="brand"><span class="brand-mark">67</span><span class="brand-text">pizza</span></span><h1 class="h4 mt-3 mb-1">Admin Login</h1><p class="text-muted small mb-0">Authorized staff only.</p></div>
  <?php foreach (get_flashes() as $f): ?><div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div><?php endforeach; ?>
  <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
  <?php if (!$hasAdmin): ?><div class="alert alert-warning small">No administrator exists yet. Run <code>admin/setup-admin.php</code> once to create the first admin account.</div><?php endif; ?>
  <form method="post"><?= csrf_field() ?>
    <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required autofocus></div>
    <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
    <button class="btn btn-brand w-100 justify-content-center py-2"><i class="bi bi-shield-lock"></i> Log in</button>
  </form>
  <p class="text-center small mt-3 mb-0"><a href="<?= url('index.php') ?>" class="text-muted"><i class="bi bi-arrow-left"></i> Back to website</a></p>
</div></div></div></main></body></html>
