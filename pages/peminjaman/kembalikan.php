<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$id = (int) ($_GET['id'] ?? 0);
$error = null;

$stmt = $pdo->prepare(
    'SELECT p.*, pm.Nama, pm.Status_Peminjam, pm.Kontak
     FROM peminjaman p
     JOIN peminjam pm ON pm.ID_Peminjam = p.ID_Peminjam
     WHERE p.ID_Peminjaman = ?
     LIMIT 1'
);
$stmt->execute([$id]);
$pinjam = $stmt->fetch();

if (!$pinjam) {
    set_flash('error', 'Data peminjaman tidak ditemukan.');
    redirect('pages/peminjaman/index.php');
}

if ($pinjam['Status_Peminjaman'] === 'dikembalikan') {
    set_flash('error', 'Peminjaman ini sudah dikembalikan.');
    redirect('pages/peminjaman/index.php');
}

$detailStmt = $pdo->prepare(
    'SELECT d.ID_Detail_Pinjam, d.ID_Barang, d.Jumlah_Pinjam, d.Kondisi_Saat_Pinjam,
            b.Nama_Barang, b.Kode_Barang
     FROM detail_peminjaman d
     JOIN barang b ON b.ID_Barang = d.ID_Barang
     WHERE d.ID_Peminjaman = ?'
);
$detailStmt->execute([$id]);
$details = $detailStmt->fetchAll();

$form = [
    'Tanggal_Kembali' => date('Y-m-d'),
    'Keterangan_Pengembalian' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $error = 'Sesi formulir tidak valid. Silakan coba lagi.';
    } else {
        $form['Tanggal_Kembali'] = $_POST['Tanggal_Kembali'] ?? date('Y-m-d');
        $form['Keterangan_Pengembalian'] = trim($_POST['Keterangan_Pengembalian'] ?? '');
        $kondisiKembali = $_POST['kondisi'] ?? [];
        $adminId = (int) ($_SESSION['admin_id'] ?? 0);

        if ($form['Tanggal_Kembali'] === '') {
            $error = 'Tanggal kembali wajib diisi.';
        } elseif ($form['Tanggal_Kembali'] < $pinjam['Tanggal_Pinjam']) {
            $error = 'Tanggal kembali tidak boleh sebelum tanggal pinjam.';
        } elseif (mb_strlen($form['Keterangan_Pengembalian']) > 30) {
            $error = 'Keterangan pengembalian maksimal 30 karakter.';
        } else {
            try {
                $pdo->beginTransaction();

                $lock = $pdo->prepare("SELECT Status_Peminjaman FROM peminjaman WHERE ID_Peminjaman = ? FOR UPDATE");
                $lock->execute([$id]);
                $statusNow = $lock->fetchColumn();
                if ($statusNow !== 'dipinjam') {
                    throw new RuntimeException('Peminjaman ini sudah tidak dalam status dipinjam.');
                }

                $insKembali = $pdo->prepare(
                    'INSERT INTO pengembalian (ID_Peminjaman, ID_Admin, Tanggal_Kembali, Keterangan_Pengembalian)
                     VALUES (?, ?, ?, ?)'
                );
                $insKembali->execute([
                    $id,
                    $adminId,
                    $form['Tanggal_Kembali'],
                    $form['Keterangan_Pengembalian'] !== '' ? $form['Keterangan_Pengembalian'] : null,
                ]);
                $idPengembalian = (int) $pdo->lastInsertId();

                $insDet = $pdo->prepare(
                    'INSERT INTO detail_pengembalian (ID_Detail_Pinjam, ID_Pengembalian, Jumlah_Kembali, Kondisi_Saat_Kembali)
                     VALUES (?, ?, ?, ?)'
                );

                foreach ($details as $item) {
                    $idDetail = (int) $item['ID_Detail_Pinjam'];
                    $kondisi = $kondisiKembali[$idDetail] ?? 'baik';
                    if (!in_array($kondisi, ['baik', 'rusak_ringan', 'rusak_berat'], true)) {
                        $kondisi = 'baik';
                    }

                    $insDet->execute([$idDetail, $idPengembalian, (int) $item['Jumlah_Pinjam'], $kondisi]);
                    perbarui_status_stok($pdo, (int) $item['ID_Barang'], $kondisi);
                }

                $updPinjam = $pdo->prepare("UPDATE peminjaman SET Status_Peminjaman = 'dikembalikan' WHERE ID_Peminjaman = ?");
                $updPinjam->execute([$id]);

                $pdo->commit();
                set_flash('success', 'Pengembalian berhasil dicatat. Status barang sudah diperbarui.');
                redirect('pages/peminjaman/index.php');
            } catch (RuntimeException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = $e->getMessage();
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Gagal mencatat pengembalian.';
            }
        }
    }
}

