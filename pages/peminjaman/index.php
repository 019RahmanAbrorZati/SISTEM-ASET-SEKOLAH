<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$q = trim($_GET['q'] ?? '');
$filter = $_GET['status'] ?? 'semua';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;

$where = ['1=1'];
$params = [];

if ($q !== '') {
    $where[] = '(pm.Nama LIKE ? OR b.Nama_Barang LIKE ? OR b.Kode_Barang LIKE ?)';
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($filter === 'dikembalikan') {
    $where[] = "p.Status_Peminjaman = 'dikembalikan'";
} elseif ($filter === 'dipinjam') {
    $where[] = "p.Status_Peminjaman = 'dipinjam' AND (p.Tanggal_Rencana_Kembali IS NULL OR p.Tanggal_Rencana_Kembali >= CURDATE())";
} elseif ($filter === 'terlambat') {
    $where[] = "p.Status_Peminjaman = 'dipinjam' AND p.Tanggal_Rencana_Kembali < CURDATE()";
}

$sqlWhere = implode(' AND ', $where);

$countStmt = $pdo->prepare(
    "SELECT COUNT(DISTINCT p.ID_Peminjaman)
     FROM peminjaman p
     JOIN peminjam pm ON pm.ID_Peminjam = p.ID_Peminjam
     JOIN detail_peminjaman d ON d.ID_Peminjaman = p.ID_Peminjaman
     JOIN barang b ON b.ID_Barang = d.ID_Barang
     WHERE {$sqlWhere}"
);
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

$listStmt = $pdo->prepare(
    "SELECT p.ID_Peminjaman, p.Tanggal_Pinjam, p.Tanggal_Rencana_Kembali, p.Status_Peminjaman,
            pm.Nama, pm.Status_Peminjam,
            GROUP_CONCAT(CONCAT(d.Jumlah_Pinjam, ' ', b.Nama_Barang) ORDER BY b.Nama_Barang SEPARATOR ', ') AS daftar_barang,
            pg.Tanggal_Kembali
     FROM peminjaman p
     JOIN peminjam pm ON pm.ID_Peminjam = p.ID_Peminjam
     JOIN detail_peminjaman d ON d.ID_Peminjaman = p.ID_Peminjaman
     JOIN barang b ON b.ID_Barang = d.ID_Barang
     LEFT JOIN pengembalian pg ON pg.ID_Peminjaman = p.ID_Peminjaman
     WHERE {$sqlWhere}
     GROUP BY p.ID_Peminjaman, p.Tanggal_Pinjam, p.Tanggal_Rencana_Kembali, p.Status_Peminjaman,
              pm.Nama, pm.Status_Peminjam, pg.Tanggal_Kembali
     ORDER BY p.ID_Peminjaman DESC
     LIMIT {$perPage} OFFSET {$offset}"
);
$listStmt->execute($params);
$rows = $listStmt->fetchAll();

$success = get_flash('success');
$error = get_flash('error');
$from = $total === 0 ? 0 : $offset + 1;
$to = min($offset + $perPage, $total);

$pageTitle = 'Peminjaman Aset';
$currentPage = 'peminjaman';
$searchPlaceholder = 'Cari peminjaman...';
require_once dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="flex items-start justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Peminjaman Aset</h1>
        <p class="text-sm text-gray-500 mt-1">Catat peminjaman dan pengembalian barang inventaris.</p>
    </div>
    <a href="<?php echo e(url('pages/peminjaman/pinjam.php')); ?>" class="inline-flex items-center gap-2 rounded-lg bg-brand hover:bg-brand-dark text-white text-sm font-semibold px-4 py-2.5">
        + Catat Peminjaman Baru
    </a>
</div>

<?php if ($success): ?>
    <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-100 text-emerald-800 text-sm px-4 py-3"><?php echo e($success); ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="mb-4 rounded-lg bg-red-50 border border-red-100 text-red-700 text-sm px-4 py-3"><?php echo e($error); ?></div>
<?php endif; ?>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <form method="get" class="px-5 py-4 flex flex-col sm:flex-row gap-3">
        <input type="search" name="q" value="<?php echo e($q); ?>" placeholder="Cari nama peminjam atau barang..."
               class="flex-1 rounded-lg border border-gray-200 py-2 px-3 text-sm">
        <select name="status" class="rounded-lg border border-gray-200 py-2 px-3 text-sm bg-white sm:w-52">
            <option value="semua" <?php echo $filter === 'semua' ? 'selected' : ''; ?>>Semua Status</option>
            <option value="dipinjam" <?php echo $filter === 'dipinjam' ? 'selected' : ''; ?>>Pinjam</option>
            <option value="terlambat" <?php echo $filter === 'terlambat' ? 'selected' : ''; ?>>Terlambat</option>
            <option value="dikembalikan" <?php echo $filter === 'dikembalikan' ? 'selected' : ''; ?>>Dikembalikan</option>
        </select>
        <button class="rounded-lg bg-brand text-white text-sm font-semibold px-4 py-2">Filter</button>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-left text-xs uppercase tracking-wide text-gray-500 border-y border-gray-100 bg-[#FAFBFC]">
                <tr>
                    <th class="px-5 py-3 font-semibold">Nama Peminjam</th>
                    <th class="px-5 py-3 font-semibold">Barang Dipinjam</th>
                    <th class="px-5 py-3 font-semibold">Tanggal Pinjam</th>
                    <th class="px-5 py-3 font-semibold">Tanggal Kembali</th>
                    <th class="px-5 py-3 font-semibold">Status</th>
                    <th class="px-5 py-3 font-semibold text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$rows): ?>
                    <tr>
                        <td colspan="6" class="px-5 py-10 text-center text-gray-500">Belum ada data peminjaman.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <?php $tampil = status_peminjaman_tampil($row); ?>
                        <tr class="border-b border-gray-50 hover:bg-gray-50/60">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-brand/10 text-brand flex items-center justify-center text-xs font-semibold">
                                        <?php echo e(strtoupper(substr($row['Nama'], 0, 1))); ?>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900"><?php echo e($row['Nama']); ?></p>
                                        <p class="text-xs text-gray-500"><?php echo e($row['Status_Peminjam']); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-gray-700"><?php echo e($row['daftar_barang']); ?></td>
                            <td class="px-5 py-3 text-gray-600"><?php echo e(format_tanggal($row['Tanggal_Pinjam'])); ?></td>
                            <td class="px-5 py-3">
                                <?php if ($tampil === 'dikembalikan'): ?>
                                    <span class="text-gray-600"><?php echo e(format_tanggal($row['Tanggal_Kembali'])); ?></span>
                                <?php elseif ($tampil === 'terlambat'): ?>
                                    <span class="text-red-600 font-medium">Terlambat</span>
                                <?php else: ?>
                                    <span class="text-gray-500"><?php echo e(format_tanggal($row['Tanggal_Rencana_Kembali'])); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-3"><?php echo badge_peminjaman($tampil === 'terlambat' ? 'dipinjam' : $tampil); ?></td>
                            <td class="px-5 py-3 text-right">
                                <?php if ($tampil !== 'dikembalikan'): ?>
                                    <a href="<?php echo e(url('pages/peminjaman/kembalikan.php?id=' . (int) $row['ID_Peminjaman'])); ?>"
                                       class="inline-flex rounded-lg bg-brand/10 text-brand text-xs font-semibold px-3 py-1.5 hover:bg-brand hover:text-white">
                                        Kembalikan
                                    </a>
                                <?php else: ?>
                                    <span class="text-xs text-gray-400">Selesai</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="px-5 py-3 flex items-center justify-between text-xs text-gray-500">
        <span>Menampilkan <?php echo $from; ?>–<?php echo $to; ?> dari <?php echo $total; ?> data</span>
        <?php if ($totalPages > 1): ?>
            <div class="flex gap-1">
                <?php $qs = $_GET; for ($p = 1; $p <= $totalPages; $p++): $qs['page'] = $p; ?>
                    <a class="min-w-[28px] h-7 inline-flex items-center justify-center rounded-md <?php echo $p === $page ? 'bg-brand text-white' : 'hover:bg-gray-100'; ?>"
                       href="<?php echo e(url('pages/peminjaman/index.php') . '?' . http_build_query($qs)); ?>"><?php echo $p; ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
