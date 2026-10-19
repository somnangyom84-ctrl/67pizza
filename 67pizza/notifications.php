<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
require_login();
$pageTitle = 'Notifications';
$user = current_user();
if (is_post()) {
    verify_csrf();
    if (($_POST['action'] ?? '') === 'read_all') {
        db()->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?')->execute([$user['id']]);
        flash('success', 'All notifications marked as read.');
    } elseif (($_POST['action'] ?? '') === 'clear') {
        db()->prepare('DELETE FROM notifications WHERE user_id = ?')->execute([$user['id']]);
        flash('success', 'Notifications cleared.');
    }
    redirect(url('notifications.php'));
}
$stmt = db()->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 100');
$stmt->execute([$user['id']]);
$notes = $stmt->fetchAll();
// Mark displayed as read after rendering list
db()->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0')->execute([$user['id']]);
require ROOT_PATH . '/includes/header.php';
?>
<div class="container">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h1 class="page-title"><i class="bi bi-bell-fill"></i> Notifications</h1>
    <?php if ($notes): ?><form method="post" data-confirm="Clear all notifications?"><?= csrf_field() ?><input type="hidden" name="action" value="clear"><button class="btn btn-sm btn-dark-soft">Clear all</button></form><?php endif; ?>
  </div>
  <?php if (!$notes): ?>
  <div class="empty-state card-dark"><i class="bi bi-bell-slash"></i><h4>You're all caught up</h4><p>Order updates will appear here.</p></div>
  <?php else: ?>
  <div class="card-dark overflow-hidden">
    <?php foreach ($notes as $n): ?>
    <div class="notification-item <?= (int) $n['is_read'] === 0 ? 'unread' : '' ?>">
      <div class="n-icon"><i class="bi <?= str_contains($n['title'], 'Cancel') ? 'bi-x-circle' : (str_contains($n['title'], 'Welcome') ? 'bi-gift' : 'bi-bag-check') ?>"></i></div>
      <div class="flex-grow-1"><div class="d-flex justify-content-between gap-2"><strong><?= e($n['title']) ?></strong><span class="text-muted small text-nowrap"><?= time_ago($n['created_at']) ?></span></div><div class="text-muted small"><?= e($n['message']) ?></div><?php if ($n['link']): ?><a class="small" href="<?= url($n['link']) ?>">View details <i class="bi bi-arrow-right"></i></a><?php endif; ?></div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
