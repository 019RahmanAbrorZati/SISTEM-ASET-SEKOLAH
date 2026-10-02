<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$tab = $_GET['tab'] ?? 'kategori';
if (!in_array($tab, ['kategori', 'ruang'], true)) {
    $tab = 'kategori';
}

$q = trim($_GET['q'] ?? '');
$like = '%' . $q . '%';

if ($tab === 'kategori') {
    if ($q !== '') {
        $stmt = $pdo->prepare(
            'SELECT k.ID_Kategori, k.Nama_Kategori, k.Keterangan_Kategori,
                    COUNT(b.ID_Barang) AS jumlah_barang
             FROM kategori_barang k
             LEFT JOIN barang b ON b.ID_Kategori = k.ID_Kategori
             WHERE k.Nama_Kategori LIKE ? OR k.Keterangan_Kategori LIKE ?
             GROUP BY k.ID_Kategori, k.Nama_Kategori, k.Keterangan_Kategori
             ORDER BY k.Nama_Kategori ASC'
        );
        $stmt->execute([$like, $like]);
    } else {
        $stmt = $pdo->query(
            'SELECT k.ID_Kategori, k.Nama_Kategori, k.Keterangan_Kategori,
                    COUNT(b.ID_Barang) AS jumlah_barang
             FROM kategori_barang k
             LEFT JOIN barang b ON b.ID_Kategori = k.ID_Kategori
             GROUP BY k.ID_Kategori, k.Nama_Kategori, k.Keterangan_Kategori
             ORDER BY k.Nama_Kategori ASC'
        );
    }
} else {
    if ($q !== '') {
        $stmt = $pdo->prepare(
            'SELECT r.ID_Ruang, r.Nama_Ruang, r.Lokasi,
                    COUNT(b.ID_Barang) AS jumlah_barang
             FROM ruang r
             LEFT JOIN barang b ON b.ID_Ruang = r.ID_Ruang
             WHERE r.Nama_Ruang LIKE ? OR r.Lokasi LIKE ?
             GROUP BY r.ID_Ruang, r.Nama_Ruang, r.Lokasi
             ORDER BY r.Nama_Ruang ASC'
        );
        $stmt->execute([$like, $like]);
    } else {
        $stmt = $pdo->query(
            'SELECT r.ID_Ruang, r.Nama_Ruang, r.Lokasi,
                    COUNT(b.ID_Barang) AS jumlah_barang
             FROM ruang r
             LEFT JOIN barang b ON b.ID_Ruang = r.ID_Ruang
             GROUP BY r.ID_Ruang, r.Nama_Ruang, r.Lokasi
             ORDER BY r.Nama_Ruang ASC'
        );
    }
}

$rows = $stmt->fetchAll();
$success = get_flash('success');
$error = get_flash('error');

$pageTitle = 'Master Data';
$currentPage = 'master';
$searchPlaceholder = 'Cari kategori atau ruang...';
require_once dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="flex items-start justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Master Data</h1>
        <p class="text-sm text-gray-500 mt-1">Kelola kategori barang dan ruang/lokasi penyimpanan aset.</p>
    </div>
    <?php if ($tab === 'kategori'): ?>
        <a href="<?php echo e(url('pages/master/kategori_form.php')); ?>" class="inline-flex items-center gap-2 rounded-lg bg-brand hover:bg-brand-dark text-white text-sm font-semibold px-4 py-2.5">
            + Tambah Kategori
        </a>
    <?php else: ?>
        <a href="<?php echo e(url('pages/master/ruang_form.php')); ?>" class="inline-flex items-center gap-2 rounded-lg bg-brand hover:bg-brand-dark text-white text-sm font-semibold px-4 py-2.5">
            + Tambah Ruang
        </a>
    <?php endif; ?>
</div>

<?php if ($success): ?>
    <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-100 text-emerald-800 text-sm px-4 py-3"><?php echo e($success); ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="mb-4 rounded-lg bg-red-50 border border-red-100 text-red-700 text-sm px-4 py-3"><?php echo e($error); ?></div>
