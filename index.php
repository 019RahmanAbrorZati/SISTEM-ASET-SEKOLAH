<?php
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Ringkasan Dasbor';
$currentPage = 'dasbor';
$searchPlaceholder = 'Cari kode atau nama aset...';

function count_safe(PDO $pdo, string $sql, array $params = []): int
{
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

$totalAset = count_safe($pdo, "SELECT COUNT(*) FROM barang WHERE Status_Barang <> 'dihapus'");
$sedangDipinjam = count_safe($pdo, "SELECT COUNT(*) FROM barang WHERE Status_Barang = 'dipinjam'");
$perluPerbaikan = count_safe($pdo, "SELECT COUNT(*) FROM barang WHERE Status_Barang = 'perlu_perbaikan'");
$siapAkreditasi = $totalAset > 0
    ? (int) round((($totalAset - $perluPerbaikan) / $totalAset) * 100)
    : 0;

$kategoriDist = [];
try {
    $kategoriDist = $pdo->query(
        "SELECT k.Nama_Kategori, COUNT(b.ID_Barang) AS jumlah
         FROM kategori_barang k
         LEFT JOIN barang b ON b.ID_Kategori = k.ID_Kategori AND b.Status_Barang <> 'dihapus'
         GROUP BY k.ID_Kategori, k.Nama_Kategori
         ORDER BY jumlah DESC, k.Nama_Kategori
         LIMIT 5"
    )->fetchAll();
} catch (PDOException $e) {
    $kategoriDist = [];
}
$maxKat = 1;
foreach ($kategoriDist as $kd) {
    $maxKat = max($maxKat, (int) $kd['jumlah']);
}

$aktivitas = [];
try {
    $aktivitas = $pdo->query(
        "(SELECT 'pinjam' AS tipe, p.Tanggal_Pinjam AS tgl,
                 CONCAT(pm.Nama, ' meminjam barang') AS teks, p.Status_Peminjaman AS status
          FROM peminjaman p JOIN peminjam pm ON pm.ID_Peminjam = p.ID_Peminjam
          ORDER BY p.ID_Peminjaman DESC LIMIT 3)
         UNION ALL
         (SELECT 'ba' AS tipe, ba.Tanggal_BA AS tgl,
                 CONCAT('BA ', ba.Nomor_BA) AS teks, ba.Status_BA AS status
          FROM berita_acara_penghapusan ba
          ORDER BY ba.ID_BA DESC LIMIT 3)
         ORDER BY tgl DESC LIMIT 5"
    )->fetchAll();
} catch (PDOException $e) {
    $aktivitas = [];
}

$jumlahTerlambat = jumlah_peminjaman_terlambat($pdo);
$daftarTerlambat = peminjaman_terlambat($pdo, 6);

require_once __DIR__ . '/includes/header.php';
?>

<div class="flex items-start justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Ringkasan Dasbor</h1>
        <p class="text-sm text-gray-500 mt-1">Gambaran umum status aset SDN 1 hari ini.</p>
    </div>
    <a href="<?php echo e(url('pages/barang/tambah.php')); ?>" class="inline-flex items-center gap-2 rounded-lg bg-brand hover:bg-brand-dark text-white text-sm font-semibold px-4 py-2.5">
        + Tambah Aset
    </a>
</div>

