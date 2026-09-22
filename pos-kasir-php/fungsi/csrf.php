<?php
if (!function_exists('csrf_get_token')) {
    function csrf_get_token(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_get_token(), ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('csrf_validate_token')) {
    function csrf_validate_token($token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        if (!is_string($sessionToken) || $sessionToken === '' || !is_string($token)) {
            return false;
        }
        return hash_equals($sessionToken, $token);
    }
}

if (!function_exists('csrf_guard')) {
    function csrf_guard(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }
        $token = $_POST['csrf_token'] ?? '';
        if (!csrf_validate_token($token)) {
            http_response_code(400);
            exit('Invalid CSRF token');
        }
    }
}

if (!function_exists('csrf_require_token')) {
    function csrf_require_token($token): void
    {
        if (!csrf_validate_token($token)) {
            http_response_code(400);
            exit('Invalid CSRF token');
        }
    }
}

if (!function_exists('session_enforce_timeout')) {
    function session_enforce_timeout(int $maxIdleSeconds = 1800): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['admin'])) {
            return;
        }
        $now = time();
        if (!empty($_SESSION['last_activity']) && ($now - (int) $_SESSION['last_activity']) > $maxIdleSeconds) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
            }
            session_destroy();
            return;
        }
        $_SESSION['last_activity'] = $now;
    }
}
