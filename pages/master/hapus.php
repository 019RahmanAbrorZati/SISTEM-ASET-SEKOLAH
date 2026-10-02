<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify($_POST['csrf_token'] ?? null)) {
    set_flash('error', 'Permintaan hapus tidak valid.');
    redirect('pages/master/index.php');
}

$jenis = $_POST['jenis'] ?? '';
$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0 || !in_array($jenis, ['kategori', 'ruang'], true)) {
    set_flash('error', 'Data yang akan dihapus tidak valid.');
    redirect('pages/master/index.php');
}

if ($jenis === 'kategori') {
    $cek = $pdo->prepare('SELECT COUNT(*) FROM barang WHERE ID_Kategori = ?');
    $cek->execute([$id]);
    if ((int) $cek->fetchColumn() > 0) {
        set_flash('error', 'Kategori tidak bisa dihapus karena masih dipakai data barang.');
        redirect('pages/master/index.php?tab=kategori');
    }

    try {
        $stmt = $pdo->prepare('DELETE FROM kategori_barang WHERE ID_Kategori = ?');
        $stmt->execute([$id]);
        set_flash('success', 'Kategori berhasil dihapus.');
    } catch (PDOException $e) {
        set_flash('error', 'Kategori tidak bisa dihapus karena masih terhubung ke data lain.');
    }
    redirect('pages/master/index.php?tab=kategori');
}

$cek = $pdo->prepare('SELECT COUNT(*) FROM barang WHERE ID_Ruang = ?');
$cek->execute([$id]);
if ((int) $cek->fetchColumn() > 0) {
    set_flash('error', 'Ruang tidak bisa dihapus karena masih dipakai data barang.');
    redirect('pages/master/index.php?tab=ruang');
}

try {
    $stmt = $pdo->prepare('DELETE FROM ruang WHERE ID_Ruang = ?');
    $stmt->execute([$id]);
    set_flash('success', 'Ruang berhasil dihapus.');
} catch (PDOException $e) {
    set_flash('error', 'Ruang tidak bisa dihapus karena masih terhubung ke data lain.');
}
redirect('pages/master/index.php?tab=ruang');
