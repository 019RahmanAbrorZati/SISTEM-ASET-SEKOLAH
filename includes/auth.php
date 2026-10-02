<?php
/**
 * Middleware: halaman di dalam sistem hanya boleh diakses setelah login.
 * Include file ini di paling atas setiap halaman terproteksi.
 */

require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/functions.php';

if (empty($_SESSION['admin_id'])) {
    set_flash('error', 'Silakan masuk terlebih dahulu.');
    header('Location: ' . url('login.php'));
    exit;
}
