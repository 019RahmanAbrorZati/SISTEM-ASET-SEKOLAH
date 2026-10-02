<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$idBa = (int) ($_GET['id'] ?? 0);
$idKerusakanGet = (int) ($_GET['kerusakan'] ?? 0);
$isEdit = $idBa > 0;
$error = null;

$barangList = $pdo->query(
    "SELECT b.ID_Barang, b.Kode_Barang, b.Nama_Barang, b.Kondisi, b.Status_Barang,
            k.Nama_Kategori
     FROM barang b
     JOIN kategori_barang k ON k.ID_Kategori = b.ID_Kategori
     WHERE b.Status_Barang NOT IN ('dihapus', 'dipinjam')
     ORDER BY b.Nama_Barang"
)->fetchAll();

$data = [
    'ID_Barang' => '',
    'Nomor_BA' => generate_nomor_ba($pdo),
    'Tanggal_BA' => date('Y-m-d'),
    'Alasan_Penghapusan' => '',
    'Kondisi_Barang' => 'rusak_berat',
    'ID_Kerusakan' => $idKerusakanGet,
];

if ($idKerusakanGet > 0 && !$isEdit) {
    $kStmt = $pdo->prepare(
        'SELECT k.*, b.ID_Barang, b.Kondisi FROM kerusakan k
         JOIN barang b ON b.ID_Barang = k.ID_Barang
         WHERE k.ID_Kerusakan = ? LIMIT 1'
    );
    $kStmt->execute([$idKerusakanGet]);
    $kRow = $kStmt->fetch();
    if ($kRow) {
        $data['ID_Barang'] = (int) $kRow['ID_Barang'];
        $data['Kondisi_Barang'] = $kRow['Kondisi'] ?: 'rusak_berat';
        $cekBa = $pdo->prepare('SELECT ID_BA FROM berita_acara_penghapusan WHERE ID_Kerusakan = ? LIMIT 1');
        $cekBa->execute([$idKerusakanGet]);
        if ($cekBa->fetch()) {
            set_flash('error', 'Kerusakan ini sudah memiliki berita acara.');
            redirect('pages/kerusakan/index.php');
        }
    }
}

