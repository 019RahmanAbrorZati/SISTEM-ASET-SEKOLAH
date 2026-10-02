<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare(
    'SELECT ba.*, k.ID_Barang, k.Tanggal_Lapor, k.Jenis_Kerusakan, k.Tingkat_Kerusakan,
            b.Nama_Barang, b.Kode_Barang, kat.Nama_Kategori, a.Nama_Lengkap
     FROM berita_acara_penghapusan ba
     JOIN kerusakan k ON k.ID_Kerusakan = ba.ID_Kerusakan
     JOIN barang b ON b.ID_Barang = k.ID_Barang
     JOIN kategori_barang kat ON kat.ID_Kategori = b.ID_Kategori
     JOIN admin a ON a.ID_Admin = k.ID_Admin
     WHERE ba.ID_BA = ?
     LIMIT 1'
);
$stmt->execute([$id]);
$ba = $stmt->fetch();

if (!$ba) {
    set_flash('error', 'Berita acara tidak ditemukan.');
    redirect('pages/kerusakan/index.php');
}

$pageTitle = 'Lihat Berita Acara';
$currentPage = 'kerusakan';
require_once dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="flex items-start justify-between gap-4 mb-6 print:hidden">
    <h1 class="text-2xl font-bold text-gray-900">Berita Acara <?php echo e($ba['Nomor_BA']); ?></h1>
    <div class="flex gap-2">
        <a href="<?php echo e(url('pages/kerusakan/index.php')); ?>" class="px-4 py-2 rounded-lg border border-gray-200 text-sm font-medium">Kembali</a>
        <button type="button" onclick="window.print()" class="px-4 py-2 rounded-lg bg-brand text-white text-sm font-semibold">Cetak</button>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 px-10 py-12 max-w-3xl mx-auto text-center">
    <?php echo badge_ba($ba['Status_BA']); ?>
    <p class="text-xs font-semibold tracking-[0.2em] text-gray-700 mt-4">PEMERINTAH KABUPATEN/KOTA</p>
    <p class="text-sm font-bold text-gray-900 mt-1">DINAS PENDIDIKAN</p>
    <p class="text-base font-bold text-gray-900">SDN 1</p>
    <p class="text-[11px] text-gray-500 mt-1">Jl. Pendidikan No. 1</p>
    <div class="border-t-2 border-gray-800 mt-4 pt-4">
        <p class="font-bold underline">BERITA ACARA PENGHAPUSAN BARANG MILIK SEKOLAH</p>
        <p class="mt-2 text-sm">Nomor: <strong><?php echo e($ba['Nomor_BA']); ?></strong></p>
    </div>

    <p class="text-sm text-justify text-gray-700 mt-8 leading-relaxed">
        Pada hari ini, bertempat di SDN 1, telah dilakukan pemeriksaan terhadap Barang Milik Sekolah
        yang dihapus dari inventaris aktif dengan rincian sebagai berikut:
    </p>

    <div class="text-left text-sm mt-6 space-y-2 max-w-lg mx-auto">
        <p><span class="text-gray-500 w-40 inline-block">Nama Barang</span>: <?php echo e($ba['Nama_Barang']); ?></p>
        <p><span class="text-gray-500 w-40 inline-block">Kode Aset</span>: <?php echo e($ba['Kode_Barang']); ?></p>
        <p><span class="text-gray-500 w-40 inline-block">Kategori</span>: <?php echo e($ba['Nama_Kategori']); ?></p>
        <p><span class="text-gray-500 w-40 inline-block">Kondisi terakhir</span>: <?php echo e(label_kondisi($ba['Kondisi_Barang'])); ?></p>
        <p><span class="text-gray-500 w-40 inline-block">Tanggal penghapusan</span>: <?php echo e(format_tanggal($ba['Tanggal_BA'])); ?></p>
        <p><span class="text-gray-500 w-40 inline-block">Alasan</span>: <?php echo e($ba['Alasan_Penghapusan']); ?></p>
    </div>

    <p class="text-sm text-justify text-gray-700 mt-8 leading-relaxed">
        Demikian Berita Acara ini dibuat dengan sebenarnya. Data barang tidak dihapus permanen dari sistem,
        melainkan dinonaktifkan pada buku inventaris.
    </p>

    <div class="grid grid-cols-2 gap-8 mt-12 text-sm">
        <div>
            <p>Mengetahui/Menyetujui,</p>
            <p class="font-semibold">Kepala Sekolah</p>
            <div class="h-16"></div>
            <p>________________</p>
        </div>
        <div>
            <p><?php echo e(format_tanggal($ba['Tanggal_BA'])); ?></p>
            <p class="font-semibold">Petugas Inventaris</p>
            <div class="h-16"></div>
            <p><?php echo e($ba['Nama_Lengkap']); ?></p>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
