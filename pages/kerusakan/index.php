<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$q = trim($_GET['q'] ?? '');
$like = '%' . $q . '%';

$total = (int) $pdo->query('SELECT COUNT(*) FROM berita_acara_penghapusan')->fetchColumn();
$draft = (int) $pdo->query("SELECT COUNT(*) FROM berita_acara_penghapusan WHERE Status_BA = 'draft'")->fetchColumn();
$final = (int) $pdo->query("SELECT COUNT(*) FROM berita_acara_penghapusan WHERE Status_BA = 'final'")->fetchColumn();

if ($q !== '') {
    $stmt = $pdo->prepare(
        "SELECT ba.ID_BA, ba.Nomor_BA, ba.Tanggal_BA, ba.Alasan_Penghapusan, ba.Status_BA,
                b.Nama_Barang, b.Kode_Barang
         FROM berita_acara_penghapusan ba
         JOIN kerusakan k ON k.ID_Kerusakan = ba.ID_Kerusakan
         JOIN barang b ON b.ID_Barang = k.ID_Barang
         WHERE ba.Nomor_BA LIKE ? OR b.Nama_Barang LIKE ? OR b.Kode_Barang LIKE ? OR ba.Alasan_Penghapusan LIKE ?
         ORDER BY ba.ID_BA DESC"
    );
    $stmt->execute([$like, $like, $like, $like]);
} else {
    $stmt = $pdo->query(
        "SELECT ba.ID_BA, ba.Nomor_BA, ba.Tanggal_BA, ba.Alasan_Penghapusan, ba.Status_BA,
                b.Nama_Barang, b.Kode_Barang
         FROM berita_acara_penghapusan ba
         JOIN kerusakan k ON k.ID_Kerusakan = ba.ID_Kerusakan
         JOIN barang b ON b.ID_Barang = k.ID_Barang
         ORDER BY ba.ID_BA DESC"
    );
}
$dokumen = $stmt->fetchAll();

$pending = $pdo->query(
    "SELECT k.ID_Kerusakan, k.Tanggal_Lapor, k.Tingkat_Kerusakan, k.Status_Kerusakan,
            b.Nama_Barang, b.Kode_Barang
     FROM kerusakan k
     JOIN barang b ON b.ID_Barang = k.ID_Barang
     LEFT JOIN berita_acara_penghapusan ba ON ba.ID_Kerusakan = k.ID_Kerusakan
     WHERE ba.ID_BA IS NULL
     ORDER BY k.ID_Kerusakan DESC"
)->fetchAll();

$success = get_flash('success');
$error = get_flash('error');

$pageTitle = 'Berita Acara Penghapusan';
$currentPage = 'kerusakan';
$searchPlaceholder = 'Cari dokumen...';
require_once dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="flex items-start justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Berita Acara Penghapusan</h1>
        <p class="text-sm text-gray-500 mt-1">Kelola dokumen pencatatan penghapusan aset sekolah.</p>
    </div>
    <div class="flex gap-2">
        <a href="<?php echo e(url('pages/kerusakan/catat.php')); ?>" class="inline-flex items-center rounded-lg border border-gray-200 bg-white text-sm font-semibold px-4 py-2.5 text-gray-700 hover:bg-gray-50">
            Catat Kerusakan
        </a>
        <a href="<?php echo e(url('pages/kerusakan/ba_form.php')); ?>" class="inline-flex items-center rounded-lg bg-brand hover:bg-brand-dark text-white text-sm font-semibold px-4 py-2.5">
            + Buat Berita Acara
        </a>
    </div>
</div>

<?php if ($success): ?>
    <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-100 text-emerald-800 text-sm px-4 py-3"><?php echo e($success); ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="mb-4 rounded-lg bg-red-50 border border-red-100 text-red-700 text-sm px-4 py-3"><?php echo e($error); ?></div>
<?php endif; ?>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex items-center gap-4">
        <div class="w-11 h-11 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.75h16.5m-16.5 0A2.25 2.25 0 015.25 7.5h13.5a2.25 2.25 0 012.25 2.25m-16.5 0v7.5A2.25 2.25 0 005.25 19.5h13.5a2.25 2.25 0 002.25-2.25v-7.5"/></svg>
        </div>
        <div>
            <p class="text-[11px] font-semibold tracking-wide text-gray-500">TOTAL DOKUMEN</p>
            <p class="text-2xl font-bold"><?php echo $total; ?></p>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex items-center gap-4">
        <div class="w-11 h-11 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
            <p class="text-[11px] font-semibold tracking-wide text-gray-500">DRAFT</p>
            <p class="text-2xl font-bold"><?php echo $draft; ?></p>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex items-center gap-4">
        <div class="w-11 h-11 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
            <p class="text-[11px] font-semibold tracking-wide text-gray-500">FINAL / DISELESAIKAN</p>
            <p class="text-2xl font-bold"><?php echo $final; ?></p>
        </div>
    </div>
