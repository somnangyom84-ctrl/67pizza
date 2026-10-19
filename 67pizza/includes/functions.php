<?php
declare(strict_types=1);

/* =========================================================================
 | Output / URL / flow helpers
 * ========================================================================= */
function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function redirect(string $to): never
{
    header('Location: ' . $to);
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function is_ajax(): bool
{
    return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
        || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
}

function json_response(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/** Remember form input across a redirect. */
function remember_old(array $data, array $except = ['password', 'password_confirmation', 'csrf_token']): void
{
    foreach ($except as $k) {
        unset($data[$k]);
    }
    $_SESSION['old_input'] = $data;
}

function old(string $key, string $default = ''): string
{
    static $old = null;
    if ($old === null) {
        $old = $_SESSION['old_input'] ?? [];
        unset($_SESSION['old_input']);
    }
    $v = $old[$key] ?? $default;
    return is_scalar($v) ? (string) $v : $default;
}

function set_form_errors(array $errors): void
{
    $_SESSION['form_errors'] = $errors;
}

function form_errors(): array
{
    static $errors = null;
    if ($errors === null) {
        $errors = $_SESSION['form_errors'] ?? [];
        unset($_SESSION['form_errors']);
    }
    return $errors;
}

function current_page(): string
{
    return basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'index.php'));
}

/* =========================================================================
 | Settings & formatting
 * ========================================================================= */
function settings_all(): array
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (db()->query('SELECT setting_key, setting_value FROM settings') as $row) {
            $cache[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $cache;
}

function setting(string $key, string $default = ''): string
{
    $all = settings_all();
    return isset($all[$key]) && $all[$key] !== null ? (string) $all[$key] : $default;
}

function save_setting(string $key, string $value): void
{
    db()->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)')
        ->execute([$key, $value]);
}

function format_price(float|int|string $amount): string
{
    $symbol = setting('currency_symbol', '៛');
    $amount = (float) $amount;
    $formatted = number_format($amount, 0, '.', ',');
    return $formatted . ' ' . $symbol;
}

