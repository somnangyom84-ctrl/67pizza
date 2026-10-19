<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/config.php';
if (!is_post()) redirect(url('login.php'));
verify_csrf();
$email = clean_string($_POST['email'] ?? '', 190);
$password = (string) ($_POST['password'] ?? '');
$v = new Validator(['email' => $email, 'password' => $password]);
if (!$v->validate(['email' => 'required|email', 'password' => 'required'])) {
    set_form_errors($v->firstErrors());
    remember_old($_POST);
    redirect(url('login.php'));
}
$result = attempt_login($email, $password);
if (!$result['success']) {
    flash('danger', $result['message']);
    remember_old($_POST);
    redirect(url('login.php'));
}
flash('success', $result['message']);
$next = $_SESSION['redirect_after_login'] ?? null;
unset($_SESSION['redirect_after_login']);
if ($result['user']['role'] === 'admin') redirect(url('admin/index.php'));
// only allow local relative redirects
if ($next && str_starts_with($next, BASE_URL . '/') && !str_contains($next, '//')) redirect($next);
redirect(url('index.php'));
