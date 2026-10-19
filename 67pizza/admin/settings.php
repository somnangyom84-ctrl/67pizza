<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/config.php';
require_admin();
$pageTitle = 'Website Settings';
$fields = [
    'General' => [
        'site_name' => ['Site name', 'text'], 'site_tagline' => ['Tagline', 'text'], 'about_text' => ['About text', 'textarea'], 'opening_hours' => ['Opening hours', 'text'],
    ],
    'Contact' => [
        'contact_phone' => ['Phone', 'text'], 'contact_email' => ['Email', 'email'], 'telegram_username' => ['Telegram username (without @)', 'text'], 'facebook_url' => ['Facebook URL', 'url'],
    ],
    'Ordering & delivery' => [
        'currency_code' => ['Currency code', 'text'], 'currency_symbol' => ['Currency symbol', 'text'], 'delivery_fee' => ['Delivery fee', 'number'], 'free_delivery_over' => ['Free delivery for orders over (0 = never)', 'number'], 'min_order_amount' => ['Minimum order amount', 'number'], 'allow_guest_cart' => ['Allow guests to use the cart before logging in', 'checkbox'],
    ],
    'Payments' => [
        'cod_enabled' => ['Enable Cash on Delivery', 'checkbox'], 'manual_payment_enabled' => ['Enable manual bank transfer (verified by staff)', 'checkbox'], 'manual_payment_label' => ['Manual payment label', 'text'], 'manual_payment_instructions' => ['Manual payment instructions shown to customers', 'textarea'],
    ],
];
if (is_post()) {
    verify_csrf();
    $errors = [];
    foreach ($fields as $group) {
        foreach ($group as $key => [$label, $type]) {
            if ($type === 'checkbox') { save_setting($key, isset($_POST[$key]) ? '1' : '0'); continue; }
            $val = clean_string($_POST[$key] ?? '', $type === 'textarea' ? 3000 : 255);
            if ($type === 'number') { if ($val !== '' && (!is_numeric($val) || (float) $val < 0)) { $errors[] = "$label must be a non-negative number."; continue; } $val = (string) (float) $val; }
            if ($type === 'email' && $val !== '' && !valid_email($val)) { $errors[] = "$label must be a valid email."; continue; }
            if ($type === 'url' && $val !== '' && !filter_var($val, FILTER_VALIDATE_URL)) { $errors[] = "$label must be a valid URL."; continue; }
            if ($key === 'telegram_username') $val = ltrim($val, '@');
            save_setting($key, $val);
        }
    }
    try {
        $qr = handle_image_upload($_FILES['manual_payment_qr'] ?? [], 'settings');
        if ($qr) { delete_upload(setting('manual_payment_qr') ?: null); save_setting('manual_payment_qr', $qr); }
    } catch (RuntimeException $ex) { $errors[] = 'QR image: ' . $ex->getMessage(); }
    if (isset($_POST['remove_qr'])) { delete_upload(setting('manual_payment_qr') ?: null); save_setting('manual_payment_qr', ''); }
    if ($errors) { foreach ($errors as $er) flash('danger', $er); } else { flash('success', 'Settings saved.'); }
    redirect(url('admin/settings.php'));
}
require __DIR__ . '/includes/header.php'; ?>
<form method="post" enctype="multipart/form-data" style="max-width:1000px"><?= csrf_field() ?>
  <div class="row g-3">
    <?php foreach ($fields as $group => $items): ?>
    <div class="col-lg-6"><div class="panel h-100"><div class="panel-head"><?= e($group) ?></div><div class="panel-body">
      <?php foreach ($items as $key => [$label, $type]): $val = setting($key); ?>
      <?php if ($type === 'checkbox'): ?>
      <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" name="<?= $key ?>" id="<?= $key ?>" <?= $val === '1' ? 'checked' : '' ?>><label class="form-check-label" for="<?= $key ?>"><?= e($label) ?></label></div>
      <?php elseif ($type === 'textarea'): ?>
      <div class="mb-3"><label class="form-label"><?= e($label) ?></label><textarea name="<?= $key ?>" rows="4" class="form-control"><?= e($val) ?></textarea></div>
      <?php else: ?>
      <div class="mb-3"><label class="form-label"><?= e($label) ?></label><input type="<?= $type === 'number' ? 'number' : 'text' ?>" <?= $type === 'number' ? 'step="1" min="0"' : '' ?> name="<?= $key ?>" class="form-control" value="<?= e($val) ?>"></div>
      <?php endif; ?>
      <?php endforeach; ?>
      <?php if ($group === 'Payments'): ?>
      <div class="mb-2"><label class="form-label">Payment QR image (optional, e.g. your bank QR)</label>
        <?php if (setting('manual_payment_qr')): ?><div class="mb-2"><img class="img-qr" src="<?= e(image_url(setting('manual_payment_qr'))) ?>" alt="Payment QR"></div><div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="remove_qr" id="rmqr"><label class="form-check-label" for="rmqr">Remove current QR image</label></div><?php endif; ?>
        <input type="file" name="manual_payment_qr" class="form-control" accept="image/jpeg,image/png,image/webp"><div class="form-text text-muted">Shown to customers who choose manual transfer. Payments are <strong>not</strong> processed automatically – staff must verify each transfer and mark the order as paid.</div></div>
      <?php endif; ?>
    </div></div></div>
    <?php endforeach; ?>
    <div class="col-12"><button class="btn btn-brand"><i class="bi bi-save"></i> Save settings</button></div>
  </div>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