<?php endif; ?>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-5 pt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex gap-1 bg-gray-100 rounded-lg p-1 w-fit">
            <a href="<?php echo e(url('pages/master/index.php?tab=kategori')); ?>"
               class="px-4 py-1.5 rounded-md text-sm font-medium <?php echo $tab === 'kategori' ? 'bg-white text-brand shadow-sm' : 'text-gray-600'; ?>">
                Kategori
            </a>
            <a href="<?php echo e(url('pages/master/index.php?tab=ruang')); ?>"
               class="px-4 py-1.5 rounded-md text-sm font-medium <?php echo $tab === 'ruang' ? 'bg-white text-brand shadow-sm' : 'text-gray-600'; ?>">
                Ruang
            </a>
        </div>

        <form method="get" class="flex gap-2">
            <input type="hidden" name="tab" value="<?php echo e($tab); ?>">
            <input type="search" name="q" value="<?php echo e($q); ?>"
                   placeholder="<?php echo $tab === 'kategori' ? 'Cari nama kategori...' : 'Cari nama ruang...'; ?>"
                   class="rounded-lg border border-gray-200 py-2 px-3 text-sm w-56 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand">
            <button type="submit" class="rounded-lg bg-brand text-white text-sm font-semibold px-4 py-2">Filter</button>
        </form>
    </div>

    <div class="overflow-x-auto mt-2">
        <table class="w-full text-sm">
            <thead class="text-left text-xs uppercase tracking-wide text-gray-500 border-y border-gray-100 bg-[#FAFBFC]">
                <tr>
                    <th class="px-5 py-3 font-semibold">No</th>
                    <?php if ($tab === 'kategori'): ?>
                        <th class="px-5 py-3 font-semibold">Nama Kategori</th>
                        <th class="px-5 py-3 font-semibold">Keterangan</th>
                    <?php else: ?>
                        <th class="px-5 py-3 font-semibold">Nama Ruang</th>
                        <th class="px-5 py-3 font-semibold">Lokasi</th>
                    <?php endif; ?>
                    <th class="px-5 py-3 font-semibold">Jumlah Aset</th>
                    <th class="px-5 py-3 font-semibold text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$rows): ?>
                    <tr>
                        <td colspan="5" class="px-5 py-10 text-center text-gray-500">
                            Belum ada data. Gunakan tombol tambah di kanan atas.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $i => $row): ?>
                        <?php
                        $id = $tab === 'kategori' ? (int) $row['ID_Kategori'] : (int) $row['ID_Ruang'];
                        $editUrl = $tab === 'kategori'
                            ? url('pages/master/kategori_form.php?id=' . $id)
                            : url('pages/master/ruang_form.php?id=' . $id);
                        $dipakai = (int) $row['jumlah_barang'] > 0;
                        ?>
                        <tr class="border-b border-gray-50 hover:bg-gray-50/60">
                            <td class="px-5 py-3 text-gray-500"><?php echo $i + 1; ?></td>
                            <td class="px-5 py-3 font-medium text-gray-900">
                                <?php echo e($tab === 'kategori' ? $row['Nama_Kategori'] : $row['Nama_Ruang']); ?>
                            </td>
                            <td class="px-5 py-3 text-gray-600">
                                <?php echo e($tab === 'kategori' ? ($row['Keterangan_Kategori'] ?: '-') : ($row['Lokasi'] ?: '-')); ?>
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center rounded-full bg-brand/10 text-brand text-xs font-semibold px-2.5 py-1">
                                    <?php echo (int) $row['jumlah_barang']; ?> aset
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="<?php echo e($editUrl); ?>" class="p-2 rounded-lg text-gray-500 hover:bg-gray-100" title="Ubah">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487z"/></svg>
                                    </a>
                                    <form method="post" action="<?php echo e(url('pages/master/hapus.php')); ?>"
                                          onsubmit="return confirm('Hapus data ini? Data yang sudah dipakai aset tidak bisa dihapus.');">
                                        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                                        <input type="hidden" name="jenis" value="<?php echo e($tab); ?>">
                                        <input type="hidden" name="id" value="<?php echo $id; ?>">
                                        <button type="submit" class="p-2 rounded-lg text-red-500 hover:bg-red-50 <?php echo $dipakai ? 'opacity-40 cursor-not-allowed' : ''; ?>"
                                                <?php echo $dipakai ? 'disabled title="Masih dipakai data aset"' : 'title="Hapus"'; ?>>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673A2.25 2.25 0 0115.916 21.75H8.084A2.25 2.25 0 015.84 19.673L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="px-5 py-3 text-xs text-gray-400">
        Menampilkan <?php echo count($rows); ?> data
        <?php echo $tab === 'kategori' ? 'kategori' : 'ruang'; ?>.
        Data yang masih terhubung ke aset tidak dapat dihapus.
    </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
