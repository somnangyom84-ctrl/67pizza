<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/config.php';
if (!is_post()) redirect(url('index.php'));
verify_csrf();
$user = current_user();
if (!$user) {
    if (is_ajax()) json_response(['success' => false, 'login' => true, 'message' => 'Please log in to save favorites.'], 401);
    flash('warning', 'Please log in to save favorites.');
    redirect(url('login.php'));
}
$pid = to_int($_POST['product_id'] ?? 0);
$product = $pid ? get_product($pid) : null;
if (!$product) {
    if (is_ajax()) json_response(['success' => false, 'message' => 'Product not found.'], 404);
    flash('danger', 'Product not found.');
    redirect(url('menu.php'));
}
$stmt = db()->prepare('SELECT id FROM favorites WHERE user_id = ? AND product_id = ?');
$stmt->execute([$user['id'], $pid]);
if ($stmt->fetch()) {
    db()->prepare('DELETE FROM favorites WHERE user_id = ? AND product_id = ?')->execute([$user['id'], $pid]);
    $favorited = false;
    $msg = 'Removed from favorites.';
} else {
    db()->prepare('INSERT INTO favorites (user_id, product_id) VALUES (?, ?)')->execute([$user['id'], $pid]);
    $favorited = true;
    $msg = 'Saved to favorites.';
}
if (is_ajax()) json_response(['success' => true, 'favorited' => $favorited, 'message' => $msg]);
flash('success', $msg);
redirect($_SERVER['HTTP_REFERER'] ?? url('favorites.php'));
