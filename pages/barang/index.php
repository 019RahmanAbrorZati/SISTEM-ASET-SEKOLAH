<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$q = trim($_GET['q'] ?? '');
$filterKategori = (int) ($_GET['kategori'] ?? 0);
$filterKondisi = trim($_GET['kondisi'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;

$kategoriList = $pdo->query('SELECT ID_Kategori, Nama_Kategori FROM kategori_barang ORDER BY Nama_Kategori')->fetchAll();

$where = ["b.Status_Barang <> 'dihapus'"];
$params = [];

if ($q !== '') {
    $where[] = '(b.Kode_Barang LIKE ? OR b.Nama_Barang LIKE ?)';
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
}
if ($filterKategori > 0) {
    $where[] = 'b.ID_Kategori = ?';
    $params[] = $filterKategori;
}
if (in_array($filterKondisi, ['baik', 'rusak_ringan', 'rusak_berat'], true)) {
    $where[] = 'b.Kondisi = ?';
    $params[] = $filterKondisi;
}

$sqlWhere = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM barang b WHERE {$sqlWhere}");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

$listStmt = $pdo->prepare(
    "SELECT b.ID_Barang, b.Kode_Barang, b.Nama_Barang, b.Kondisi, b.Status_Barang, b.Jumlah, b.Satuan, b.Foto,
            k.Nama_Kategori, r.Nama_Ruang,
            " . sql_jumlah_dipinjam('b') . " AS jumlah_dipinjam
     FROM barang b
     JOIN kategori_barang k ON k.ID_Kategori = b.ID_Kategori
     JOIN ruang r ON r.ID_Ruang = b.ID_Ruang
     WHERE {$sqlWhere}
     ORDER BY b.ID_Barang DESC
     LIMIT {$perPage} OFFSET {$offset}"
);
$listStmt->execute($params);
$rows = $listStmt->fetchAll();

$success = get_flash('success');
$error = get_flash('error');
$from = $total === 0 ? 0 : $offset + 1;
$to = min($offset + $perPage, $total);

$pageTitle = 'Data Aset';
$currentPage = 'barang';
$searchPlaceholder = 'Cari aset...';
require_once dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="flex items-start justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Data Aset</h1>
        <p class="text-sm text-gray-500 mt-1">Kelola daftar inventaris aset sekolah.</p>
    </div>
    <a href="<?php echo e(url('pages/barang/tambah.php')); ?>" class="inline-flex items-center gap-2 rounded-lg bg-brand hover:bg-brand-dark text-white text-sm font-semibold px-4 py-2.5">
        + Tambah Aset Baru
    </a>
</div>

<?php if ($success): ?>
    <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-100 text-emerald-800 text-sm px-4 py-3"><?php echo e($success); ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="mb-4 rounded-lg bg-red-50 border border-red-100 text-red-700 text-sm px-4 py-3"><?php echo e($error); ?></div>
<?php endif; ?>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <form method="get" class="px-5 py-4 flex flex-col lg:flex-row lg:items-end gap-3">
        <div class="flex-1">
            <label class="block text-xs font-medium text-gray-500 mb-1">Cari berdasarkan</label>
            <input type="search" name="q" value="<?php echo e($q); ?>" placeholder="Kode aset atau nama..."
                   class="w-full rounded-lg border border-gray-200 py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand">
        </div>
        <div class="w-full lg:w-52">
            <label class="block text-xs font-medium text-gray-500 mb-1">Kategori</label>
            <select name="kategori" class="w-full rounded-lg border border-gray-200 py-2 px-3 text-sm bg-white">
                <option value="0">Semua Kategori</option>
                <?php foreach ($kategoriList as $kat): ?>
                    <option value="<?php echo (int) $kat['ID_Kategori']; ?>" <?php echo $filterKategori === (int) $kat['ID_Kategori'] ? 'selected' : ''; ?>>
                        <?php echo e($kat['Nama_Kategori']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="w-full lg:w-52">
            <label class="block text-xs font-medium text-gray-500 mb-1">Kondisi</label>
            <select name="kondisi" class="w-full rounded-lg border border-gray-200 py-2 px-3 text-sm bg-white">
                <option value="">Semua Kondisi</option>
                <option value="baik" <?php echo $filterKondisi === 'baik' ? 'selected' : ''; ?>>Baik</option>
                <option value="rusak_ringan" <?php echo $filterKondisi === 'rusak_ringan' ? 'selected' : ''; ?>>Rusak Ringan</option>
                <option value="rusak_berat" <?php echo $filterKondisi === 'rusak_berat' ? 'selected' : ''; ?>>Rusak</option>
            </select>
        </div>
        <button type="submit" class="rounded-lg bg-brand text-white text-sm font-semibold px-5 py-2 h-[38px]">Filter</button>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-left text-xs uppercase tracking-wide text-gray-500 border-y border-gray-100 bg-[#FAFBFC]">
                <tr>
                    <th class="px-5 py-3 font-semibold">Kode Aset</th>
                    <th class="px-5 py-3 font-semibold">Foto</th>
                    <th class="px-5 py-3 font-semibold">Nama Barang</th>
                    <th class="px-5 py-3 font-semibold">Kategori</th>
                    <th class="px-5 py-3 font-semibold">Jumlah</th>
                    <th class="px-5 py-3 font-semibold">Kondisi</th>
                    <th class="px-5 py-3 font-semibold">Lokasi</th>
                    <th class="px-5 py-3 font-semibold">Status</th>
                    <th class="px-5 py-3 font-semibold text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$rows): ?>
                    <tr>
                        <td colspan="9" class="px-5 py-10 text-center text-gray-500">
                            Belum ada data aset. Tambah aset baru, atau ubah filter pencarian.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <?php
                        $dipinjam = (int) $row['jumlah_dipinjam'];
                        $jumlah = (int) $row['Jumlah'];
                        $sisa = max(0, $jumlah - $dipinjam);
                        $satuan = $row['Satuan'] ?: 'unit';
                        $statusTampil = status_operasional($row);
                        ?>
                        <tr class="border-b border-gray-50 hover:bg-gray-50/60">
                            <td class="px-5 py-3 font-medium text-gray-800"><?php echo e($row['Kode_Barang']); ?></td>
                            <td class="px-5 py-3">
                                <?php if (!empty($row['Foto'])): ?>
                                    <img src="<?php echo e(url('assets/img/barang/' . $row['Foto'])); ?>" alt="<?php echo e($row['Nama_Barang']); ?>" class="w-12 h-12 rounded-lg object-cover border border-gray-100">
                                <?php else: ?>
                                    <div class="w-12 h-12 rounded-lg bg-gray-100 border border-gray-100 flex items-center justify-center text-[10px] text-gray-400">Tidak ada</div>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-3 text-gray-900"><?php echo e($row['Nama_Barang']); ?></td>
                            <td class="px-5 py-3 text-gray-600"><?php echo e($row['Nama_Kategori']); ?></td>
                            <td class="px-5 py-3 text-gray-700">
                                <span class="font-medium"><?php echo $sisa; ?></span>
                                <span class="text-gray-400">/ <?php echo $jumlah; ?></span>
                                <span class="text-gray-500"><?php echo e($satuan); ?></span>
                                <?php if ($dipinjam > 0): ?>
                                    <p class="text-[11px] text-blue-600 mt-0.5"><?php echo $dipinjam; ?> sedang dipinjam</p>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-3"><?php echo badge_kondisi($row['Kondisi']); ?></td>
                            <td class="px-5 py-3 text-gray-600"><?php echo e($row['Nama_Ruang']); ?></td>
                            <td class="px-5 py-3"><?php echo badge_status($statusTampil); ?></td>
                            <td class="px-5 py-3">
                                <div class="flex justify-end">
                                    <a href="<?php echo e(url('pages/barang/form.php?id=' . (int) $row['ID_Barang'])); ?>"
                                       class="p-2 rounded-lg text-gray-500 hover:bg-gray-100" title="Ubah">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487z"/></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="px-5 py-3 flex items-center justify-between gap-3 text-xs text-gray-500">
        <span>Menampilkan <?php echo $from; ?>–<?php echo $to; ?> dari <?php echo $total; ?> aset</span>
        <?php if ($totalPages > 1): ?>
            <div class="flex items-center gap-1">
                <?php
                $qs = $_GET;
                for ($p = 1; $p <= $totalPages; $p++):
                    $qs['page'] = $p;
                    $href = url('pages/barang/index.php') . '?' . http_build_query($qs);
                ?>
                    <a href="<?php echo e($href); ?>"
                       class="min-w-[28px] h-7 px-2 inline-flex items-center justify-center rounded-md <?php echo $p === $page ? 'bg-brand text-white' : 'text-gray-600 hover:bg-gray-100'; ?>">
                        <?php echo $p; ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
