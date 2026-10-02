<?php
/**
 * Koneksi PDO ke MySQL (XAMPP).
 * Ubah $dbPass jika MySQL Anda memakai password.
 */

define('APP_ROOT', dirname(__DIR__));

$dbHost = '127.0.0.1';
$dbName = 'sarpras';
$dbUser = 'root';
$dbPass = '';
$dbCharset = 'utf8mb4';

$dsn = "mysql:host={$dbHost};dbname={$dbName};charset={$dbCharset}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
} catch (PDOException $e) {
    exit('Koneksi database gagal. Pastikan MySQL berjalan dan database <strong>sarpras</strong> sudah diimpor.');
}
