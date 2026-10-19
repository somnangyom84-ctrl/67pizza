<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/config.php';
if (!is_post()) redirect(url('forgot-password.php'));
verify_csrf();
$email = mb_strtolower(clean_string($_POST['email'] ?? '', 190));
if (valid_email($email) && !login_blocked('reset:' . $email)) {
    record_failed_login('reset:' . $email); // throttle reset requests too
    $stmt = db()->prepare('SELECT id FROM users WHERE email = ? AND is_active = 1 LIMIT 1');
    $stmt->execute([$email]);
    if ($user = $stmt->fetch()) {
        $token = bin2hex(random_bytes(32));
        db()->prepare('DELETE FROM password_resets WHERE user_id = ? OR expires_at < NOW()')->execute([$user['id']]);
        db()->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))')->execute([$user['id'], hash('sha256', $token)]);
        $link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . url('reset-password.php?token=' . $token);
        if (DEV_SHOW_RESET_LINK) {
            $_SESSION['dev_reset_link'] = $link;
        } else {
            @mail($email, '67pizza password reset', "Reset your password using this link (valid 30 minutes):\n$link", 'From: no-reply@67pizza.local');
        }
    }
}
// Always the same message to avoid account enumeration
flash('info', 'If an account exists for that email, a password reset link has been generated.');
redirect(url('forgot-password.php'));
