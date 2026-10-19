<?php
declare(strict_types=1);
if (!defined('APP_NAME')) { require_once dirname(__DIR__, 2) . '/config/config.php'; }
require_admin();
$admin = current_user();
$adminPage = current_page();
$adminTitle = ($pageTitle ?? 'Dashboard') . ' | Admin – ' . APP_NAME;
$pendingOrders = (int) db()->query('SELECT COUNT(*) FROM orders WHERE status = "pending"')->fetchColumn();
$unreadMessages = (int) db()->query('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0')->fetchColumn();
$pendingReviews = (int) db()->query('SELECT COUNT(*) FROM reviews WHERE status = "pending"')->fetchColumn();
$menu = [
    ['index.php', 'bi-speedometer2', 'Dashboard', 0],
    ['orders.php', 'bi-receipt', 'Orders', $pendingOrders],
    ['products.php', 'bi-basket', 'Products', 0],
    ['categories.php', 'bi-grid', 'Categories', 0],
    ['customers.php', 'bi-people', 'Customers', 0],
    ['coupons.php', 'bi-ticket-perforated', 'Coupons', 0],
    ['reviews.php', 'bi-star', 'Reviews', $pendingReviews],
    ['restaurants.php', 'bi-shop', 'Restaurants', 0],
    ['banners.php', 'bi-image', 'Banners', 0],
    ['messages.php', 'bi-envelope', 'Messages', $unreadMessages],
    ['reports.php', 'bi-bar-chart', 'Reports', 0],
    ['settings.php', 'bi-gear', 'Settings', 0],
];
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($adminTitle) ?></title>
<meta name="csrf-token" content="<?= e(csrf_token()) ?>"><meta name="base-url" content="<?= e(BASE_URL) ?>">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="<?= url('assets/css/style.css') ?>?v=1.0" rel="stylesheet">
<link href="<?= url('assets/css/admin.css') ?>?v=1.0" rel="stylesheet">
</head>
<body class="admin-body">
<aside class="admin-sidebar" id="adminSidebar">
  <a class="brand px-3 py-3 d-flex" href="<?= url('admin/index.php') ?>"><span class="brand-mark">67</span><span class="brand-text">pizza</span><span class="badge bg-brand ms-2 align-self-center" style="font-size:.6rem">ADMIN</span></a>
  <nav class="admin-nav">
    <?php foreach ($menu as [$href, $icon, $label, $badge]): ?>
    <a href="<?= url('admin/' . $href) ?>" class="<?= $adminPage === $href ? 'active' : '' ?>"><i class="bi <?= $icon ?>"></i><span><?= $label ?></span><?php if ($badge): ?><span class="nav-badge"><?= $badge ?></span><?php endif; ?></a>
    <?php endforeach; ?>
  </nav>
  <div class="admin-sidebar-footer">
    <a href="<?= url('index.php') ?>" target="_blank"><i class="bi bi-box-arrow-up-right"></i> View website</a>
  </div>
</aside>
<div class="admin-overlay" id="adminOverlay"></div>
<div class="admin-content">
  <header class="admin-topbar">
    <button class="icon-btn d-lg-none" id="sidebarToggle" aria-label="Menu"><i class="bi bi-list"></i></button>
    <h1 class="admin-page-title"><?= e($pageTitle ?? 'Dashboard') ?></h1>
    <div class="ms-auto d-flex align-items-center gap-2">
      <a href="<?= url('admin/orders.php?status=pending') ?>" class="icon-btn position-relative" title="Pending orders"><i class="bi bi-bell"></i><?php if ($pendingOrders): ?><span class="count-badge"><?= $pendingOrders ?></span><?php endif; ?></a>
      <div class="dropdown">
        <button class="btn btn-dark-soft dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown"><span class="avatar" style="width:30px;height:30px;font-size:.85rem"><?= e(mb_strtoupper(mb_substr($admin['name'], 0, 1))) ?></span><span class="d-none d-sm-inline"><?= e($admin['name']) ?></span></button>
        <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="<?= url('admin/settings.php') ?>"><i class="bi bi-gear me-2"></i>Settings</a></li><li><hr class="dropdown-divider"></li><li><form method="post" action="<?= url('actions/logout.php') ?>"><?= csrf_field() ?><button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Logout</button></form></li></ul>
      </div>
    </div>
  </header>
  <main class="admin-main">
  <?php foreach (get_flashes() as $f): ?>
  <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show" role="alert"><?= e($f['message']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  <?php endforeach; ?>
