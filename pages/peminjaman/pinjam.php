<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$error = null;
$form = [
    'ID_Peminjam' => '',
    'Nama' => '',
    'Status_Peminjam' => 'Guru',
    'Kontak' => '',
    'Tanggal_Pinjam' => date('Y-m-d'),
    'Tanggal_Rencana_Kembali' => '',
    'Keperluan' => '',
    'barang' => [],
    'jumlah' => [],
];

$peminjamList = $pdo->query('SELECT ID_Peminjam, Nama, Status_Peminjam FROM peminjam ORDER BY Nama')->fetchAll();
$barangTersedia = $pdo->query(
    "SELECT b.ID_Barang, b.Kode_Barang, b.Nama_Barang, b.Kondisi, b.Jumlah, b.Satuan, b.Status_Barang,
            " . sql_jumlah_dipinjam('b') . " AS jumlah_dipinjam
     FROM barang b
     WHERE b.Status_Barang NOT IN ('dihapus', 'perlu_perbaikan')
       AND (b.Jumlah - " . sql_jumlah_dipinjam('b') . ") > 0
     ORDER BY b.Nama_Barang"
)->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $error = 'Sesi formulir tidak valid. Silakan coba lagi.';
    } else {
        $form['ID_Peminjam'] = $_POST['ID_Peminjam'] ?? '';
        $form['Nama'] = trim($_POST['Nama'] ?? '');
        $form['Status_Peminjam'] = trim($_POST['Status_Peminjam'] ?? 'Guru');
        $form['Kontak'] = trim($_POST['Kontak'] ?? '');
        $form['Tanggal_Pinjam'] = $_POST['Tanggal_Pinjam'] ?? date('Y-m-d');
        $form['Tanggal_Rencana_Kembali'] = $_POST['Tanggal_Rencana_Kembali'] ?? '';
        $form['Keperluan'] = trim($_POST['Keperluan'] ?? '');
        $form['barang'] = array_map('intval', $_POST['barang'] ?? []);
        $form['jumlah'] = $_POST['jumlah'] ?? [];

        $adminId = (int) ($_SESSION['admin_id'] ?? 0);
        $idPeminjam = $form['ID_Peminjam'];

        if ($adminId <= 0) {
            $error = 'Sesi admin tidak valid.';
        } elseif (!$form['barang']) {
            $error = 'Pilih minimal satu barang yang akan dipinjam.';
        } elseif ($form['Tanggal_Pinjam'] === '') {
            $error = 'Tanggal pinjam wajib diisi.';
        } elseif ($form['Tanggal_Rencana_Kembali'] !== '' && $form['Tanggal_Rencana_Kembali'] < $form['Tanggal_Pinjam']) {
            $error = 'Tanggal rencana kembali tidak boleh sebelum tanggal pinjam.';
        } else {
            try {
                $pdo->beginTransaction();

                if ($idPeminjam === '__baru__' || $idPeminjam === '') {
                    if ($form['Nama'] === '') {
                        throw new RuntimeException('Nama peminjam wajib diisi.');
                    }
                    if (mb_strlen($form['Nama']) > 25) {
                        throw new RuntimeException('Nama peminjam maksimal 25 karakter.');
                    }
                    $insPeminjam = $pdo->prepare(
                        'INSERT INTO peminjam (Nama, Status_Peminjam, Kontak) VALUES (?, ?, ?)'
                    );
                    $insPeminjam->execute([
                        $form['Nama'],
                        $form['Status_Peminjam'] !== '' ? $form['Status_Peminjam'] : 'Guru',
                        $form['Kontak'] !== '' ? $form['Kontak'] : null,
                    ]);
                    $idPeminjam = (int) $pdo->lastInsertId();
                } else {
                    $idPeminjam = (int) $idPeminjam;
                    $cekPm = $pdo->prepare('SELECT ID_Peminjam FROM peminjam WHERE ID_Peminjam = ?');
                    $cekPm->execute([$idPeminjam]);
                    if (!$cekPm->fetch()) {
                        throw new RuntimeException('Data peminjam tidak ditemukan.');
                    }
                }

                $insPinjam = $pdo->prepare(
                    'INSERT INTO peminjaman (ID_Admin, ID_Peminjam, Tanggal_Pinjam, Tanggal_Rencana_Kembali, Keperluan, Status_Peminjaman)
                     VALUES (?, ?, ?, ?, ?, ?)'
                );
                $insPinjam->execute([
                    $adminId,
                    $idPeminjam,
                    $form['Tanggal_Pinjam'],
                    $form['Tanggal_Rencana_Kembali'] !== '' ? $form['Tanggal_Rencana_Kembali'] : null,
                    $form['Keperluan'] !== '' ? $form['Keperluan'] : null,
                    'dipinjam',
                ]);
                $idPeminjaman = (int) $pdo->lastInsertId();

                $cekBarang = $pdo->prepare(
                    "SELECT ID_Barang, Kondisi, Status_Barang, Jumlah FROM barang WHERE ID_Barang = ? FOR UPDATE"
                );
                $insDetail = $pdo->prepare(
                    'INSERT INTO detail_peminjaman (ID_Peminjaman, ID_Barang, Jumlah_Pinjam, Kondisi_Saat_Pinjam)
                     VALUES (?, ?, ?, ?)'
                );

                $adaItem = false;
                foreach (array_unique($form['barang']) as $idBarang) {
                    $qty = (int) ($form['jumlah'][$idBarang] ?? 0);
                    if ($qty < 1) {
                        continue;
                    }
                    $cekBarang->execute([$idBarang]);
                    $barang = $cekBarang->fetch();
                    if (!$barang) {
                        throw new RuntimeException('Salah satu barang tidak ditemukan.');
                    }
                    if (in_array($barang['Status_Barang'], ['dihapus', 'perlu_perbaikan'], true)) {
                        throw new RuntimeException('Ada barang yang tidak dapat dipinjam.');
                    }

                    $dipinjamStmt = $pdo->prepare(
                        "SELECT COALESCE(SUM(d.Jumlah_Pinjam), 0)
                         FROM detail_peminjaman d
                         JOIN peminjaman p ON p.ID_Peminjaman = d.ID_Peminjaman
                         WHERE d.ID_Barang = ? AND p.Status_Peminjaman = 'dipinjam'"
                    );
                    $dipinjamStmt->execute([$idBarang]);
                    $sisa = (int) $barang['Jumlah'] - (int) $dipinjamStmt->fetchColumn();
                    if ($qty > $sisa) {
                        throw new RuntimeException('Jumlah pinjam melebihi stok tersedia.');
                    }

                    $insDetail->execute([$idPeminjaman, $idBarang, $qty, $barang['Kondisi']]);
                    perbarui_status_stok($pdo, $idBarang);
                    $adaItem = true;
                }
                if (!$adaItem) {
                    throw new RuntimeException('Isi jumlah pinjam minimal 1 untuk barang yang dipilih.');
                }

                $pdo->commit();
                set_flash('success', 'Peminjaman berhasil dicatat. Stok tersedia dikurangi sesuai jumlah yang dipinjam.');
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
                $error = 'Gagal mencatat peminjaman. Periksa data peminjam dan barang.';
            }
        }
    }
}

