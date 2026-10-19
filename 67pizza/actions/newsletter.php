<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/config.php';
if (!is_post()) redirect(url('index.php'));
verify_csrf();
$email = mb_strtolower(clean_string($_POST['email'] ?? '', 190));
if (!valid_email($email)) {
    flash('danger', 'Please enter a valid email address.');
} else {
    db()->prepare('INSERT IGNORE INTO newsletter_subscribers (email) VALUES (?)')->execute([$email]);
    flash('success', 'You are subscribed! Watch your inbox for deals.');
}
redirect(($_SERVER['HTTP_REFERER'] ?? url('index.php')) . '#newsletter');
