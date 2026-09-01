<?php
// 1. Deteksi Protokol
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";

// 2. Deteksi Host
if (!empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
    $host = $_SERVER['HTTP_X_FORWARDED_HOST'];
} elseif (!empty($_SERVER['HTTP_HOST'])) {
    $host = $_SERVER['HTTP_HOST'];
} else {
    $host = $_SERVER['SERVER_NAME'];
}

// 3. Deteksi Otomatis Sub-Folder Proyek
// Jika diakses via Localhost biasa -> /cafe-order/
// Jika diakses via Dev Tunnels langsung ke folder -> /
$script_name = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
if (strpos($script_name, '/cafe-order/') !== false) {
    $base_folder = '/cafe-order/';
} else {
    $base_folder = '/';
}

// 4. Set BASE_URL
define('BASE_URL', $protocol . $host . $base_folder);