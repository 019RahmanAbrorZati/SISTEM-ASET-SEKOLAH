<?php
/**
 * Mengosongkan data percobaan agar web tampak seperti belum pernah dipakai.
 *
 * DIKOSONGKAN : barang, peminjam, peminjaman, pengembalian, kerusakan,
 *               berita acara, beserta foto aset di assets/img/barang.
 * DIPERTAHANKAN: akun admin, daftar kategori barang, dan daftar ruangan.
 *
 * Nomor urut (ID) ikut dikembalikan ke 1.
 *
 * Cara menjalankan lewat Command Prompt:
 *   cd C:\xampp\htdocs\WEB SARPRAS
 *   C:\xampp\php\php.exe database\reset_data.php YA
 *
 * Cara menjalankan lewat browser:
 *   http://localhost/WEB SARPRAS/database/reset_data.php?konfirmasi=YA
 *
 * Kata "YA" wajib ditulis agar skrip ini tidak terjalankan tanpa sengaja.
 */

require __DIR__ . '/../config/database.php';

$viaCli = PHP_SAPI === 'cli';
$eol    = $viaCli ? PHP_EOL : "<br>\n";

if (!$viaCli) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<pre style="font:14px/1.6 Consolas,monospace;padding:20px">';
}

$konfirmasi = $viaCli ? ($argv[1] ?? '') : ($_GET['konfirmasi'] ?? '');

if (strtoupper(trim($konfirmasi)) !== 'YA') {
    echo 'DIBATALKAN. Skrip ini menghapus seluruh data aset dan transaksi.' . $eol;
    echo 'Pastikan database sudah dicadangkan (phpMyAdmin > Export) sebelum melanjutkan.' . $eol . $eol;
    echo $viaCli
        ? 'Jalankan ulang dengan menambahkan kata YA di belakang perintah.' . $eol
        : 'Tambahkan ?konfirmasi=YA pada alamat untuk melanjutkan.' . $eol;
    exit;
}

// Urutan tidak masalah karena pengecekan relasi dimatikan sementara,
// tetapi tetap ditulis dari tabel anak ke tabel induk agar mudah dibaca.
$tabel = [
    'berita_acara_penghapusan',
    'detail_pengembalian',
    'pengembalian',
    'detail_peminjaman',
    'peminjaman',
    'kerusakan',
    'peminjam',
    'barang',
];

echo 'Jumlah baris sebelum dikosongkan:' . $eol;
foreach ($tabel as $t) {
    echo '  ' . str_pad($t, 26) . $pdo->query("SELECT COUNT(*) FROM `{$t}`")->fetchColumn() . $eol;
}
echo $eol;

$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
foreach ($tabel as $t) {
    $pdo->exec("TRUNCATE TABLE `{$t}`");
}
$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

echo 'Semua tabel di atas sudah dikosongkan dan nomor ID kembali ke 1.' . $eol . $eol;

// Hapus foto aset, sisakan berkas penanda folder.
$folderFoto = APP_ROOT . '/assets/img/barang';
$dikecualikan = ['.gitkeep', 'index.html', '.htaccess'];
$jumlahFoto = 0;

if (is_dir($folderFoto)) {
    foreach (scandir($folderFoto) as $berkas) {
        if ($berkas === '.' || $berkas === '..' || in_array($berkas, $dikecualikan, true)) {
            continue;
        }
        $path = $folderFoto . '/' . $berkas;
        if (is_file($path) && @unlink($path)) {
            $jumlahFoto++;
        }
    }
}
echo "Foto aset dihapus: {$jumlahFoto} berkas." . $eol . $eol;

// Ringkasan data yang sengaja dipertahankan.
echo 'Data yang dipertahankan:' . $eol;
foreach (['admin', 'kategori_barang', 'ruang'] as $t) {
    echo '  ' . str_pad($t, 26) . $pdo->query("SELECT COUNT(*) FROM `{$t}`")->fetchColumn() . ' baris' . $eol;
}

echo $eol . 'Selesai. Silakan buka kembali web-nya, semua halaman akan tampil kosong.' . $eol;

if (!$viaCli) {
    echo '</pre>';
}
