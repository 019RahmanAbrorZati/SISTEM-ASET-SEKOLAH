<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEdit = $id > 0;
$data = ['Nama_Kategori' => '', 'Keterangan_Kategori' => ''];
$error = null;

if ($isEdit) {
    $stmt = $pdo->prepare('SELECT ID_Kategori, Nama_Kategori, Keterangan_Kategori FROM kategori_barang WHERE ID_Kategori = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        set_flash('error', 'Kategori tidak ditemukan.');
        redirect('pages/master/index.php?tab=kategori');
    }
    $data = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $error = 'Sesi formulir tidak valid. Silakan coba lagi.';
    } else {
        $nama = trim($_POST['Nama_Kategori'] ?? '');
        $keterangan = trim($_POST['Keterangan_Kategori'] ?? '');
        $data['Nama_Kategori'] = $nama;
        $data['Keterangan_Kategori'] = $keterangan;

        if ($nama === '') {
            $error = 'Nama kategori wajib diisi.';
        } elseif (mb_strlen($nama) > 30) {
            $error = 'Nama kategori maksimal 30 karakter.';
        } elseif (mb_strlen($keterangan) > 100) {
            $error = 'Keterangan maksimal 100 karakter.';
        } else {
            $cek = $pdo->prepare(
                'SELECT ID_Kategori FROM kategori_barang WHERE Nama_Kategori = ? AND ID_Kategori <> ? LIMIT 1'
            );
            $cek->execute([$nama, $id]);
            if ($cek->fetch()) {
                $error = 'Nama kategori sudah digunakan.';
            } elseif ($isEdit) {
                $stmt = $pdo->prepare(
                    'UPDATE kategori_barang SET Nama_Kategori = ?, Keterangan_Kategori = ? WHERE ID_Kategori = ?'
                );
                $stmt->execute([$nama, $keterangan !== '' ? $keterangan : null, $id]);
                set_flash('success', 'Kategori berhasil diubah.');
                redirect('pages/master/index.php?tab=kategori');
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO kategori_barang (Nama_Kategori, Keterangan_Kategori) VALUES (?, ?)'
                );
                $stmt->execute([$nama, $keterangan !== '' ? $keterangan : null]);
                set_flash('success', 'Kategori berhasil ditambahkan.');
                redirect('pages/master/index.php?tab=kategori');
            }
        }
    }
}

$pageTitle = $isEdit ? 'Ubah Kategori' : 'Tambah Kategori';
$currentPage = 'master';
require_once dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="flex items-start justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900"><?php echo $isEdit ? 'Ubah Kategori' : 'Tambah Kategori Baru'; ?></h1>
        <p class="text-sm text-gray-500 mt-1">Master data kategori dipakai saat menambah aset.</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 max-w-2xl">
    <?php if ($error): ?>
        <div class="mb-4 rounded-lg bg-red-50 border border-red-100 text-red-700 text-sm px-4 py-3"><?php echo e($error); ?></div>
    <?php endif; ?>

    <form method="post" class="space-y-4">
        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Kategori <span class="text-red-500">*</span></label>
            <input type="text" name="Nama_Kategori" maxlength="30" required
                   value="<?php echo e($data['Nama_Kategori']); ?>"
                   placeholder="Contoh: Elektronik"
                   class="w-full rounded-lg border border-gray-200 py-2.5 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand/30 focus:border-brand">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan</label>
            <textarea name="Keterangan_Kategori" rows="3" maxlength="100"
                      placeholder="Catatan singkat (opsional)"
                      class="w-full rounded-lg border border-gray-200 py-2.5 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand/30 focus:border-brand"><?php echo e($data['Keterangan_Kategori']); ?></textarea>
        </div>

        <div class="flex justify-end gap-3 pt-2">
            <a href="<?php echo e(url('pages/master/index.php?tab=kategori')); ?>" class="px-4 py-2.5 rounded-lg border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50">Batal</a>
            <button type="submit" class="px-4 py-2.5 rounded-lg bg-brand hover:bg-brand-dark text-white text-sm font-semibold">
                <?php echo $isEdit ? 'Simpan Perubahan' : 'Simpan Kategori'; ?>
            </button>
        </div>
    </form>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
