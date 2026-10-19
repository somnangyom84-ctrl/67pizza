<?php
declare(strict_types=1);
if (!defined('APP_NAME')) { require_once dirname(__DIR__) . '/config/config.php'; }
$pageTitle = isset($pageTitle) ? $pageTitle . ' | ' . APP_NAME : APP_NAME . ' – ' . setting('site_tagline', 'Good Food, Happy Mood');
$metaDescription = $metaDescription ?? 'Order burgers, pizza, pasta, fries, drinks and desserts from 67pizza. Hot, fresh and on time.';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($metaDescription) ?>">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<meta name="base-url" content="<?= e(BASE_URL) ?>">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='22' fill='%23FFD629'/><text x='50' y='68' font-size='52' font-weight='900' text-anchor='middle' font-family='Arial'>67</text></svg>">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Caveat:wght@700&display=swap" rel="stylesheet">
<link href="<?= url('assets/css/style.css') ?>?v=1.0" rel="stylesheet">
</head>
<body>
<?php require ROOT_PATH . '/includes/navbar.php'; ?>
<main class="site-main">
<?php foreach (get_flashes() as $f): ?>
<div class="container mt-3"><div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show shadow-sm" role="alert"><?= e($f['message']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div></div>
<?php endforeach; ?>
