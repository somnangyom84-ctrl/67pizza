<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/config.php';
if (!is_post()) redirect(url('contact.php'));
verify_csrf();
if (!empty($_POST['website'])) { // honeypot
    flash('success', 'Thanks! Your message has been sent.');
    redirect(url('contact.php'));
}
$data = [
    'name' => clean_string($_POST['name'] ?? '', 120),
    'email' => clean_string($_POST['email'] ?? '', 190),
    'phone' => clean_string($_POST['phone'] ?? '', 30),
    'subject' => clean_string($_POST['subject'] ?? '', 150),
    'message' => clean_string($_POST['message'] ?? '', 3000),
];
$v = new Validator($data);
if (!$v->validate(['name' => 'required|min:2|max:120', 'email' => 'required|email', 'phone' => 'phone', 'message' => 'required|min:10|max:3000'])) {
    set_form_errors($v->firstErrors());
    remember_old($data);
    flash('danger', 'Please check the form and try again.');
    redirect(url('contact.php'));
}
db()->prepare('INSERT INTO contact_messages (name, email, phone, subject, message) VALUES (?,?,?,?,?)')
    ->execute([$data['name'], $data['email'], $data['phone'] ?: null, $data['subject'] ?: null, $data['message']]);
flash('success', 'Thanks ' . $data['name'] . '! Your message has been sent. We will get back to you soon.');
redirect(url('contact.php'));
