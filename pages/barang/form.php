<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEdit = $id > 0;
$error = null;
$fotoLama = null;

$data = [
    'Nama_Barang'      => '',
    'Kode_Barang'      => '',
    'ID_Kategori'      => '',
    'ID_Ruang'         => '',
    'Kondisi'          => 'baik',
    'Jumlah'           => 1,
    'Satuan'           => 'Unit',
    'Tahun_Pengadaan'  => date('Y'),
    'tanggal'          => date('Y-m-d'),
    'Sumber_Dana'      => '',
    'Keterangan'       => '',
    'Status_Barang'    => 'tersedia',
];

$kategoriList = $pdo->query('SELECT ID_Kategori, Nama_Kategori FROM kategori_barang ORDER BY Nama_Kategori')->fetchAll();
$ruangList = $pdo->query('SELECT ID_Ruang, Nama_Ruang FROM ruang ORDER BY Nama_Ruang')->fetchAll();

if ($isEdit) {
    $stmt = $pdo->prepare(
        'SELECT * FROM barang WHERE ID_Barang = ? LIMIT 1'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        set_flash('error', 'Aset tidak ditemukan.');
        redirect('pages/barang/index.php');
    }
    // Barang yang sudah dinonaktifkan lewat Berita Acara tidak diubah dari sini.
    if ($row['Status_Barang'] === 'dihapus') {
        set_flash('error', 'Aset ini sudah dinonaktifkan melalui Berita Acara Penghapusan.');
        redirect('pages/barang/index.php');
    }
    $data = $row;
    $data['tanggal'] = !empty($row['Tahun_Pengadaan']) ? $row['Tahun_Pengadaan'] . '-01-01' : date('Y-m-d');
    $fotoLama = $row['Foto'] ?? null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $error = 'Sesi formulir tidak valid. Silakan coba lagi.';
    } else {
        $nama = trim($_POST['Nama_Barang'] ?? '');
        $kode = trim($_POST['Kode_Barang'] ?? '');
        $idKategori = $_POST['ID_Kategori'] ?? '';
        $idRuang = $_POST['ID_Ruang'] ?? '';
        $kondisi = $_POST['Kondisi'] ?? 'baik';
        $jumlah = (int) ($_POST['Jumlah'] ?? 1);
        $satuan = trim($_POST['Satuan'] ?? '');
        $tanggal = trim($_POST['tanggal'] ?? '');
        $sumber = trim($_POST['Sumber_Dana'] ?? '');
        $keterangan = trim($_POST['Keterangan'] ?? '');

        $data['Nama_Barang'] = $nama;
        $data['Kode_Barang'] = $kode;
        $data['ID_Kategori'] = $idKategori;
        $data['ID_Ruang'] = $idRuang;
        $data['Kondisi'] = $kondisi;
        $data['Jumlah'] = $jumlah;
        $data['Satuan'] = $satuan;
        $data['tanggal'] = $tanggal;
        $data['Sumber_Dana'] = $sumber;
        $data['Keterangan'] = $keterangan;

        $kondisiValid = in_array($kondisi, ['baik', 'rusak_ringan', 'rusak_berat'], true);

        if ($nama === '' || $kode === '' || $jumlah < 1 || !$kondisiValid) {
            $error = 'Nama, kode aset, jumlah, dan kondisi wajib diisi dengan benar.';
        } elseif (mb_strlen($nama) > 150 || mb_strlen($kode) > 50) {
            $error = 'Nama atau kode aset melebihi batas karakter.';
        } else {
            try {
                $pdo->beginTransaction();

                // Operator boleh menambah kategori baru langsung dari form aset.
                if ($idKategori === '__baru__') {
                    $namaKat = trim($_POST['Nama_Kategori_Baru'] ?? '');
                    if ($namaKat === '') {
                        throw new RuntimeException('Isi nama kategori baru, atau pilih kategori yang sudah ada.');
                    }
                    $cekKat = $pdo->prepare('SELECT ID_Kategori FROM kategori_barang WHERE Nama_Kategori = ? LIMIT 1');
                    $cekKat->execute([$namaKat]);
                    $adaKat = $cekKat->fetch();
                    if ($adaKat) {
                        $idKategori = (int) $adaKat['ID_Kategori'];
                    } else {
                        $insKat = $pdo->prepare('INSERT INTO kategori_barang (Nama_Kategori) VALUES (?)');
                        $insKat->execute([$namaKat]);
                        $idKategori = (int) $pdo->lastInsertId();
                    }
                }

                if ($idRuang === '__baru__') {
                    $namaRuang = trim($_POST['Nama_Ruang_Baru'] ?? '');
                    if ($namaRuang === '') {
                        throw new RuntimeException('Isi nama ruang baru, atau pilih ruang yang sudah ada.');
                    }
                    $cekRuang = $pdo->prepare('SELECT ID_Ruang FROM ruang WHERE Nama_Ruang = ? LIMIT 1');
                    $cekRuang->execute([$namaRuang]);
                    $adaRuang = $cekRuang->fetch();
                    if ($adaRuang) {
                        $idRuang = (int) $adaRuang['ID_Ruang'];
                    } else {
                        $insRuang = $pdo->prepare('INSERT INTO ruang (Nama_Ruang, Lokasi) VALUES (?, ?)');
                        $insRuang->execute([$namaRuang, null]);
                        $idRuang = (int) $pdo->lastInsertId();
                    }
                }

                $idKategori = (int) $idKategori;
                $idRuang = (int) $idRuang;
                if ($idKategori <= 0 || $idRuang <= 0) {
                    throw new RuntimeException('Kategori dan ruang/lokasi wajib dipilih.');
                }

                $cekKode = $pdo->prepare('SELECT ID_Barang FROM barang WHERE Kode_Barang = ? AND ID_Barang <> ? LIMIT 1');
                $cekKode->execute([$kode, $id]);
                if ($cekKode->fetch()) {
                    throw new RuntimeException('Kode aset sudah digunakan.');
                }

                $tahun = $tanggal !== '' ? (int) date('Y', strtotime($tanggal)) : (int) date('Y');
                $fotoNama = $fotoLama;

                if ($isEdit) {
                    $dipinjamCek = $pdo->prepare(
                        "SELECT COALESCE(SUM(d.Jumlah_Pinjam), 0)
                         FROM detail_peminjaman d
                         JOIN peminjaman p ON p.ID_Peminjaman = d.ID_Peminjaman
                         WHERE d.ID_Barang = ? AND p.Status_Peminjaman = 'dipinjam'"
                    );
                    $dipinjamCek->execute([$id]);
                    if ($jumlah < (int) $dipinjamCek->fetchColumn()) {
                        throw new RuntimeException('Jumlah tidak boleh lebih kecil dari stok yang sedang dipinjam.');
                    }
                }

                $adaUnggahan = isset($_FILES['Foto']) && is_array($_FILES['Foto'])
                    && ($_FILES['Foto']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

                if ($adaUnggahan) {
                    $err = (int) $_FILES['Foto']['error'];
                    if ($err !== UPLOAD_ERR_OK) {
                        throw new RuntimeException('Unggah foto gagal. Coba file JPG/PNG yang lebih kecil dari 2 MB.');
                    }
                    $tmp = $_FILES['Foto']['tmp_name'];
                    $size = (int) $_FILES['Foto']['size'];
                    if ($size <= 0 || $size > 2 * 1024 * 1024) {
                        throw new RuntimeException('Ukuran foto maksimal 2 MB.');
                    }

                    $mime = '';
                    if (class_exists('finfo')) {
                        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp) ?: '';
                    }
                    $extMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                    $ext = $extMap[$mime] ?? strtolower(pathinfo($_FILES['Foto']['name'], PATHINFO_EXTENSION));
                    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                        throw new RuntimeException('Format foto harus JPG, PNG, atau WEBP.');
                    }
                    if ($ext === 'jpeg') {
                        $ext = 'jpg';
                    }

                    $folder = APP_ROOT . '/assets/img/barang';
                    if (!is_dir($folder) && !mkdir($folder, 0775, true) && !is_dir($folder)) {
                        throw new RuntimeException('Folder unggahan foto tidak dapat dibuat.');
                    }
                    $fotoNama = 'aset_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    if (!move_uploaded_file($tmp, $folder . '/' . $fotoNama)) {
                        throw new RuntimeException('Gagal menyimpan foto ke server.');
                    }
                }

                $statusAwal = 'tersedia';
                if ($kondisi === 'rusak_berat') {
                    $statusAwal = 'perlu_perbaikan';
                }

                if ($isEdit) {
                    $statusKeep = $data['Status_Barang'];
                    if ($statusKeep === 'dipinjam') {
                        $statusKeep = 'dipinjam';
                    } elseif ($kondisi === 'rusak_berat') {
                        $statusKeep = 'perlu_perbaikan';
                    } elseif ($statusKeep === 'perlu_perbaikan' && $kondisi === 'baik') {
                        $statusKeep = 'tersedia';
                    }

                    $upd = $pdo->prepare(
                        'UPDATE barang SET ID_Ruang = ?, ID_Kategori = ?, Kode_Barang = ?, Nama_Barang = ?,
                         Jumlah = ?, Satuan = ?, Kondisi = ?, Tahun_Pengadaan = ?, Sumber_Dana = ?,
                         Status_Barang = ?, Foto = ?, Keterangan = ?
                         WHERE ID_Barang = ? AND Status_Barang <> ?'
                    );
                    $upd->execute([
                        $idRuang, $idKategori, $kode, $nama, $jumlah,
                        $satuan !== '' ? $satuan : null,
                        $kondisi, $tahun, $sumber !== '' ? $sumber : null,
                        $statusKeep, $fotoNama, $keterangan !== '' ? $keterangan : null,
                        $id, 'dihapus',
                    ]);
                } else {
                    $ins = $pdo->prepare(
                        'INSERT INTO barang (ID_Ruang, ID_Kategori, Kode_Barang, Nama_Barang, Jumlah, Satuan, Kondisi, Tahun_Pengadaan, Sumber_Dana, Status_Barang, Foto, Keterangan)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                    );
                    $ins->execute([
                        $idRuang, $idKategori, $kode, $nama, $jumlah,
                        $satuan !== '' ? $satuan : null,
                        $kondisi, $tahun, $sumber !== '' ? $sumber : null,
                        $statusAwal, $fotoNama, $keterangan !== '' ? $keterangan : null,
                    ]);
                }

                $pdo->commit();
                set_flash('success', $isEdit ? 'Aset berhasil diubah.' : 'Aset berhasil ditambahkan.');
                redirect('pages/barang/index.php');
            } catch (RuntimeException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = $e->getMessage();
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Gagal menyimpan data aset. Periksa kategori, ruang, dan kode aset.';
            }
        }
    }
}