</div>

<?php if ($pending): ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
        <h2 class="font-semibold text-gray-900 mb-3">Barang rusak belum mempunyai berita acara</h2>
        <p class="text-xs text-gray-500 mb-3">Barang rusak tidak dihapus di sini. Buat Berita Acara, lalu finalkan agar aset menjadi nonaktif.</p>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-left text-xs uppercase text-gray-500 border-y border-gray-100 bg-[#FAFBFC]">
                    <tr>
                        <th class="px-3 py-2">Tanggal</th>
                        <th class="px-3 py-2">Kode Barang</th>
                        <th class="px-3 py-2">Nama Barang</th>
                        <th class="px-3 py-2">Tingkat</th>
                        <th class="px-3 py-2 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending as $row): ?>
                        <tr class="border-b border-gray-50">
                            <td class="px-3 py-2 text-gray-600"><?php echo e(format_tanggal($row['Tanggal_Lapor'])); ?></td>
                            <td class="px-3 py-2 font-medium text-gray-800"><?php echo e($row['Kode_Barang']); ?></td>
                            <td class="px-3 py-2"><?php echo e($row['Nama_Barang']); ?></td>
                            <td class="px-3 py-2"><?php echo e($row['Tingkat_Kerusakan'] ?: '-'); ?></td>
                            <td class="px-3 py-2 text-right">
                                <a href="<?php echo e(url('pages/kerusakan/ba_form.php?kerusakan=' . (int) $row['ID_Kerusakan'])); ?>" class="text-brand text-xs font-semibold">Buat BA</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-5 py-4 flex items-center justify-between gap-3">
        <h2 class="font-semibold text-gray-900">Daftar Dokumen</h2>
        <form method="get">
            <input type="search" name="q" value="<?php echo e($q); ?>" placeholder="Cari nomor, barang, alasan..."
                   class="rounded-lg border border-gray-200 py-2 px-3 text-sm w-64">
        </form>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-left text-xs uppercase tracking-wide text-gray-500 border-y border-gray-100 bg-[#FAFBFC]">
                <tr>
                    <th class="px-5 py-3 font-semibold">Nomor Berita Acara</th>
                    <th class="px-5 py-3 font-semibold">Tanggal</th>
                    <th class="px-5 py-3 font-semibold">Kode Barang</th>
                    <th class="px-5 py-3 font-semibold">Nama Barang</th>
                    <th class="px-5 py-3 font-semibold">Alasan Penghapusan</th>
                    <th class="px-5 py-3 font-semibold">Status</th>
                    <th class="px-5 py-3 font-semibold text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$dokumen): ?>
                    <tr><td colspan="7" class="px-5 py-10 text-center text-gray-500">Belum ada berita acara.</td></tr>
                <?php else: ?>
                    <?php foreach ($dokumen as $row): ?>
                        <tr class="border-b border-gray-50 hover:bg-gray-50/60">
                            <td class="px-5 py-3 font-medium"><?php echo e($row['Nomor_BA']); ?></td>
                            <td class="px-5 py-3 text-gray-600"><?php echo e(format_tanggal($row['Tanggal_BA'])); ?></td>
                            <td class="px-5 py-3 font-medium text-gray-800"><?php echo e($row['Kode_Barang']); ?></td>
                            <td class="px-5 py-3"><?php echo e($row['Nama_Barang']); ?></td>
                            <td class="px-5 py-3 text-gray-600"><?php echo e($row['Alasan_Penghapusan'] ?: '-'); ?></td>
                            <td class="px-5 py-3"><?php echo badge_ba($row['Status_BA']); ?></td>
                            <td class="px-5 py-3 text-right">
                                <?php if ($row['Status_BA'] === 'draft'): ?>
                                    <a href="<?php echo e(url('pages/kerusakan/ba_form.php?id=' . (int) $row['ID_BA'])); ?>" class="text-brand text-xs font-semibold">Edit</a>
                                <?php else: ?>
                                    <a href="<?php echo e(url('pages/kerusakan/ba_lihat.php?id=' . (int) $row['ID_BA'])); ?>" class="text-brand text-xs font-semibold">Lihat</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="px-5 py-3 text-xs text-gray-400">Menampilkan <?php echo count($dokumen); ?> dari <?php echo $total; ?> data</div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