function image_url(?string $path): string
{
    if (!$path) {
        return url('assets/images/placeholder.svg');
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    $path = ltrim($path, '/');
    if (!is_file(ROOT_PATH . '/' . $path)) {
        return url('assets/images/placeholder.svg');
    }
    return url($path);
}

function placeholder_url(): string
{
    return url('assets/images/placeholder.svg');
}

function slugify(string $text): string
{
    $text = mb_strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/u', '-', $text) ?? '';
    $text = trim($text, '-');
    return $text !== '' ? $text : 'item-' . substr(bin2hex(random_bytes(3)), 0, 6);
}

function unique_slug(string $table, string $base, int $excludeId = 0): string
{
    $allowed = ['products', 'categories', 'restaurants'];
    if (!in_array($table, $allowed, true)) {
        throw new InvalidArgumentException('Invalid table');
    }
    $slug = $base;
    $i = 2;
    while (true) {
        $stmt = db()->prepare("SELECT id FROM `$table` WHERE slug = ? AND id <> ? LIMIT 1");
        $stmt->execute([$slug, $excludeId]);
        if (!$stmt->fetch()) {
            return $slug;
        }
        $slug = $base . '-' . $i++;
    }
}

function time_ago(string $datetime): string
{
    $ts = strtotime($datetime);
    $diff = time() - $ts;
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hr ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return date('M j, Y', $ts);
}

/** Simple pagination metadata. */
function paginate(int $total, int $perPage, int $page): array
{
    $pages = max(1, (int) ceil($total / max(1, $perPage)));
    $page = min(max(1, $page), $pages);
    return ['total' => $total, 'per_page' => $perPage, 'page' => $page, 'pages' => $pages, 'offset' => ($page - 1) * $perPage];
}

function pagination_links(array $p, array $query = []): string
{
    if ($p['pages'] <= 1) return '';
    $html = '<nav class="mt-4"><ul class="pagination justify-content-center">';
    for ($i = 1; $i <= $p['pages']; $i++) {
        if ($p['pages'] > 9 && abs($i - $p['page']) > 3 && $i !== 1 && $i !== $p['pages']) {
            if (abs($i - $p['page']) === 4) $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
            continue;
        }
        $q = http_build_query(array_merge($query, ['page' => $i]));
        $html .= '<li class="page-item' . ($i === $p['page'] ? ' active' : '') . '"><a class="page-link" href="?' . e($q) . '">' . $i . '</a></li>';
    }
    return $html . '</ul></nav>';
}

/* =========================================================================
 | Catalog queries
 * ========================================================================= */
function get_categories(bool $activeOnly = true): array
{
    $sql = 'SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.is_available = 1) AS product_count FROM categories c';
    if ($activeOnly) $sql .= ' WHERE c.is_active = 1';
    $sql .= ' ORDER BY c.sort_order ASC, c.name ASC';
    return db()->query($sql)->fetchAll();
}

function get_category_by_slug(string $slug): ?array
{
    $stmt = db()->prepare('SELECT * FROM categories WHERE slug = ? AND is_active = 1 LIMIT 1');
    $stmt->execute([$slug]);
    return $stmt->fetch() ?: null;
}

/**
 * Flexible product listing.
 * $opts: category_id, restaurant_id, search, featured, available(bool, default true), sort, limit, offset, min_price, max_price
 * Returns ['items'=>[], 'total'=>int]
 */
function get_products(array $opts = []): array
{
    $where = [];
    $params = [];
    if (!array_key_exists('available', $opts) || $opts['available']) {
        $where[] = 'p.is_available = 1';
    }
    if (!empty($opts['category_id'])) {
        $where[] = 'p.category_id = ?';
        $params[] = (int) $opts['category_id'];
    }
    if (!empty($opts['restaurant_id'])) {
        $where[] = 'p.restaurant_id = ?';
        $params[] = (int) $opts['restaurant_id'];
    }
    if (!empty($opts['featured'])) {
        $where[] = 'p.is_featured = 1';
    }
    if (isset($opts['min_price']) && $opts['min_price'] !== null) {
        $where[] = 'p.price >= ?';
        $params[] = (float) $opts['min_price'];
    }
    if (!empty($opts['max_price'])) {
        $where[] = 'p.price <= ?';
        $params[] = (float) $opts['max_price'];
    }
    if (!empty($opts['search'])) {
        $like = '%' . $opts['search'] . '%';
        $where[] = '(p.name LIKE ? OR p.description LIKE ? OR c.name LIKE ? OR r.name LIKE ?)';
        array_push($params, $like, $like, $like, $like);
    }
    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $sorts = [
        'popular'    => 'p.sold_count DESC, p.id DESC',
        'newest'     => 'p.created_at DESC',
        'price_asc'  => 'p.price ASC',
        'price_desc' => 'p.price DESC',
        'name'       => 'p.name ASC',
    ];
    $order = $sorts[$opts['sort'] ?? 'popular'] ?? $sorts['popular'];
    $base = 'FROM products p INNER JOIN categories c ON c.id = p.category_id LEFT JOIN restaurants r ON r.id = p.restaurant_id ' . $whereSql;

    $count = db()->prepare('SELECT COUNT(*) ' . $base);
    $count->execute($params);
    $total = (int) $count->fetchColumn();

    $limit = max(1, (int) ($opts['limit'] ?? 12));
    $offset = max(0, (int) ($opts['offset'] ?? 0));
    $sql = 'SELECT p.*, c.name AS category_name, c.slug AS category_slug, r.name AS restaurant_name, r.slug AS restaurant_slug,
            (SELECT ROUND(AVG(rv.rating),1) FROM reviews rv WHERE rv.product_id = p.id AND rv.status = "approved") AS avg_rating,
            (SELECT COUNT(*) FROM reviews rv WHERE rv.product_id = p.id AND rv.status = "approved") AS review_count '
         . $base . " ORDER BY $order LIMIT $limit OFFSET $offset";
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return ['items' => $stmt->fetchAll(), 'total' => $total];
}

function get_product(int $id, bool $availableOnly = false): ?array
{
    $sql = 'SELECT p.*, c.name AS category_name, c.slug AS category_slug, r.name AS restaurant_name, r.slug AS restaurant_slug
            FROM products p INNER JOIN categories c ON c.id = p.category_id LEFT JOIN restaurants r ON r.id = p.restaurant_id
            WHERE p.id = ?' . ($availableOnly ? ' AND p.is_available = 1' : '') . ' LIMIT 1';
    $stmt = db()->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function get_product_by_slug(string $slug): ?array
{
    $stmt = db()->prepare('SELECT p.*, c.name AS category_name, c.slug AS category_slug, r.name AS restaurant_name, r.slug AS restaurant_slug
            FROM products p INNER JOIN categories c ON c.id = p.category_id LEFT JOIN restaurants r ON r.id = p.restaurant_id
            WHERE p.slug = ? LIMIT 1');
    $stmt->execute([$slug]);
    return $stmt->fetch() ?: null;
}

function product_rating(int $productId): array
{
    $stmt = db()->prepare('SELECT ROUND(AVG(rating),1) AS avg_rating, COUNT(*) AS cnt FROM reviews WHERE product_id = ? AND status = "approved"');
    $stmt->execute([$productId]);
    $r = $stmt->fetch();
    return ['avg' => (float) ($r['avg_rating'] ?? 0), 'count' => (int) ($r['cnt'] ?? 0)];
}

function get_restaurants(bool $featuredOnly = false, int $limit = 50): array
{
    $sql = 'SELECT r.*, (SELECT COUNT(*) FROM products p WHERE p.restaurant_id = r.id AND p.is_available = 1) AS product_count FROM restaurants r WHERE r.is_active = 1';
    if ($featuredOnly) $sql .= ' AND r.is_featured = 1';
    $sql .= ' ORDER BY r.is_featured DESC, r.rating DESC LIMIT ' . max(1, $limit);
    return db()->query($sql)->fetchAll();
}

function get_banner(string $position): ?array
{
    $stmt = db()->prepare('SELECT * FROM banners WHERE position = ? AND is_active = 1 ORDER BY sort_order ASC, id DESC LIMIT 1');
    $stmt->execute([$position]);
    return $stmt->fetch() ?: null;
}

function get_approved_reviews(int $limit = 6): array
{
    $stmt = db()->prepare('SELECT rv.*, u.name AS user_name, p.name AS product_name FROM reviews rv
        INNER JOIN users u ON u.id = rv.user_id INNER JOIN products p ON p.id = rv.product_id
        WHERE rv.status = "approved" ORDER BY rv.created_at DESC LIMIT ' . max(1, $limit));
    $stmt->execute();
    return $stmt->fetchAll();
}

/* =========================================================================
 | Favorites
 * ========================================================================= */
function favorite_ids(): array
{
    static $ids = null;
    if ($ids !== null) return $ids;
    $ids = [];
    $u = current_user();
    if ($u) {
        $stmt = db()->prepare('SELECT product_id FROM favorites WHERE user_id = ?');
        $stmt->execute([$u['id']]);
        $ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }
    return $ids;
}

function is_favorite(int $productId): bool
{
    return in_array($productId, favorite_ids(), true);
}

/* =========================================================================
 | Cart (DB for logged-in users, PHP session for guests)
 * ========================================================================= */
function cart_user_id(): int
{
    $u = current_user();
    return $u && $u['role'] === 'customer' ? (int) $u['id'] : 0;
}

/** Raw map product_id => quantity */
function cart_raw(): array
{
    $uid = cart_user_id();
    if ($uid) {
        $stmt = db()->prepare('SELECT product_id, quantity FROM cart_items WHERE user_id = ?');
        $stmt->execute([$uid]);
        $map = [];
        foreach ($stmt->fetchAll() as $r) $map[(int) $r['product_id']] = (int) $r['quantity'];
        return $map;
    }
    return array_map('intval', $_SESSION['cart'] ?? []);
}

function cart_add(int $productId, int $qty = 1): array
{
    $qty = max(1, min(50, $qty));
    $product = get_product($productId, true);
    if (!$product) {
        return ['success' => false, 'message' => 'This item is currently unavailable.'];
    }
    $uid = cart_user_id();
    if ($uid) {
        db()->prepare('INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE quantity = LEAST(50, quantity + VALUES(quantity))')->execute([$uid, $productId, $qty]);
    } else {
        if (setting('allow_guest_cart', '1') !== '1') {
            return ['success' => false, 'message' => 'Please log in to add items to your cart.', 'login' => true];
        }
        $cart = $_SESSION['cart'] ?? [];
        $cart[$productId] = min(50, ($cart[$productId] ?? 0) + $qty);
        $_SESSION['cart'] = $cart;
    }
    return ['success' => true, 'message' => $product['name'] . ' added to cart.'];
}

function cart_update(int $productId, int $qty): array
{
    if ($qty <= 0) return cart_remove($productId);
    $qty = min(50, $qty);
    $uid = cart_user_id();
    if ($uid) {
        db()->prepare('UPDATE cart_items SET quantity = ? WHERE user_id = ? AND product_id = ?')->execute([$qty, $uid, $productId]);
    } elseif (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId] = $qty;
    }
    return ['success' => true, 'message' => 'Cart updated.'];
}

function cart_remove(int $productId): array
{
    $uid = cart_user_id();
    if ($uid) {
        db()->prepare('DELETE FROM cart_items WHERE user_id = ? AND product_id = ?')->execute([$uid, $productId]);
    } else {
        unset($_SESSION['cart'][$productId]);
    }
    return ['success' => true, 'message' => 'Item removed from cart.'];
}

function cart_clear(): void
{
    $uid = cart_user_id();
    if ($uid) {
        db()->prepare('DELETE FROM cart_items WHERE user_id = ?')->execute([$uid]);
    }
    unset($_SESSION['cart'], $_SESSION['coupon_code']);
}

/** Move guest session cart into DB after login. */
function cart_merge_session_into_db(int $userId): void
{
    $cart = $_SESSION['cart'] ?? [];
    if (!$cart) return;
    $stmt = db()->prepare('INSERT INTO cart_items (user_id, product_id, quantity) SELECT ?, id, ? FROM products WHERE id = ? AND is_available = 1
        ON DUPLICATE KEY UPDATE quantity = LEAST(50, quantity + VALUES(quantity))');
    foreach ($cart as $pid => $qty) {
        $stmt->execute([$userId, max(1, min(50, (int) $qty)), (int) $pid]);
    }
    unset($_SESSION['cart']);
}

/** Cart lines with current DB product data (unavailable items are dropped). */
function cart_items(): array
{
    $raw = cart_raw();
    if (!$raw) return [];
    $ids = array_keys($raw);
    $in = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare("SELECT p.*, c.name AS category_name FROM products p INNER JOIN categories c ON c.id = p.category_id WHERE p.id IN ($in)");
    $stmt->execute($ids);
    $items = [];
    foreach ($stmt->fetchAll() as $p) {
        $pid = (int) $p['id'];
        if ((int) $p['is_available'] !== 1 || (float) $p['price'] < 0) {
            cart_remove($pid);
            continue;
        }
        $qty = max(1, min(50, (int) $raw[$pid]));
        $items[] = [
            'product_id' => $pid,
            'name'       => $p['name'],
            'slug'       => $p['slug'],
            'image'      => $p['image'],
            'category'   => $p['category_name'],
            'price'      => round((float) $p['price'], 2),
            'quantity'   => $qty,
            'line_total' => round((float) $p['price'] * $qty, 2),
        ];
    }
    return $items;
}

function cart_count(): int
{
    return array_sum(cart_raw());
}

/** Server-side totals. */
function cart_totals(array $items, ?array $coupon = null): array
{
    $subtotal = round(array_sum(array_column($items, 'line_total')), 2);
    $discount = 0.0;
    if ($coupon) {
        $discount = coupon_discount($coupon, $subtotal);
    }
    $deliveryFee = (float) setting('delivery_fee', '0');
    $freeOver = (float) setting('free_delivery_over', '0');
    if ($subtotal <= 0 || ($freeOver > 0 && $subtotal >= $freeOver)) {
        $deliveryFee = 0.0;
    }
    $total = max(0, round($subtotal - $discount + $deliveryFee, 2));
    return ['subtotal' => $subtotal, 'discount' => $discount, 'delivery_fee' => $deliveryFee, 'total' => $total];
}

/* =========================================================================
 | Coupons
 * ========================================================================= */
function coupon_discount(array $coupon, float $subtotal): float
{
    if ($coupon['type'] === 'percent') {
        $d = $subtotal * ((float) $coupon['value'] / 100);
        if (!empty($coupon['max_discount'])) {
            $d = min($d, (float) $coupon['max_discount']);
        }
    } else {
        $d = (float) $coupon['value'];
    }
    return round(min(max(0, $d), $subtotal), 2);
}

/** Validate coupon for current context. Returns ['valid'=>bool,'coupon'=>?array,'message'=>string] */
function validate_coupon(string $code, float $subtotal, int $userId = 0): array
{
    $code = strtoupper(trim($code));
    if ($code === '') return ['valid' => false, 'coupon' => null, 'message' => 'Enter a coupon code.'];
    $stmt = db()->prepare('SELECT * FROM coupons WHERE code = ? LIMIT 1');
    $stmt->execute([$code]);
    $c = $stmt->fetch();
    if (!$c || (int) $c['is_active'] !== 1) {
        return ['valid' => false, 'coupon' => null, 'message' => 'This coupon code is not valid.'];
    }
    $now = time();
    if ($c['starts_at'] && strtotime($c['starts_at']) > $now) {
        return ['valid' => false, 'coupon' => null, 'message' => 'This coupon is not active yet.'];
    }
    if ($c['expires_at'] && strtotime($c['expires_at']) < $now) {
        return ['valid' => false, 'coupon' => null, 'message' => 'This coupon has expired.'];
    }
    if ($c['usage_limit'] !== null && (int) $c['used_count'] >= (int) $c['usage_limit']) {
        return ['valid' => false, 'coupon' => null, 'message' => 'This coupon has reached its usage limit.'];
    }
    if ((float) $c['min_order'] > 0 && $subtotal < (float) $c['min_order']) {
        return ['valid' => false, 'coupon' => null, 'message' => 'Minimum order of ' . format_price($c['min_order']) . ' required for this coupon.'];
    }
    if ($userId > 0) {
        $q = db()->prepare('SELECT COUNT(*) FROM orders WHERE user_id = ? AND status <> "cancelled"');
        $q->execute([$userId]);
        $orderCount = (int) $q->fetchColumn();
        if ((int) $c['first_order_only'] === 1 && $orderCount > 0) {
            return ['valid' => false, 'coupon' => null, 'message' => 'This coupon is only valid on your first order.'];
        }
        if ($c['per_user_limit'] !== null) {
            $q = db()->prepare('SELECT COUNT(*) FROM orders WHERE user_id = ? AND coupon_id = ? AND status <> "cancelled"');
            $q->execute([$userId, $c['id']]);
            if ((int) $q->fetchColumn() >= (int) $c['per_user_limit']) {
                return ['valid' => false, 'coupon' => null, 'message' => 'You have already used this coupon.'];
            }
        }
    }
    return ['valid' => true, 'coupon' => $c, 'message' => 'Coupon applied: ' . ($c['description'] ?: $c['code'])];
}

/* =========================================================================
 | Orders
 * ========================================================================= */
function order_statuses(): array
{
    return [
        'pending'            => 'Pending',
        'confirmed'          => 'Confirmed',
        'preparing'          => 'Preparing',
        'ready_for_delivery' => 'Ready for Delivery',
        'delivering'         => 'Delivering',
        'completed'          => 'Completed',
        'cancelled'          => 'Cancelled',
    ];
}

function status_label(string $status): string
{
    return order_statuses()[$status] ?? ucfirst($status);
}

function status_badge_class(string $status): string
{
    return match ($status) {
        'pending'            => 'badge-status-pending',
        'confirmed'          => 'badge-status-confirmed',
        'preparing'          => 'badge-status-preparing',
        'ready_for_delivery' => 'badge-status-ready',
        'delivering'         => 'badge-status-delivering',
        'completed'          => 'badge-status-completed',
        'cancelled'          => 'badge-status-cancelled',
        default              => 'badge-status-pending',
    };
}

function allowed_transitions(string $from): array
{
    return match ($from) {
        'pending'            => ['confirmed', 'cancelled'],
        'confirmed'          => ['preparing', 'cancelled'],
        'preparing'          => ['ready_for_delivery', 'cancelled'],
        'ready_for_delivery' => ['delivering', 'cancelled'],
        'delivering'         => ['completed'],
        default              => [],
    };
}

function can_transition(string $from, string $to): bool
{
    return in_array($to, allowed_transitions($from), true);
}

function customer_can_cancel(array $order): bool
{
    return in_array($order['status'], ['pending', 'confirmed'], true);
}

function generate_order_number(): string
{
    do {
        $num = 'SP' . date('ymd') . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
        $stmt = db()->prepare('SELECT id FROM orders WHERE order_number = ?');
        $stmt->execute([$num]);
    } while ($stmt->fetch());
    return $num;
}

/**
 * Create an order inside a transaction.
 * $delivery: customer_name, customer_phone, delivery_address, delivery_instructions, order_notes, payment_method
 * Returns ['success'=>bool,'message'=>string,'order_id'=>int|null,'order_number'=>string|null]
 */
function create_order(int $userId, array $delivery, ?string $couponCode): array
{
    $pdo = db();
    try {
        $pdo->beginTransaction();

        // Lock + revalidate every cart line against current DB price/availability
        $raw = cart_raw();
        if (!$raw) throw new RuntimeException('Your cart is empty.');
        $items = [];
        $stmt = $pdo->prepare('SELECT id, name, image, price, is_available FROM products WHERE id = ? FOR UPDATE');
        foreach ($raw as $pid => $qty) {
            $qty = (int) $qty;
            if ($qty < 1 || $qty > 50) throw new RuntimeException('Invalid quantity in cart.');
            $stmt->execute([(int) $pid]);
            $p = $stmt->fetch();
            if (!$p || (int) $p['is_available'] !== 1) throw new RuntimeException('One of your items is no longer available. Please review your cart.');
            $price = round((float) $p['price'], 2);
            if ($price < 0) throw new RuntimeException('Invalid product price.');
            $items[] = ['product_id' => (int) $p['id'], 'name' => $p['name'], 'image' => $p['image'], 'price' => $price, 'quantity' => $qty, 'line_total' => round($price * $qty, 2)];
        }
        $subtotal = round(array_sum(array_column($items, 'line_total')), 2);
        $minOrder = (float) setting('min_order_amount', '0');
        if ($minOrder > 0 && $subtotal < $minOrder) {
            throw new RuntimeException('Minimum order amount is ' . format_price($minOrder) . '.');
        }

        $coupon = null;
        $discount = 0.0;
        if ($couponCode) {
            $v = validate_coupon($couponCode, $subtotal, $userId);
            if (!$v['valid']) throw new RuntimeException($v['message']);
            $coupon = $v['coupon'];
            $discount = coupon_discount($coupon, $subtotal);
        }
        $deliveryFee = (float) setting('delivery_fee', '0');
        $freeOver = (float) setting('free_delivery_over', '0');
        if ($freeOver > 0 && $subtotal >= $freeOver) $deliveryFee = 0.0;
        $total = max(0, round($subtotal - $discount + $deliveryFee, 2));

        $method = $delivery['payment_method'];
        if ($method === 'cod' && setting('cod_enabled', '1') !== '1') throw new RuntimeException('Cash on delivery is currently disabled.');
        if ($method === 'manual_transfer' && setting('manual_payment_enabled', '0') !== '1') throw new RuntimeException('Manual payment is currently disabled.');

        $orderNumber = generate_order_number();
        $pdo->prepare('INSERT INTO orders (order_number, user_id, coupon_id, coupon_code, customer_name, customer_phone, delivery_address, delivery_instructions, order_notes,
                subtotal, discount, delivery_fee, total, payment_method, payment_status, status)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,"unpaid","pending")')
            ->execute([
                $orderNumber, $userId, $coupon['id'] ?? null, $coupon['code'] ?? null,
                $delivery['customer_name'], $delivery['customer_phone'], $delivery['delivery_address'],
                $delivery['delivery_instructions'] ?: null, $delivery['order_notes'] ?: null,
                $subtotal, $discount, $deliveryFee, $total, $method,
            ]);
        $orderId = (int) $pdo->lastInsertId();

        $ins = $pdo->prepare('INSERT INTO order_items (order_id, product_id, product_name, product_image, unit_price, quantity, line_total) VALUES (?,?,?,?,?,?,?)');
        $upd = $pdo->prepare('UPDATE products SET sold_count = sold_count + ? WHERE id = ?');
        foreach ($items as $it) {
            $ins->execute([$orderId, $it['product_id'], $it['name'], $it['image'], $it['price'], $it['quantity'], $it['line_total']]);
            $upd->execute([$it['quantity'], $it['product_id']]);
        }
        $pdo->prepare('INSERT INTO payments (order_id, method, amount, status) VALUES (?,?,?,"pending")')->execute([$orderId, $method, $total]);
        if ($coupon) {
            $pdo->prepare('UPDATE coupons SET used_count = used_count + 1 WHERE id = ?')->execute([$coupon['id']]);
        }
        $pdo->prepare('DELETE FROM cart_items WHERE user_id = ?')->execute([$userId]);
        $pdo->prepare('INSERT INTO notifications (user_id, title, message, link) VALUES (?,?,?,?)')
            ->execute([$userId, 'Order placed', "Your order #$orderNumber has been received and is pending confirmation.", 'order-details.php?id=' . $orderId]);

        $pdo->commit();
        unset($_SESSION['cart'], $_SESSION['coupon_code']);
        return ['success' => true, 'message' => 'Order placed successfully.', 'order_id' => $orderId, 'order_number' => $orderNumber];
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $msg = $ex instanceof RuntimeException ? $ex->getMessage() : (APP_DEBUG ? $ex->getMessage() : 'We could not place your order. Please try again.');
        return ['success' => false, 'message' => $msg, 'order_id' => null, 'order_number' => null];
    }
}

/** Change order status with transition rules; records timestamps + notification. */
function update_order_status(int $orderId, string $newStatus, ?string $reason = null): array
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? LIMIT 1');
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    if (!$order) return ['success' => false, 'message' => 'Order not found.'];
    if (!can_transition($order['status'], $newStatus)) {
        return ['success' => false, 'message' => 'Cannot change status from ' . status_label($order['status']) . ' to ' . status_label($newStatus) . '.'];
    }
    try {
        $pdo->beginTransaction();
        $extra = '';
        if ($newStatus === 'confirmed') $extra = ', confirmed_at = NOW()';
        if ($newStatus === 'completed') $extra = ', completed_at = NOW()';
        if ($newStatus === 'cancelled') $extra = ', cancelled_at = NOW(), cancel_reason = ' . $pdo->quote((string) $reason);
        $pdo->prepare("UPDATE orders SET status = ? $extra WHERE id = ?")->execute([$newStatus, $orderId]);
        if ($newStatus === 'completed' && $order['payment_method'] === 'cod') {
            $pdo->prepare('UPDATE orders SET payment_status = "paid" WHERE id = ?')->execute([$orderId]);
            $pdo->prepare('UPDATE payments SET status = "paid", paid_at = NOW() WHERE order_id = ? AND status = "pending"')->execute([$orderId]);
        }
        if ($newStatus === 'cancelled') {
            $pdo->prepare('UPDATE payments SET status = IF(status = "paid", "refunded", "failed") WHERE order_id = ?')->execute([$orderId]);
            if ($order['coupon_id']) {
                $pdo->prepare('UPDATE coupons SET used_count = GREATEST(0, used_count - 1) WHERE id = ?')->execute([$order['coupon_id']]);
            }
        }
        $pdo->prepare('INSERT INTO notifications (user_id, title, message, link) VALUES (?,?,?,?)')->execute([
            $order['user_id'], 'Order ' . status_label($newStatus),
            'Order #' . $order['order_number'] . ' is now "' . status_label($newStatus) . '".' . ($reason ? ' Reason: ' . $reason : ''),
            'order-details.php?id=' . $orderId,
        ]);
        $pdo->commit();
        return ['success' => true, 'message' => 'Order status updated to ' . status_label($newStatus) . '.'];
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return ['success' => false, 'message' => APP_DEBUG ? $ex->getMessage() : 'Could not update status.'];
    }
}

/* =========================================================================
 | Notifications
 * ========================================================================= */
function notify(int $userId, string $title, string $message, ?string $link = null): void
{
    db()->prepare('INSERT INTO notifications (user_id, title, message, link) VALUES (?,?,?,?)')->execute([$userId, $title, $message, $link]);
}

function unread_notifications_count(): int
{
    $u = current_user();
    if (!$u) return 0;
    $stmt = db()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$u['id']]);
    return (int) $stmt->fetchColumn();
}

