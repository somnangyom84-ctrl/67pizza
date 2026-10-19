<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/config.php';
if (!is_post()) redirect(url('register.php'));
verify_csrf();
$data = [
    'name' => clean_string($_POST['name'] ?? '', 120),
    'email' => mb_strtolower(clean_string($_POST['email'] ?? '', 190)),
    'phone' => clean_string($_POST['phone'] ?? '', 30),
    'address' => clean_string($_POST['address'] ?? '', 255),
    'password' => (string) ($_POST['password'] ?? ''),
    'password_confirmation' => (string) ($_POST['password_confirmation'] ?? ''),
];
$v = new Validator($data);
if (!$v->validate([
    'name' => 'required|min:2|max:120',
    'email' => 'required|email',
    'phone' => 'required|phone',
    'address' => 'required|min:5|max:255',
    'password' => 'required|password|confirmed',
])) {
    set_form_errors($v->firstErrors());
    remember_old($data);
    flash('danger', 'Please fix the highlighted fields.');
    redirect(url('register.php'));
}
$r = register_user($data);
if (!$r['success']) {
    set_form_errors(['email' => $r['message']]);
    remember_old($data);
    redirect(url('register.php'));
}
attempt_login($data['email'], $data['password']);
flash('success', 'Welcome to 67pizza, ' . $data['name'] . '! Your account is ready.');
redirect(url('index.php'));