if ($isEdit) {
    $stmt = $pdo->prepare(
        'SELECT ba.*, k.ID_Barang FROM berita_acara_penghapusan ba
         JOIN kerusakan k ON k.ID_Kerusakan = ba.ID_Kerusakan
         WHERE ba.ID_BA = ? LIMIT 1'
    );
    $stmt->execute([$idBa]);
    $row = $stmt->fetch();
    if (!$row) {
        set_flash('error', 'Berita acara tidak ditemukan.');
        redirect('pages/kerusakan/index.php');
    }
    if ($row['Status_BA'] === 'final') {
        redirect('pages/kerusakan/ba_lihat.php?id=' . $idBa);
    }
    $data = array_merge($data, $row);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $error = 'Sesi formulir tidak valid.';
    } else {
        $aksi = $_POST['aksi'] ?? 'draft';
        $data['ID_Barang'] = (int) ($_POST['ID_Barang'] ?? 0);
        $data['Nomor_BA'] = trim($_POST['Nomor_BA'] ?? '');
        $data['Tanggal_BA'] = $_POST['Tanggal_BA'] ?? date('Y-m-d');
        $data['Alasan_Penghapusan'] = trim($_POST['Alasan_Penghapusan'] ?? '');
        $data['Kondisi_Barang'] = trim($_POST['Kondisi_Barang'] ?? 'rusak_berat');
        $data['ID_Kerusakan'] = (int) ($_POST['ID_Kerusakan'] ?? 0);
        $adminId = (int) ($_SESSION['admin_id'] ?? 0);

        if ($data['ID_Barang'] <= 0 || $data['Nomor_BA'] === '' || $data['Alasan_Penghapusan'] === '') {
            $error = 'Barang, nomor BA, dan alasan penghapusan wajib diisi.';
        } elseif (mb_strlen($data['Nomor_BA']) > 20) {
            $error = 'Nomor BA maksimal 20 karakter.';
        } elseif (mb_strlen($data['Alasan_Penghapusan']) > 100) {
            $error = 'Alasan penghapusan maksimal 100 karakter.';
        } else {
            try {
                $pdo->beginTransaction();

                $cekNomor = $pdo->prepare('SELECT ID_BA FROM berita_acara_penghapusan WHERE Nomor_BA = ? AND ID_BA <> ?');
                $cekNomor->execute([$data['Nomor_BA'], $idBa]);
                if ($cekNomor->fetch()) {
                    throw new RuntimeException('Nomor berita acara sudah digunakan.');
                }

                $barangStmt = $pdo->prepare('SELECT ID_Barang, Status_Barang FROM barang WHERE ID_Barang = ? FOR UPDATE');
                $barangStmt->execute([$data['ID_Barang']]);
                $barang = $barangStmt->fetch();
                if (!$barang || in_array($barang['Status_Barang'], ['dihapus', 'dipinjam'], true)) {
                    throw new RuntimeException('Barang tidak bisa dihapus: sedang dipinjam, sudah nonaktif, atau tidak ditemukan.');
                }

                $idKerusakan = (int) ($data['ID_Kerusakan'] ?? 0);
                if ($idKerusakan <= 0) {
                    $cari = $pdo->prepare(
                        'SELECT k.ID_Kerusakan FROM kerusakan k
                         LEFT JOIN berita_acara_penghapusan ba ON ba.ID_Kerusakan = k.ID_Kerusakan
                         WHERE k.ID_Barang = ? AND ba.ID_BA IS NULL
                         ORDER BY k.ID_Kerusakan DESC LIMIT 1'
                    );
                    $cari->execute([$data['ID_Barang']]);
                    $idKerusakan = (int) $cari->fetchColumn();
                }

                if ($idKerusakan <= 0) {
                    $insK = $pdo->prepare(
                        'INSERT INTO kerusakan (ID_Barang, ID_Admin, Tanggal_Lapor, Jenis_Kerusakan, Tingkat_Kerusakan, Status_Kerusakan)
                         VALUES (?, ?, ?, ?, ?, ?)'
                    );
                    $insK->execute([$data['ID_Barang'], $adminId, $data['Tanggal_BA'], 'Penghapusan', 'total', 'menunggu_ba']);
                    $idKerusakan = (int) $pdo->lastInsertId();
                }

                $statusBa = $aksi === 'final' ? 'final' : 'draft';

                if ($isEdit) {
                    $updBa = $pdo->prepare(
                        'UPDATE berita_acara_penghapusan
                         SET Nomor_BA = ?, Tanggal_BA = ?, Alasan_Penghapusan = ?, Kondisi_Barang = ?, Status_BA = ?
                         WHERE ID_BA = ? AND Status_BA = ?'
                    );
                    $updBa->execute([
                        $data['Nomor_BA'], $data['Tanggal_BA'], $data['Alasan_Penghapusan'],
                        $data['Kondisi_Barang'], $statusBa, $idBa, 'draft',
                    ]);
                    $idSimpan = $idBa;
                } else {
                    $insBa = $pdo->prepare(
                        'INSERT INTO berita_acara_penghapusan (ID_Kerusakan, Nomor_BA, Tanggal_BA, Alasan_Penghapusan, Kondisi_Barang, Status_BA)
                         VALUES (?, ?, ?, ?, ?, ?)'
                    );
                    $insBa->execute([
                        $idKerusakan, $data['Nomor_BA'], $data['Tanggal_BA'],
                        $data['Alasan_Penghapusan'], $data['Kondisi_Barang'], $statusBa,
                    ]);
                    $idSimpan = (int) $pdo->lastInsertId();
                }

                if ($statusBa === 'final') {
                    // Soft delete: baris barang tetap ada, status dihapus/nonaktif.
                    $pdo->prepare("UPDATE barang SET Status_Barang = 'dihapus' WHERE ID_Barang = ?")->execute([$data['ID_Barang']]);
                    $pdo->prepare("UPDATE kerusakan SET Status_Kerusakan = 'selesai' WHERE ID_Kerusakan = ?")->execute([$idKerusakan]);
                } else {
                    $pdo->prepare("UPDATE kerusakan SET Status_Kerusakan = 'menunggu_ba' WHERE ID_Kerusakan = ?")->execute([$idKerusakan]);
                    if ($barang['Status_Barang'] === 'tersedia') {
                        $pdo->prepare("UPDATE barang SET Status_Barang = 'perlu_perbaikan' WHERE ID_Barang = ?")->execute([$data['ID_Barang']]);
                    }
                }

                $pdo->commit();

                if ($statusBa === 'final') {
                    set_flash('success', 'Berita acara difinalkan. Aset dinonaktifkan (tidak dihapus permanen dari database).');
                    redirect('pages/kerusakan/ba_lihat.php?id=' . $idSimpan);
                }
                set_flash('success', 'Berita acara disimpan sebagai draft. Aset belum dinonaktifkan.');
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
                $error = 'Gagal menyimpan berita acara.';
            }
        }
    }
}

$pageTitle = $isEdit ? 'Ubah Berita Acara' : 'Buat Berita Acara';
$currentPage = 'kerusakan';
require_once dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="flex items-start justify-between gap-4 mb-6">
    <h1 class="text-2xl font-bold text-gray-900"><?php echo $isEdit ? 'Ubah Berita Acara' : 'Buat Berita Acara Baru'; ?></h1>
</div>

<?php if ($error): ?>
    <div class="mb-4 rounded-lg bg-red-50 border border-red-100 text-red-700 text-sm px-4 py-3 max-w-3xl mx-auto"><?php echo e($error); ?></div>
<?php endif; ?>

