<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/config.php';
require_admin();
require __DIR__ . '/includes/helpers.php';
$pageTitle = 'Contact Messages';
$pdo = db();
if (is_post()) {
    verify_csrf();
    $id = to_int($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($action === 'read' && $id) { $pdo->prepare('UPDATE contact_messages SET is_read = 1 - is_read WHERE id = ?')->execute([$id]); }
    elseif ($action === 'delete' && $id) { $pdo->prepare('DELETE FROM contact_messages WHERE id = ?')->execute([$id]); flash('success', 'Message deleted.'); }
    redirect(url('admin/messages.php'));
}
$q = clean_string($_GET['q'] ?? '', 100);
$w = $q !== '' ? 'WHERE name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?' : '';
$params = $q !== '' ? array_fill(0, 4, "%$q%") : [];
$cnt = $pdo->prepare("SELECT COUNT(*) FROM contact_messages $w"); $cnt->execute($params);
$pg = paginate((int) $cnt->fetchColumn(), 15, max(1, to_int($_GET['page'] ?? 1, 1)));
$stmt = $pdo->prepare("SELECT * FROM contact_messages $w ORDER BY is_read ASC, created_at DESC LIMIT {$pg['per_page']} OFFSET {$pg['offset']}");
$stmt->execute($params); $rows = $stmt->fetchAll();
require __DIR__ . '/includes/header.php'; ?>
<div class="panel"><div class="panel-head"><form class="toolbar" method="get"><input name="q" class="form-control" placeholder="Search messages…" value="<?= e($q) ?>"><button class="btn btn-sm btn-outline-brand">Search</button></form><span class="text-muted small"><?= $pg['total'] ?> messages</span></div>
  <?php if (!$rows): ?><div class="text-center text-muted py-5">No messages.</div><?php endif; ?>
  <div class="accordion accordion-flush" id="msgAcc">
    <?php foreach ($rows as $m): ?>
    <div class="accordion-item bg-transparent border-bottom border-secondary-subtle">
      <h2 class="accordion-header"><button class="accordion-button collapsed bg-transparent text-white <?= $m['is_read'] ? '' : 'fw-bold' ?>" data-bs-toggle="collapse" data-bs-target="#m<?= $m['id'] ?>"><?= $m['is_read'] ? '' : '<span class="badge bg-brand me-2">New</span>' ?><span class="me-2"><?= e($m['name']) ?></span><span class="text-muted small me-2">&lt;<?= e($m['email']) ?>&gt;</span><span class="text-truncate"><?= e($m['subject'] ?: '(no subject)') ?></span><span class="ms-auto text-muted small me-3"><?= time_ago($m['created_at']) ?></span></button></h2>
      <div id="m<?= $m['id'] ?>" class="accordion-collapse collapse" data-bs-parent="#msgAcc"><div class="accordion-body">
        <p style="white-space:pre-wrap"><?= e($m['message']) ?></p>
        <div class="d-flex flex-wrap gap-2 align-items-center small">
          <a class="btn btn-sm btn-brand" href="mailto:<?= e($m['email']) ?>?subject=Re: <?= rawurlencode($m['subject'] ?: '67pizza') ?>"><i class="bi bi-reply"></i> Reply by email</a>
          <?php if ($m['phone']): ?><a class="btn btn-sm btn-dark-soft" href="tel:<?= e($m['phone']) ?>"><i class="bi bi-telephone"></i> <?= e($m['phone']) ?></a><?php endif; ?>
          <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="read"><input type="hidden" name="id" value="<?= $m['id'] ?>"><button class="btn btn-sm btn-dark-soft"><?= $m['is_read'] ? 'Mark unread' : 'Mark read' ?></button></form>
          <?= admin_delete_form('delete', (int) $m['id'], 'Delete this message?') ?>
          <span class="text-muted ms-auto"><?= e($m['created_at']) ?></span>
        </div>
      </div></div>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="px-3"><?= pagination_links($pg, ['q' => $q ?: null]) ?></div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
