<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/config.php';
if (is_post()) { verify_csrf(); }
$wasAdmin = is_admin();
logout_user();
session_start();
flash('success', 'You have been logged out.');
redirect($wasAdmin ? url('admin/login.php') : url('index.php'));