$pageTitle = 'Catat Peminjaman';
$currentPage = 'peminjaman';
require_once dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Catat Peminjaman Baru</h1>
    <p class="text-sm text-gray-500 mt-1">Pilih barang dan isi jumlah yang dipinjam. Sisa stok tetap tersedia untuk peminjam lain.</p>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 max-w-3xl">
    <?php if ($error): ?>
        <div class="mb-4 rounded-lg bg-red-50 border border-red-100 text-red-700 text-sm px-4 py-3"><?php echo e($error); ?></div>
    <?php endif; ?>

    <?php if (!$barangTersedia): ?>
        <p class="text-sm text-amber-700 bg-amber-50 border border-amber-100 rounded-lg px-4 py-3 mb-4">
            Tidak ada barang berstatus tersedia. Tambah aset baru atau kembalikan barang yang masih dipinjam.
        </p>
    <?php endif; ?>

    <form method="post" class="space-y-5" id="formPinjam">
        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Peminjam <span class="text-red-500">*</span></label>
            <select name="ID_Peminjam" id="ID_Peminjam" class="w-full rounded-lg border border-gray-200 py-2.5 px-3 text-sm bg-white">
                <option value="__baru__">+ Peminjam baru</option>
                <?php foreach ($peminjamList as $pm): ?>
                    <option value="<?php echo (int) $pm['ID_Peminjam']; ?>" <?php echo (string) $form['ID_Peminjam'] === (string) $pm['ID_Peminjam'] ? 'selected' : ''; ?>>
                        <?php echo e($pm['Nama'] . ' (' . $pm['Status_Peminjam'] . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="peminjamBaru" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama <span class="text-red-500">*</span></label>
                <input type="text" name="Nama" maxlength="25" value="<?php echo e($form['Nama']); ?>"
                       placeholder="Contoh: Budi Santoso"
                       class="w-full rounded-lg border border-gray-200 py-2.5 px-3 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Peran</label>
                <select name="Status_Peminjam" class="w-full rounded-lg border border-gray-200 py-2.5 px-3 text-sm bg-white">
                    <?php foreach (['Guru', 'Staf', 'Siswa', 'Lainnya'] as $peran): ?>
                        <option value="<?php echo e($peran); ?>" <?php echo $form['Status_Peminjam'] === $peran ? 'selected' : ''; ?>><?php echo e($peran); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Kontak</label>
                <input type="text" name="Kontak" maxlength="25" value="<?php echo e($form['Kontak']); ?>"
                       class="w-full rounded-lg border border-gray-200 py-2.5 px-3 text-sm">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Pinjam <span class="text-red-500">*</span></label>
                <input type="date" name="Tanggal_Pinjam" required value="<?php echo e($form['Tanggal_Pinjam']); ?>"
                       class="w-full rounded-lg border border-gray-200 py-2.5 px-3 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Rencana Kembali</label>
                <input type="date" name="Tanggal_Rencana_Kembali" value="<?php echo e($form['Tanggal_Rencana_Kembali']); ?>"
                       class="w-full rounded-lg border border-gray-200 py-2.5 px-3 text-sm">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Keperluan</label>
            <input type="text" name="Keperluan" maxlength="50" value="<?php echo e($form['Keperluan']); ?>"
                   placeholder="Contoh: Kegiatan pembelajaran kelas 5"
                   class="w-full rounded-lg border border-gray-200 py-2.5 px-3 text-sm">
        </div>

        <div>
            <p class="text-sm font-medium text-gray-700 mb-2">Barang dipinjam <span class="text-red-500">*</span></p>
            <div class="rounded-lg border border-gray-200 divide-y max-h-64 overflow-y-auto">
                <?php if (!$barangTersedia): ?>
                    <p class="px-4 py-3 text-sm text-gray-500">Tidak ada barang tersedia.</p>
                <?php else: ?>
                    <?php foreach ($barangTersedia as $barang): ?>
                        <?php
                        $idB = (int) $barang['ID_Barang'];
                        $sisa = (int) $barang['Jumlah'] - (int) $barang['jumlah_dipinjam'];
                        $satuan = $barang['Satuan'] ?: 'unit';
                        ?>
                        <label class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50">
                            <input type="checkbox" name="barang[]" value="<?php echo $idB; ?>"
                                   <?php echo in_array($idB, $form['barang'], true) ? 'checked' : ''; ?>
                                   class="rounded border-gray-300 text-brand focus:ring-brand">
                            <span class="flex-1 text-sm">
                                <span class="font-medium"><?php echo e($barang['Nama_Barang']); ?></span>
                                <span class="text-gray-400"> · <?php echo e($barang['Kode_Barang']); ?></span>
                                <span class="block text-xs text-gray-500">Tersedia <?php echo $sisa; ?> dari <?php echo (int) $barang['Jumlah']; ?> <?php echo e($satuan); ?></span>
                            </span>
                            <input type="number" name="jumlah[<?php echo $idB; ?>]" min="1" max="<?php echo $sisa; ?>"
                                   value="<?php echo e((string) ($form['jumlah'][$idB] ?? '1')); ?>"
                                   class="w-20 rounded-lg border border-gray-200 py-1.5 px-2 text-sm">
                            <?php echo badge_kondisi($barang['Kondisi']); ?>
                        </label>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="<?php echo e(url('pages/peminjaman/index.php')); ?>" class="px-4 py-2.5 rounded-lg border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50">Batal</a>
            <button type="submit" class="px-4 py-2.5 rounded-lg bg-brand hover:bg-brand-dark text-white text-sm font-semibold" <?php echo !$barangTersedia ? 'disabled' : ''; ?>>
                Simpan Peminjaman
            </button>
        </div>
    </form>
</div>

<script>
const sel = document.getElementById('ID_Peminjam');
const box = document.getElementById('peminjamBaru');
function togglePeminjam() {
    box.classList.toggle('hidden', sel.value !== '__baru__' && sel.value !== '');
}
sel.addEventListener('change', togglePeminjam);
togglePeminjam();
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