$pageTitle = 'Pengembalian Aset';
$currentPage = 'peminjaman';
require_once dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Kembalikan Barang</h1>
    <p class="text-sm text-gray-500 mt-1">Catat kondisi barang saat dikembalikan. Status aset akan diperbarui secara otomatis.</p>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 max-w-3xl">
    <div class="rounded-lg bg-gray-50 px-4 py-3 mb-5 text-sm">
        <p><span class="text-gray-500">Peminjam:</span> <strong><?php echo e($pinjam['Nama']); ?></strong> (<?php echo e($pinjam['Status_Peminjam']); ?>)</p>
        <p class="mt-1"><span class="text-gray-500">Tanggal pinjam:</span> <?php echo e(format_tanggal($pinjam['Tanggal_Pinjam'])); ?></p>
    </div>

    <?php if ($error): ?>
        <div class="mb-4 rounded-lg bg-red-50 border border-red-100 text-red-700 text-sm px-4 py-3"><?php echo e($error); ?></div>
    <?php endif; ?>

    <form method="post" class="space-y-5">
        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Kembali <span class="text-red-500">*</span></label>
            <input type="date" name="Tanggal_Kembali" required value="<?php echo e($form['Tanggal_Kembali']); ?>"
                   class="w-full rounded-lg border border-gray-200 py-2.5 px-3 text-sm">
        </div>

        <div>
            <p class="text-sm font-medium text-gray-700 mb-2">Kondisi saat kembali</p>
            <div class="space-y-3">
                <?php foreach ($details as $item): ?>
                    <div class="rounded-lg border border-gray-200 p-3">
                        <p class="text-sm font-medium text-gray-900"><?php echo e($item['Nama_Barang']); ?>
                            <span class="text-gray-400 font-normal"> · <?php echo e($item['Kode_Barang']); ?></span>
                        </p>
                        <p class="text-xs text-gray-500 mb-2">Dipinjam <?php echo (int) $item['Jumlah_Pinjam']; ?> unit · kondisi saat pinjam: <?php echo e(label_kondisi($item['Kondisi_Saat_Pinjam'])); ?></p>
                        <select name="kondisi[<?php echo (int) $item['ID_Detail_Pinjam']; ?>]" class="w-full rounded-lg border border-gray-200 py-2 px-3 text-sm bg-white">
                            <option value="baik">Baik</option>
                            <option value="rusak_ringan">Rusak Ringan</option>
                            <option value="rusak_berat">Rusak Berat</option>
                        </select>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan</label>
            <input type="text" name="Keterangan_Pengembalian" maxlength="30" value="<?php echo e($form['Keterangan_Pengembalian']); ?>"
                   placeholder="Opsional, maks. 30 karakter"
                   class="w-full rounded-lg border border-gray-200 py-2.5 px-3 text-sm">
        </div>

        <div class="flex justify-end gap-3">
            <a href="<?php echo e(url('pages/peminjaman/index.php')); ?>" class="px-4 py-2.5 rounded-lg border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50">Batal</a>
            <button type="submit" class="px-4 py-2.5 rounded-lg bg-brand hover:bg-brand-dark text-white text-sm font-semibold">Simpan Pengembalian</button>
        </div>
    </form>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
