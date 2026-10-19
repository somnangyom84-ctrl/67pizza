<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/config.php';
require_login();
if (!is_post()) redirect(url('profile.php'));
verify_csrf();
$user = current_user();
$action = $_POST['action'] ?? '';
if ($action === 'profile') {
    $data = ['name' => clean_string($_POST['name'] ?? '', 120), 'phone' => clean_string($_POST['phone'] ?? '', 30), 'address' => clean_string($_POST['address'] ?? '', 255)];
    $v = new Validator($data);
    if (!$v->validate(['name' => 'required|min:2|max:120', 'phone' => 'required|phone', 'address' => 'required|min:5|max:255'])) {
        set_form_errors($v->firstErrors()); remember_old($data); flash('danger', 'Please fix the highlighted fields.'); redirect(url('profile.php'));
    }
    db()->prepare('UPDATE users SET name = ?, phone = ?, address = ? WHERE id = ?')->execute([$data['name'], $data['phone'], $data['address'], $user['id']]);
    flash('success', 'Profile updated.');
} elseif ($action === 'password') {
    $v = new Validator($_POST);
    if (!$v->validate(['current_password' => 'required', 'password' => 'required|password|confirmed'])) {
        set_form_errors($v->firstErrors()); flash('danger', 'Please fix the highlighted fields.'); redirect(url('profile.php'));
    }
    $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->execute([$user['id']]);
    if (!password_verify((string) $_POST['current_password'], (string) $stmt->fetchColumn())) {
        set_form_errors(['current_password' => 'Current password is incorrect.']); flash('danger', 'Current password is incorrect.'); redirect(url('profile.php'));
    }
    db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash((string) $_POST['password'], PASSWORD_DEFAULT), $user['id']]);
    session_regenerate_id(true);
    flash('success', 'Password changed successfully.');
}
redirect(url('profile.php'));