<?php if ($jumlahTerlambat > 0): ?>
    <div class="mb-6 rounded-xl border border-red-100 bg-red-50 p-5">
        <div class="flex items-start justify-between gap-3 mb-3">
            <div>
                <h2 class="font-semibold text-red-800">Pengingat peminjaman terlambat</h2>
                <p class="text-sm text-red-700 mt-0.5"><?php echo $jumlahTerlambat; ?> peminjaman melewati tanggal rencana kembali.</p>
            </div>
            <a href="<?php echo e(url('pages/peminjaman/index.php?status=terlambat')); ?>" class="text-xs font-semibold text-red-700 shrink-0">Lihat semua</a>
        </div>
        <ul class="space-y-2">
            <?php foreach ($daftarTerlambat as $telat): ?>
                <li class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 rounded-lg bg-white/80 px-3 py-2 text-sm">
                    <div>
                        <p class="font-medium text-gray-900"><?php echo e($telat['Nama']); ?> <span class="text-gray-400 font-normal">(<?php echo e($telat['Status_Peminjam']); ?>)</span></p>
                        <p class="text-xs text-gray-600"><?php echo e($telat['daftar_barang']); ?></p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-red-600 font-medium">Jatuh tempo <?php echo e(format_tanggal($telat['Tanggal_Rencana_Kembali'])); ?></span>
                        <a href="<?php echo e(url('pages/peminjaman/kembalikan.php?id=' . (int) $telat['ID_Peminjaman'])); ?>" class="text-xs font-semibold text-brand">Kembalikan</a>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-semibold tracking-wide text-gray-500">TOTAL ASET</p>
                <p class="text-3xl font-bold mt-2"><?php echo number_format($totalAset); ?></p>
                <p class="text-xs text-gray-400 mt-2">Data inventaris aktif</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-brand/10 text-brand flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.75h16.5m-16.5 0A2.25 2.25 0 015.25 7.5h13.5a2.25 2.25 0 012.25 2.25m-16.5 0v7.5A2.25 2.25 0 005.25 19.5h13.5a2.25 2.25 0 002.25-2.25v-7.5"/></svg>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-semibold tracking-wide text-gray-500">SEDANG DIPINJAM</p>
                <p class="text-3xl font-bold mt-2"><?php echo number_format($sedangDipinjam); ?></p>
                <p class="text-xs text-gray-400 mt-2">Barang belum dikembalikan</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-4.03-4.5-9-4.5s-9 2.015-9 4.5m18 0c0 2.485-4.03 4.5-9 4.5s-9-2.015-9-4.5m18 0v7.5c0 2.485-4.03 4.5-9 4.5s-9-2.015-9-4.5v-7.5"/></svg>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-semibold tracking-wide text-gray-500">PERLU PERBAIKAN</p>
                <p class="text-3xl font-bold mt-2 text-red-600"><?php echo number_format($perluPerbaikan); ?></p>
                <p class="text-xs text-red-500 mt-2">! Segera tindak lanjuti</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-red-50 text-red-500 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085"/></svg>
            </div>
        </div>
    </div>

    <div class="bg-brand rounded-xl shadow-sm p-5 text-white">
        <div class="flex items-start justify-between">
            <div class="w-full">
                <p class="text-xs font-semibold tracking-wide text-white/80">KESIAPAN AKREDITASI</p>
                <p class="text-3xl font-bold mt-2"><?php echo $siapAkreditasi; ?>%</p>
                <p class="text-xs text-white/80 mt-2">Dokumen aset lengkap</p>
                <div class="mt-3 h-1.5 rounded-full bg-white/20">
                    <div class="h-1.5 rounded-full bg-white" style="width: <?php echo max(0, min(100, $siapAkreditasi)); ?>%"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
    <div class="xl:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold text-gray-900">Aktivitas Terbaru</h2>
            <a href="<?php echo e(url('pages/peminjaman/index.php')); ?>" class="text-xs font-semibold text-brand">Lihat semua</a>
        </div>
        <?php if (!$aktivitas): ?>
            <p class="text-sm text-gray-500">Belum ada aktivitas. Data akan muncul setelah peminjaman, aset, dan berita acara dicatat.</p>
        <?php else: ?>
            <ul class="space-y-3">
                <?php foreach ($aktivitas as $item): ?>
                    <li class="flex items-start justify-between gap-3 text-sm">
                        <div>
                            <p class="text-gray-800"><?php echo e($item['teks']); ?></p>
                            <p class="text-xs text-gray-400 mt-0.5"><?php echo e(format_tanggal($item['tgl'])); ?></p>
                        </div>
                        <span class="text-[11px] font-semibold text-gray-500"><?php echo e($item['status']); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <div class="space-y-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h2 class="font-semibold text-gray-900 mb-4">Aksi Cepat</h2>
            <div class="grid grid-cols-2 gap-3">
                <a href="<?php echo e(url('pages/barang/tambah.php')); ?>" class="rounded-xl border border-gray-200 p-4 text-center text-sm text-gray-700 hover:bg-gray-50">Tambah Aset</a>
                <a href="<?php echo e(url('pages/laporan/index.php')); ?>" class="rounded-xl border border-gray-200 p-4 text-center text-sm text-gray-700 hover:bg-gray-50">Laporan PDF</a>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h2 class="font-semibold text-gray-900 mb-3">Distribusi Kategori</h2>
            <?php if (!$kategoriDist): ?>
                <p class="text-sm text-gray-500">Akan terisi setelah data barang dan kategori tersedia.</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($kategoriDist as $kd): ?>
                        <?php $pct = $maxKat > 0 ? round(((int) $kd['jumlah'] / $maxKat) * 100) : 0; ?>
                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <span class="text-gray-600"><?php echo e($kd['Nama_Kategori']); ?></span>
                                <span class="font-semibold"><?php echo (int) $kd['jumlah']; ?> unit</span>
                            </div>
                            <div class="h-1.5 rounded-full bg-gray-100">
                                <div class="h-1.5 rounded-full bg-brand" style="width: <?php echo $pct; ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
