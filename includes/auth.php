<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Memastikan user sudah login.
 */
function require_login()
{
    if (!isset($_SESSION['user_id'])) {
        // Jika BASE_URL terdefinisi di config.php, gunakan itu.
        // Jika tidak, fallback ke path absolut.
        if (defined('BASE_URL')) {
            $redirect_url = rtrim(BASE_URL, '/') . '/admin/login.php';
        } else {
            $redirect_url = '/cafe-order/admin/login.php';
        }

        header("Location: " . $redirect_url);
        exit;
    }
}

/**
 * Mengecek apakah user memiliki role tertentu.
 */
function require_role($roles)
{
    require_login();

    if (!is_array($roles)) {
        $roles = [$roles];
    }

    if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $roles)) {
        http_response_code(403);
        echo "<h1>403 - Akses Ditolak</h1>";
        echo "<p>Anda tidak memiliki izin untuk mengakses halaman ini.</p>";
        exit;
    }
}

/**
 * Mengecek status login.
 */
function is_logged_in()
{
    return isset($_SESSION['user_id']);
}

/**
 * Logout.
 */
function logout()
{
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();
}