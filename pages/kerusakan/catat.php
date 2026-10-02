<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$error = null;
$form = [
    'ID_Barang' => '',
    'Tanggal_Lapor' => date('Y-m-d'),
    'Jenis_Kerusakan' => 'Fisik',
    'Tingkat_Kerusakan' => 'berat',
];

$barangList = $pdo->query(
    "SELECT ID_Barang, Kode_Barang, Nama_Barang, Status_Barang, Kondisi
     FROM barang
     WHERE Status_Barang <> 'dihapus'
     ORDER BY Nama_Barang"
)->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $error = 'Sesi formulir tidak valid.';
    } else {
        $form['ID_Barang'] = (int) ($_POST['ID_Barang'] ?? 0);
        $form['Tanggal_Lapor'] = $_POST['Tanggal_Lapor'] ?? date('Y-m-d');
        $form['Jenis_Kerusakan'] = trim($_POST['Jenis_Kerusakan'] ?? '');
        $form['Tingkat_Kerusakan'] = trim($_POST['Tingkat_Kerusakan'] ?? 'berat');
        $adminId = (int) ($_SESSION['admin_id'] ?? 0);

        if ($form['ID_Barang'] <= 0) {
            $error = 'Pilih barang yang rusak.';
        } elseif (mb_strlen($form['Jenis_Kerusakan']) > 20) {
            $error = 'Jenis kerusakan maksimal 20 karakter.';
        } else {
            try {
                $pdo->beginTransaction();
                $cek = $pdo->prepare('SELECT ID_Barang, Status_Barang FROM barang WHERE ID_Barang = ? FOR UPDATE');
                $cek->execute([$form['ID_Barang']]);
                $barang = $cek->fetch();
                if (!$barang || $barang['Status_Barang'] === 'dihapus') {
                    throw new RuntimeException('Barang tidak valid atau sudah nonaktif.');
                }
                if ($barang['Status_Barang'] === 'dipinjam') {
                    throw new RuntimeException('Barang sedang dipinjam. Kembalikan dulu sebelum mencatat kerusakan untuk penghapusan.');
                }

                $ins = $pdo->prepare(
                    'INSERT INTO kerusakan (ID_Barang, ID_Admin, Tanggal_Lapor, Jenis_Kerusakan, Tingkat_Kerusakan, Status_Kerusakan)
                     VALUES (?, ?, ?, ?, ?, ?)'
                );
                $ins->execute([
                    $form['ID_Barang'],
                    $adminId,
                    $form['Tanggal_Lapor'],
                    $form['Jenis_Kerusakan'] !== '' ? $form['Jenis_Kerusakan'] : null,
                    $form['Tingkat_Kerusakan'],
                    'dicatat',
                ]);

                // Belum dihapus: hanya tandai perlu perbaikan.
                if ($barang['Status_Barang'] === 'tersedia') {
                    $upd = $pdo->prepare("UPDATE barang SET Status_Barang = 'perlu_perbaikan', Kondisi = 'rusak_berat' WHERE ID_Barang = ?");
                    $upd->execute([$form['ID_Barang']]);
                }

                $pdo->commit();
                set_flash('success', 'Kerusakan dicatat. Barang belum dihapus. Lanjutkan dengan membuat Berita Acara jika akan dihapus dari inventaris aktif.');
                redirect('pages/kerusakan/index.php');
            } catch (RuntimeException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = $e->getMessage();
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Gagal mencatat kerusakan.';
            }
        }
    }
}

$pageTitle = 'Catat Kerusakan';
$currentPage = 'kerusakan';
require_once dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Catat Kerusakan Barang</h1>
    <p class="text-sm text-gray-500 mt-1">Langkah 1: catat kerusakan. Barang tidak dihapus dari database pada tahap ini.</p>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 max-w-2xl">
    <?php if ($error): ?>
        <div class="mb-4 rounded-lg bg-red-50 border border-red-100 text-red-700 text-sm px-4 py-3"><?php echo e($error); ?></div>
    <?php endif; ?>

    <form method="post" class="space-y-4">
        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Barang <span class="text-red-500">*</span></label>
            <select name="ID_Barang" required class="w-full rounded-lg border border-gray-200 py-2.5 px-3 text-sm bg-white">
                <option value="">Pilih barang</option>
                <?php foreach ($barangList as $b): ?>
                    <option value="<?php echo (int) $b['ID_Barang']; ?>" <?php echo (int) $form['ID_Barang'] === (int) $b['ID_Barang'] ? 'selected' : ''; ?>>
                        <?php echo e($b['Nama_Barang'] . ' (' . $b['Kode_Barang'] . ') — ' . $b['Status_Barang']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal lapor</label>
            <input type="date" name="Tanggal_Lapor" value="<?php echo e($form['Tanggal_Lapor']); ?>" class="w-full rounded-lg border border-gray-200 py-2.5 px-3 text-sm">
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Jenis kerusakan</label>
                <input type="text" name="Jenis_Kerusakan" maxlength="20" value="<?php echo e($form['Jenis_Kerusakan']); ?>"
                       class="w-full rounded-lg border border-gray-200 py-2.5 px-3 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tingkat</label>
                <select name="Tingkat_Kerusakan" class="w-full rounded-lg border border-gray-200 py-2.5 px-3 text-sm bg-white">
                    <option value="ringan" <?php echo $form['Tingkat_Kerusakan'] === 'ringan' ? 'selected' : ''; ?>>Ringan</option>
                    <option value="berat" <?php echo $form['Tingkat_Kerusakan'] === 'berat' ? 'selected' : ''; ?>>Berat</option>
                    <option value="total" <?php echo $form['Tingkat_Kerusakan'] === 'total' ? 'selected' : ''; ?>>Total</option>
                </select>
            </div>
        </div>
        <div class="flex justify-end gap-3 pt-2">
            <a href="<?php echo e(url('pages/kerusakan/index.php')); ?>" class="px-4 py-2.5 rounded-lg border border-gray-200 text-sm font-medium text-gray-600">Batal</a>
            <button class="px-4 py-2.5 rounded-lg bg-brand hover:bg-brand-dark text-white text-sm font-semibold">Simpan Catatan</button>
        </div>
    </form>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