/* =========================================================================
 | Image uploads
 * ========================================================================= */
/**
 * Validate & store an uploaded image. Returns relative path (e.g. assets/uploads/products/abc.jpg) or null if no file.
 * Throws RuntimeException on invalid upload.
 */
function handle_image_upload(array $file, string $subdir): ?string
{
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Image upload failed (error code ' . $file['error'] . ').');
    if ($file['size'] > MAX_UPLOAD_BYTES) throw new RuntimeException('Image must be smaller than ' . (MAX_UPLOAD_BYTES / 1024 / 1024) . ' MB.');
    if (!is_uploaded_file($file['tmp_name'])) throw new RuntimeException('Invalid upload.');

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!isset($allowed[$mime])) throw new RuntimeException('Only JPG, PNG and WEBP images are allowed.');
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) throw new RuntimeException('Invalid file extension.');
    if (@getimagesize($file['tmp_name']) === false) throw new RuntimeException('The uploaded file is not a valid image.');

    $subdir = in_array($subdir, ['products', 'banners', 'settings'], true) ? $subdir : 'products';
    $dir = UPLOAD_PATH . '/' . $subdir;
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) throw new RuntimeException('Upload directory is not writable.');
    $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) throw new RuntimeException('Could not save the uploaded image.');
    return 'assets/uploads/' . $subdir . '/' . $name;
}

/** Delete a previously uploaded file (only inside uploads dir). */
function delete_upload(?string $path): void
{
    if (!$path || !str_starts_with($path, 'assets/uploads/')) return;
    $full = realpath(ROOT_PATH . '/' . $path);
    if ($full && str_starts_with($full, realpath(UPLOAD_PATH)) && is_file($full)) {
        @unlink($full);
    }
}

/* =========================================================================
 | Components
 * ========================================================================= */
function render_stars(float $rating, string $size = ''): string
{
    $html = '<span class="stars ' . e($size) . '">';
    for ($i = 1; $i <= 5; $i++) {
        if ($rating >= $i) $html .= '<i class="bi bi-star-fill"></i>';
        elseif ($rating >= $i - 0.5) $html .= '<i class="bi bi-star-half"></i>';
        else $html .= '<i class="bi bi-star"></i>';
    }
    return $html . '</span>';
}

function render_product_card(array $p): void
{
    include ROOT_PATH . '/includes/product-card.php';
}