<form method="post" class="max-w-3xl mx-auto">
    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
    <input type="hidden" name="ID_Kerusakan" value="<?php echo (int) ($data['ID_Kerusakan'] ?? 0); ?>">
    <div class="flex justify-end gap-2 mb-4">
        <a href="<?php echo e(url('pages/kerusakan/index.php')); ?>" class="px-4 py-2 rounded-lg border border-gray-200 text-sm font-medium text-gray-600 bg-white">Batal</a>
        <button type="submit" name="aksi" value="draft" class="px-4 py-2 rounded-lg border border-brand text-brand text-sm font-semibold bg-white">Simpan Draft</button>
        <button type="submit" name="aksi" value="final" class="px-4 py-2 rounded-lg bg-brand hover:bg-brand-dark text-white text-sm font-semibold"
                onclick="return confirm('Finalkan berita acara? Status aset akan menjadi nonaktif (bukan hapus permanen).');">
            Simpan &amp; Cetak
        </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 px-10 py-12 text-center">
        <p class="text-xs font-semibold tracking-[0.2em] text-gray-700">PEMERINTAH KABUPATEN/KOTA</p>
        <p class="text-sm font-bold text-gray-900 mt-1">DINAS PENDIDIKAN</p>
        <p class="text-base font-bold text-gray-900">SDN 1</p>
        <p class="text-[11px] text-gray-500 mt-1">Jl. Pendidikan No. 1</p>
        <div class="border-t-2 border-gray-800 mt-4 pt-4">
            <p class="font-bold underline">BERITA ACARA PENGHAPUSAN BARANG MILIK SEKOLAH</p>
            <div class="mt-3 max-w-xs mx-auto text-left text-sm">
                <label class="text-xs text-gray-500">Nomor</label>
                <input type="text" name="Nomor_BA" maxlength="20" required value="<?php echo e($data['Nomor_BA']); ?>"
                       class="w-full border-b border-gray-400 text-center font-medium py-1 focus:outline-none">
            </div>
        </div>

        <p class="text-sm text-justify text-gray-700 mt-8 leading-relaxed">
            Pada hari ini, bertempat di SDN 1, kami yang bertanda tangan di bawah ini secara bersama-sama
            telah melakukan pemeriksaan dan penilaian terhadap Barang Milik Sekolah yang diusulkan untuk dihapus dari
            inventaris dengan rincian sebagai berikut:
        </p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-left mt-8">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Nama Barang</label>
                <select name="ID_Barang" required class="w-full rounded-lg border border-gray-200 py-2 px-3 text-sm bg-white">
                    <option value="">Pilih barang</option>
                    <?php foreach ($barangList as $b): ?>
                        <option value="<?php echo (int) $b['ID_Barang']; ?>" <?php echo (int) $data['ID_Barang'] === (int) $b['ID_Barang'] ? 'selected' : ''; ?>>
                            <?php echo e($b['Nama_Barang'] . ' — ' . $b['Kode_Barang']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Kode Aset / Inventaris</label>
                <input type="text" disabled placeholder="Terisi otomatis dari barang" class="w-full rounded-lg border border-gray-100 bg-gray-50 py-2 px-3 text-sm text-gray-500">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Kondisi terakhir</label>
                <select name="Kondisi_Barang" class="w-full rounded-lg border border-gray-200 py-2 px-3 text-sm bg-white">
                    <option value="baik" <?php echo $data['Kondisi_Barang'] === 'baik' ? 'selected' : ''; ?>>Baik</option>
                    <option value="rusak_ringan" <?php echo $data['Kondisi_Barang'] === 'rusak_ringan' ? 'selected' : ''; ?>>Rusak Ringan</option>
                    <option value="rusak_berat" <?php echo $data['Kondisi_Barang'] === 'rusak_berat' ? 'selected' : ''; ?>>Rusak Berat</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Tanggal Penghapusan</label>
                <input type="date" name="Tanggal_BA" required value="<?php echo e($data['Tanggal_BA']); ?>"
                       class="w-full rounded-lg border border-gray-200 py-2 px-3 text-sm">
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs text-gray-500 mb-1">Alasan Penghapusan</label>
                <textarea name="Alasan_Penghapusan" rows="3" maxlength="100" required
                          placeholder="Jelaskan alasan (misalnya rusak total, hilang, atau tidak dapat diperbaiki lagi)."
                          class="w-full rounded-lg border border-gray-200 py-2 px-3 text-sm"><?php echo e($data['Alasan_Penghapusan']); ?></textarea>
            </div>
        </div>

        <p class="text-sm text-justify text-gray-700 mt-8 leading-relaxed">
            Demikian Berita Acara Penghapusan Barang Milik Sekolah ini dibuat dengan sebenarnya untuk dipakai
            sebagaimana mestinya dan sebagai dasar penyesuaian data pada buku inventaris sekolah.
        </p>

        <div class="grid grid-cols-2 gap-8 mt-12 text-sm">
            <div>
                <p>Mengetahui/Menyetujui,</p>
                <p class="font-semibold">Kepala Sekolah</p>
                <div class="h-16"></div>
                <p>________________</p>
            </div>
            <div>
                <p>Tanggal sesuai berita acara</p>
                <p class="font-semibold">Petugas Inventaris</p>
                <div class="h-16"></div>
                <p><?php echo e(current_admin()['nama']); ?></p>
            </div>
        </div>
    </div>
</form>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
