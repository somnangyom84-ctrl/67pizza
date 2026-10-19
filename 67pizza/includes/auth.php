<?php
declare(strict_types=1);

/** Currently authenticated user (or null). Cached per request. */
function current_user(): ?array
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }
    $user = null;
    $id = (int) ($_SESSION['user_id'] ?? 0);
    if ($id > 0) {
        $stmt = db()->prepare('SELECT id, name, email, phone, address, role, is_active, created_at FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row && (int) $row['is_active'] === 1) {
            $user = $row;
        } else {
            // deactivated or deleted account -> kill session
            unset($_SESSION['user_id'], $_SESSION['user_role']);
        }
    }
    return $user;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    $u = current_user();
    return $u !== null && $u['role'] === 'admin';
}

/** Force login for customer pages. */
function require_login(): void
{
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? url('index.php');
        flash('warning', 'Please log in to continue.');
        redirect(url('login.php'));
    }
}

/** Force admin role for admin pages (server-side enforcement). */
function require_admin(): void
{
    if (!is_admin()) {
        if (is_ajax()) {
            json_response(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        unset($_SESSION['user_id']);
        redirect(url('admin/login.php'));
    }
}

function client_ip(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
}

/** Login throttling helpers ------------------------------------------- */
function login_blocked(string $identifier): bool
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM login_attempts WHERE identifier = ? AND ip_address = ? AND attempted_at > (NOW() - INTERVAL ? MINUTE)');
    $stmt->execute([$identifier, client_ip(), LOGIN_LOCK_MINUTES]);
    return (int) $stmt->fetchColumn() >= MAX_LOGIN_ATTEMPTS;
}

function record_failed_login(string $identifier): void
{
    db()->prepare('INSERT INTO login_attempts (identifier, ip_address, attempted_at) VALUES (?, ?, NOW())')
        ->execute([$identifier, client_ip()]);
    // housekeeping
    db()->exec('DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL 1 DAY)');
}

function clear_failed_logins(string $identifier): void
{
    db()->prepare('DELETE FROM login_attempts WHERE identifier = ? AND ip_address = ?')->execute([$identifier, client_ip()]);
}

/**
 * Attempt login. Returns ['success'=>bool,'message'=>string,'user'=>array|null]
 */
function attempt_login(string $email, string $password, bool $adminOnly = false): array
{
    $email = mb_strtolower(trim($email));
    if ($email === '' || $password === '') {
        return ['success' => false, 'message' => 'Email and password are required.'];
    }
    if (login_blocked($email)) {
        return ['success' => false, 'message' => 'Too many failed attempts. Please wait ' . LOGIN_LOCK_MINUTES . ' minutes and try again.'];
    }
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        record_failed_login($email);
        return ['success' => false, 'message' => 'Invalid email or password.'];
    }
    if ((int) $user['is_active'] !== 1) {
        return ['success' => false, 'message' => 'Your account has been deactivated. Please contact support.'];
    }
    if ($adminOnly && $user['role'] !== 'admin') {
        record_failed_login($email);
        return ['success' => false, 'message' => 'You are not authorized to access the admin area.'];
    }
    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
    }
    clear_failed_logins($email);

    // Prevent session fixation
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['login_time'] = time();
    unset($_SESSION['csrf_token']);
    db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([$user['id']]);

    if ($user['role'] === 'customer') {
        cart_merge_session_into_db((int) $user['id']);
    }
    return ['success' => true, 'message' => 'Welcome back, ' . $user['name'] . '!', 'user' => $user];
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/** Create a customer account. Returns ['success'=>bool,'message'=>string,'id'=>int|null] */
function register_user(array $data): array
{
    $email = mb_strtolower(trim($data['email']));
    $stmt = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'An account with this email already exists.'];
    }
    $ins = db()->prepare('INSERT INTO users (name, email, phone, address, password_hash, role, is_active) VALUES (?, ?, ?, ?, ?, "customer", 1)');
    $ins->execute([
        $data['name'],
        $email,
        $data['phone'],
        $data['address'],
        password_hash($data['password'], PASSWORD_DEFAULT),
    ]);
    $id = (int) db()->lastInsertId();
    notify($id, 'Welcome to 67pizza!', 'Thanks for joining. Use code WELCOME20 for 20% off your first order.', 'menu.php');
    return ['success' => true, 'message' => 'Account created successfully.', 'id' => $id];
}
