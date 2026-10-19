<?php
declare(strict_types=1);

/** Return (and lazily create) the CSRF token for this session. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Hidden input for forms. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** Validate token from POST body or X-CSRF-Token header. */
function csrf_valid(): bool
{
    $sent = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return is_string($sent) && $sent !== '' && hash_equals(csrf_token(), $sent);
}

/** Abort the request when the CSRF token is missing/invalid. */
function verify_csrf(): void
{
    if (csrf_valid()) {
        return;
    }
    if (is_ajax()) {
        json_response(['success' => false, 'message' => 'Your session expired. Please refresh the page and try again.'], 419);
    }
    flash('danger', 'Security token mismatch. Please try again.');
    redirect($_SERVER['HTTP_REFERER'] ?? url('index.php'));
}
