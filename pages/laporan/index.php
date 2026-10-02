<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/laporan.php';

$filter = laporan_input();
$rekap = laporan_rekap($pdo, $filter);

$tahunList = $pdo->query(
    "SELECT DISTINCT Tahun_Pengadaan FROM barang
     WHERE Tahun_Pengadaan IS NOT NULL
     ORDER BY Tahun_Pengadaan DESC"
)->fetchAll(PDO::FETCH_COLUMN);
$kategoriList = $pdo->query('SELECT ID_Kategori, Nama_Kategori FROM kategori_barang ORDER BY Nama_Kategori')->fetchAll();

$queryString = http_build_query(array_filter($filter, static function ($v) {
    return $v !== '' && $v !== 0 && $v !== '0';
}));

$pageTitle = 'Laporan Akreditasi';
$currentPage = 'laporan';
require_once dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="flex items-start justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Laporan Akreditasi</h1>
        <p class="text-sm text-gray-500 mt-1">Ringkasan data aset sekolah untuk keperluan laporan akreditasi.</p>
    </div>
    <a href="<?php echo e(url('pages/laporan/export_pdf.php') . ($queryString ? '?' . $queryString : '')); ?>"
       class="inline-flex items-center gap-2 rounded-lg bg-brand hover:bg-brand-dark text-white text-sm font-semibold px-4 py-2.5">
        Export ke PDF
    </a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
    <p class="text-xs font-semibold tracking-wide text-gray-500 mb-3">FILTER LAPORAN</p>
    <form method="get" class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <div>
            <label class="block text-xs text-gray-500 mb-1">Periode / Tahun pengadaan</label>
            <select name="tahun" class="w-full rounded-lg border border-gray-200 py-2 px-3 text-sm bg-white">
                <option value="">Semua tahun</option>
                <?php foreach ($tahunList as $th): ?>
                    <option value="<?php echo e((string) $th); ?>" <?php echo $filter['tahun'] === (string) $th ? 'selected' : ''; ?>>
                        <?php echo e((string) $th); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Kategori aset</label>
            <select name="kategori" class="w-full rounded-lg border border-gray-200 py-2 px-3 text-sm bg-white">
                <option value="0">Semua kategori</option>
                <?php foreach ($kategoriList as $kat): ?>
                    <option value="<?php echo (int) $kat['ID_Kategori']; ?>" <?php echo $filter['kategori'] === (int) $kat['ID_Kategori'] ? 'selected' : ''; ?>>
                        <?php echo e($kat['Nama_Kategori']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Kondisi aset</label>
            <select name="kondisi" class="w-full rounded-lg border border-gray-200 py-2 px-3 text-sm bg-white">
                <option value="">Semua kondisi</option>
                <option value="baik" <?php echo $filter['kondisi'] === 'baik' ? 'selected' : ''; ?>>Hanya kondisi baik</option>
                <option value="rusak_ringan" <?php echo $filter['kondisi'] === 'rusak_ringan' ? 'selected' : ''; ?>>Rusak ringan</option>
                <option value="rusak_berat" <?php echo $filter['kondisi'] === 'rusak_berat' ? 'selected' : ''; ?>>Rusak berat</option>
            </select>
        </div>
        <div class="flex items-end">
            <button class="w-full rounded-lg bg-brand text-white text-sm font-semibold py-2">Terapkan</button>
        </div>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-5 py-4">
        <h2 class="font-semibold text-gray-900">Rekapitulasi Sarana dan Prasarana</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-left text-xs uppercase tracking-wide text-gray-500 border-y border-gray-100 bg-[#FAFBFC]">
                <tr>
                    <th class="px-5 py-3 font-semibold">No</th>
                    <th class="px-5 py-3 font-semibold">Jenis Prasarana / Sarana</th>
                    <th class="px-5 py-3 font-semibold">Standar Minimal</th>
                    <th class="px-5 py-3 font-semibold">Kondisi Sebenarnya</th>
                    <th class="px-5 py-3 font-semibold">Status Kesesuaian</th>
                    <th class="px-5 py-3 font-semibold text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$rekap): ?>
                    <tr><td colspan="6" class="px-5 py-10 text-center text-gray-500">Belum ada kategori. Tambah master data kategori terlebih dahulu.</td></tr>
                <?php else: ?>
                    <?php foreach ($rekap as $i => $row): ?>
                        <tr class="border-b border-gray-50 hover:bg-gray-50/60">
                            <td class="px-5 py-3 text-gray-500"><?php echo $i + 1; ?></td>
                            <td class="px-5 py-3 font-medium text-gray-900"><?php echo e($row['Nama_Kategori']); ?></td>
                            <td class="px-5 py-3 text-gray-600"><?php echo e($row['standar']); ?></td>
                            <td class="px-5 py-3 text-gray-600"><?php echo e($row['kondisi_riil']); ?></td>
                            <td class="px-5 py-3"><?php echo badge_kesesuaian($row['status_sesuai'], $row['status_label']); ?></td>
                            <td class="px-5 py-3 text-right">
                                <a href="<?php echo e(url('pages/barang/index.php?kategori=' . (int) $row['ID_Kategori'])); ?>" class="text-brand text-xs font-semibold">Lihat rincian data</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
