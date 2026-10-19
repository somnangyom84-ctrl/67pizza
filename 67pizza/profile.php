<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
require_login();
$pageTitle = 'My Profile';
$user = current_user();
$errors = form_errors();
$stats = db()->prepare('SELECT COUNT(*) AS cnt, COALESCE(SUM(CASE WHEN status <> "cancelled" THEN total ELSE 0 END),0) AS spent FROM orders WHERE user_id = ?');
$stats->execute([$user['id']]);
$stats = $stats->fetch();
require ROOT_PATH . '/includes/header.php';
?>
<div class="container">
  <h1 class="page-title"><i class="bi bi-person-circle"></i> My Profile</h1>
  <div class="row g-4">
    <div class="col-lg-4">
      <div class="card-dark p-4 text-center">
        <span class="avatar mx-auto" style="width:72px;height:72px;font-size:1.8rem"><?= e(mb_strtoupper(mb_substr($user['name'], 0, 1))) ?></span>
        <h4 class="mt-3 mb-0"><?= e($user['name']) ?></h4><div class="text-muted small"><?= e($user['email']) ?></div>
        <div class="text-muted small">Member since <?= date('M Y', strtotime($user['created_at'])) ?></div>
        <div class="row g-2 mt-3"><div class="col-6"><div class="card-dark p-2"><div class="fs-4 fw-bold text-brand"><?= (int) $stats['cnt'] ?></div><div class="text-muted small">Orders</div></div></div><div class="col-6"><div class="card-dark p-2"><div class="fs-6 fw-bold text-brand"><?= format_price($stats['spent']) ?></div><div class="text-muted small">Spent</div></div></div></div>
      </div>
      <div class="list-group mt-3">
        <a class="list-group-item list-group-item-action active" href="<?= url('profile.php') ?>"><i class="bi bi-person me-2"></i>Profile</a>
        <a class="list-group-item list-group-item-action" href="<?= url('orders.php') ?>"><i class="bi bi-receipt me-2"></i>Orders</a>
        <a class="list-group-item list-group-item-action" href="<?= url('favorites.php') ?>"><i class="bi bi-heart me-2"></i>Favorites</a>
        <a class="list-group-item list-group-item-action" href="<?= url('notifications.php') ?>"><i class="bi bi-bell me-2"></i>Notifications</a>
      </div>
    </div>
    <div class="col-lg-8">
      <div class="card-dark p-4 mb-4">
        <h5 class="mb-3">Profile & delivery address</h5>
        <form method="post" action="<?= url('actions/profile.php') ?>" class="needs-validation" novalidate><?= csrf_field() ?><input type="hidden" name="action" value="profile">
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Full name</label><input name="name" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" value="<?= e(old('name', $user['name'])) ?>" required><?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= e($errors['name']) ?></div><?php endif; ?></div>
            <div class="col-md-6"><label class="form-label">Email</label><input class="form-control" value="<?= e($user['email']) ?>" disabled><div class="form-text text-muted">Email cannot be changed.</div></div>
            <div class="col-md-6"><label class="form-label">Phone</label><input name="phone" class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>" value="<?= e(old('phone', $user['phone'] ?? '')) ?>" required><?php if (isset($errors['phone'])): ?><div class="invalid-feedback"><?= e($errors['phone']) ?></div><?php endif; ?></div>
            <div class="col-md-6"><label class="form-label">Delivery address</label><input name="address" class="form-control <?= isset($errors['address']) ? 'is-invalid' : '' ?>" value="<?= e(old('address', $user['address'] ?? '')) ?>" required><?php if (isset($errors['address'])): ?><div class="invalid-feedback"><?= e($errors['address']) ?></div><?php endif; ?></div>
            <div class="col-12"><button class="btn btn-brand"><i class="bi bi-save"></i> Save changes</button></div>
          </div>
        </form>
      </div>
      <div class="card-dark p-4">
        <h5 class="mb-3">Change password</h5>
        <form method="post" action="<?= url('actions/profile.php') ?>" class="needs-validation" novalidate><?= csrf_field() ?><input type="hidden" name="action" value="password">
          <div class="row g-3">
            <div class="col-md-4"><label class="form-label">Current password</label><input type="password" name="current_password" class="form-control <?= isset($errors['current_password']) ? 'is-invalid' : '' ?>" required><?php if (isset($errors['current_password'])): ?><div class="invalid-feedback"><?= e($errors['current_password']) ?></div><?php endif; ?></div>
            <div class="col-md-4"><label class="form-label">New password</label><input type="password" name="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" required minlength="8"><?php if (isset($errors['password'])): ?><div class="invalid-feedback"><?= e($errors['password']) ?></div><?php endif; ?></div>
            <div class="col-md-4"><label class="form-label">Confirm new password</label><input type="password" name="password_confirmation" class="form-control" required></div>
            <div class="col-12"><button class="btn btn-outline-brand"><i class="bi bi-key"></i> Update password</button></div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
