<?php
/**
 * Mengatur akun admin (username + password) untuk Sistem Aset SDN 1.
 *
 * Jalankan sekali dari Command Prompt:
 *   cd C:\xampp\htdocs\WEB SARPRAS
 *   C:\xampp\php\php.exe database\seed_admin.php
 *
 * Password disimpan sebagai hash bcrypt (password_hash), bukan teks biasa,
 * karena login memverifikasinya dengan password_verify().
 */

$usernameBaru = 'SDN 1';
$passwordBaru = 'GANTI_SANDI_INI';
$namaLengkap  = 'Administrator';

// Username lama yang mungkin masih tersimpan di database.
$usernameLama = 'irma';

if ($passwordBaru === 'GANTI_SANDI_INI') {
    exit('Ubah dulu nilai $passwordBaru di file ini sebelum dijalankan.' . PHP_EOL);
}

$pdo = new PDO(
    'mysql:host=127.0.0.1;dbname=sarpras;charset=utf8mb4',
    'root',
    '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

// Cari baris admin: pakai username baru, username lama, atau admin pertama.
$stmt = $pdo->prepare('SELECT ID_Admin FROM admin WHERE Username = ? OR Username = ? ORDER BY ID_Admin LIMIT 1');
$stmt->execute([$usernameBaru, $usernameLama]);
$idAdmin = $stmt->fetchColumn();

if ($idAdmin === false) {
    $idAdmin = $pdo->query('SELECT ID_Admin FROM admin ORDER BY ID_Admin LIMIT 1')->fetchColumn();
}

$hash = password_hash($passwordBaru, PASSWORD_DEFAULT);

if ($idAdmin === false) {
    $insert = $pdo->prepare('INSERT INTO admin (Username, Password, Nama_Lengkap) VALUES (?, ?, ?)');
    $insert->execute([$usernameBaru, $hash, $namaLengkap]);
    echo "Akun admin baru dibuat." . PHP_EOL;
} else {
    $update = $pdo->prepare('UPDATE admin SET Username = ?, Password = ? WHERE ID_Admin = ?');
    $update->execute([$usernameBaru, $hash, $idAdmin]);
    echo "Akun admin (ID {$idAdmin}) diperbarui." . PHP_EOL;
}

// Verifikasi langsung dari database.
$cek = $pdo->prepare('SELECT Password FROM admin WHERE Username = ? LIMIT 1');
$cek->execute([$usernameBaru]);
$tersimpan = $cek->fetchColumn();

echo "Username : {$usernameBaru}" . PHP_EOL;
echo "Verifikasi login: " . ($tersimpan && password_verify($passwordBaru, $tersimpan) ? 'ok' : 'GAGAL') . PHP_EOL;