$pageTitle = $isEdit ? 'Ubah Aset' : 'Tambah Aset';
$currentPage = 'barang';
$searchPlaceholder = 'Cari aset...';
require_once dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="flex items-start justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900"><?php echo $isEdit ? 'Ubah Data Aset' : 'Tambah Aset Baru'; ?></h1>
        <p class="text-sm text-gray-500 mt-1">Masukkan detail informasi aset sekolah yang baru diperoleh.</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h2 class="text-sm font-semibold text-gray-800 mb-5">Form Aset</h2>

    <?php if ($error): ?>
        <div class="mb-4 rounded-lg bg-red-50 border border-red-100 text-red-700 text-sm px-4 py-3"><?php echo e($error); ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="space-y-5" id="formAset">
        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Barang <span class="text-red-500">*</span></label>
                <input type="text" name="Nama_Barang" maxlength="150" required
                       value="<?php echo e($data['Nama_Barang']); ?>"
                       placeholder="Contoh: Laptop Asus VivoBook"
                       class="w-full rounded-lg border border-gray-200 py-2.5 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand/30 focus:border-brand">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Kode Aset <span class="text-red-500">*</span></label>
                <input type="text" name="Kode_Barang" maxlength="50" required
                       value="<?php echo e($data['Kode_Barang']); ?>"
                       placeholder="Contoh: INV/IT/2024/001"
                       class="w-full rounded-lg border border-gray-200 py-2.5 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand/30 focus:border-brand">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Kategori <span class="text-red-500">*</span></label>
                <select name="ID_Kategori" id="ID_Kategori" class="w-full rounded-lg border border-gray-200 py-2.5 px-3 text-sm bg-white">
                    <option value="">Pilih Kategori</option>
                    <?php foreach ($kategoriList as $kat): ?>
                        <option value="<?php echo (int) $kat['ID_Kategori']; ?>" <?php echo (string) $data['ID_Kategori'] === (string) $kat['ID_Kategori'] ? 'selected' : ''; ?>>
                            <?php echo e($kat['Nama_Kategori']); ?>
                        </option>
                    <?php endforeach; ?>
                    <option value="__baru__" <?php echo ($data['ID_Kategori'] ?? '') === '__baru__' ? 'selected' : ''; ?>>+ Tambah kategori baru...</option>
                </select>
                <input type="text" name="Nama_Kategori_Baru" id="Nama_Kategori_Baru" maxlength="30"
                       placeholder="Nama kategori baru"
                       class="mt-2 w-full rounded-lg border border-gray-200 py-2 px-3 text-sm hidden">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Perolehan</label>
                <input type="date" name="tanggal" value="<?php echo e($data['tanggal'] ?? ''); ?>"
                       class="w-full rounded-lg border border-gray-200 py-2.5 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand/30 focus:border-brand">
                <p class="text-[11px] text-gray-400 mt-1">Tahun pengadaan tersimpan sesuai tanggal ini.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Kondisi <span class="text-red-500">*</span></label>
                <select name="Kondisi" class="w-full rounded-lg border border-gray-200 py-2.5 px-3 text-sm bg-white">
                    <option value="baik" <?php echo ($data['Kondisi'] ?? '') === 'baik' ? 'selected' : ''; ?>>Baik</option>
                    <option value="rusak_ringan" <?php echo ($data['Kondisi'] ?? '') === 'rusak_ringan' ? 'selected' : ''; ?>>Rusak Ringan</option>
                    <option value="rusak_berat" <?php echo ($data['Kondisi'] ?? '') === 'rusak_berat' ? 'selected' : ''; ?>>Rusak Berat</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah <span class="text-red-500">*</span></label>
                <input type="number" name="Jumlah" min="1" required value="<?php echo e((string) $data['Jumlah']); ?>"
                       class="w-full rounded-lg border border-gray-200 py-2.5 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand/30 focus:border-brand">
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi (Ruangan) <span class="text-red-500">*</span></label>
                <select name="ID_Ruang" id="ID_Ruang" class="w-full rounded-lg border border-gray-200 py-2.5 px-3 text-sm bg-white">
                    <option value="">Pilih ruangan</option>
                    <?php foreach ($ruangList as $ruang): ?>
                        <option value="<?php echo (int) $ruang['ID_Ruang']; ?>" <?php echo (string) $data['ID_Ruang'] === (string) $ruang['ID_Ruang'] ? 'selected' : ''; ?>>
                            <?php echo e($ruang['Nama_Ruang']); ?>
                        </option>
                    <?php endforeach; ?>
                    <option value="__baru__" <?php echo ($data['ID_Ruang'] ?? '') === '__baru__' ? 'selected' : ''; ?>>+ Tambah ruang baru...</option>
                </select>
                <input type="text" name="Nama_Ruang_Baru" id="Nama_Ruang_Baru" maxlength="30"
                       placeholder="Nama ruang baru, contoh: Ruang Guru"
                       class="mt-2 w-full rounded-lg border border-gray-200 py-2 px-3 text-sm hidden">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Satuan</label>
                <input type="text" name="Satuan" maxlength="25" value="<?php echo e($data['Satuan'] ?? ''); ?>"
                       placeholder="Unit, Buah, Set"
                       class="w-full rounded-lg border border-gray-200 py-2.5 px-3 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sumber Dana</label>
                <input type="text" name="Sumber_Dana" maxlength="30" value="<?php echo e($data['Sumber_Dana'] ?? ''); ?>"
                       placeholder="BOS, Hibah, dll"
                       class="w-full rounded-lg border border-gray-200 py-2.5 px-3 text-sm">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan Tambahan</label>
            <textarea name="Keterangan" rows="3" placeholder="Tambahkan catatan khusus terkait aset ini (opsional)"
                      class="w-full rounded-lg border border-gray-200 py-2.5 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand/30 focus:border-brand"><?php echo e($data['Keterangan'] ?? ''); ?></textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Foto Barang</label>
            <?php if ($fotoLama): ?>
                <img src="<?php echo e(url('assets/img/barang/' . $fotoLama)); ?>" alt="Foto aset" class="h-24 rounded-lg border border-gray-100 mb-2 object-cover">
            <?php endif; ?>
            <input type="file" name="Foto" id="inputFoto" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                   class="block w-full text-sm text-gray-600 file:mr-4 file:rounded-lg file:border-0 file:bg-brand file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-brand-dark">
            <p class="text-xs text-gray-400 mt-1">JPG, PNG, atau WEBP, maksimal 2 MB.</p>
            <p id="namaFoto" class="text-xs text-brand mt-1"></p>
        </div>

        <div class="flex justify-end gap-3 pt-2">
            <a href="<?php echo e(url('pages/barang/index.php')); ?>" class="px-4 py-2.5 rounded-lg border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50">Batal</a>
            <button type="submit" class="px-4 py-2.5 rounded-lg bg-brand hover:bg-brand-dark text-white text-sm font-semibold">
                <?php echo $isEdit ? 'Simpan Perubahan' : 'Simpan Aset'; ?>
            </button>
        </div>
    </form>
</div>

<script>
function toggleBaru(selectId, inputId) {
    const select = document.getElementById(selectId);
    const input = document.getElementById(inputId);
    const isBaru = select.value === '__baru__';
    input.classList.toggle('hidden', !isBaru);
    input.required = isBaru;
}
document.getElementById('ID_Kategori').addEventListener('change', function () {
    toggleBaru('ID_Kategori', 'Nama_Kategori_Baru');
});
document.getElementById('ID_Ruang').addEventListener('change', function () {
    toggleBaru('ID_Ruang', 'Nama_Ruang_Baru');
});
document.getElementById('inputFoto').addEventListener('change', function () {
    const el = document.getElementById('namaFoto');
    el.textContent = this.files[0] ? 'File dipilih: ' + this.files[0].name : '';
});
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
