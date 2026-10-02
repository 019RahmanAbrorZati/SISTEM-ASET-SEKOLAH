<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEdit = $id > 0;
$data = ['Nama_Ruang' => '', 'Lokasi' => ''];
$error = null;

if ($isEdit) {
    $stmt = $pdo->prepare('SELECT ID_Ruang, Nama_Ruang, Lokasi FROM ruang WHERE ID_Ruang = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        set_flash('error', 'Ruang tidak ditemukan.');
        redirect('pages/master/index.php?tab=ruang');
    }
    $data = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $error = 'Sesi formulir tidak valid. Silakan coba lagi.';
    } else {
        $nama = trim($_POST['Nama_Ruang'] ?? '');
        $lokasi = trim($_POST['Lokasi'] ?? '');
        $data['Nama_Ruang'] = $nama;
        $data['Lokasi'] = $lokasi;

        if ($nama === '') {
            $error = 'Nama ruang wajib diisi.';
        } elseif (mb_strlen($nama) > 30) {
            $error = 'Nama ruang maksimal 30 karakter.';
        } elseif (mb_strlen($lokasi) > 30) {
            $error = 'Lokasi maksimal 30 karakter.';
        } else {
            $cek = $pdo->prepare(
                'SELECT ID_Ruang FROM ruang WHERE Nama_Ruang = ? AND ID_Ruang <> ? LIMIT 1'
            );
            $cek->execute([$nama, $id]);
            if ($cek->fetch()) {
                $error = 'Nama ruang sudah digunakan.';
            } elseif ($isEdit) {
                $stmt = $pdo->prepare(
                    'UPDATE ruang SET Nama_Ruang = ?, Lokasi = ? WHERE ID_Ruang = ?'
                );
                $stmt->execute([$nama, $lokasi !== '' ? $lokasi : null, $id]);
                set_flash('success', 'Ruang berhasil diubah.');
                redirect('pages/master/index.php?tab=ruang');
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO ruang (Nama_Ruang, Lokasi) VALUES (?, ?)'
                );
                $stmt->execute([$nama, $lokasi !== '' ? $lokasi : null]);
                set_flash('success', 'Ruang berhasil ditambahkan.');
                redirect('pages/master/index.php?tab=ruang');
            }
        }
    }
}

$pageTitle = $isEdit ? 'Ubah Ruang' : 'Tambah Ruang';
$currentPage = 'master';
require_once dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="flex items-start justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900"><?php echo $isEdit ? 'Ubah Ruang' : 'Tambah Ruang Baru'; ?></h1>
        <p class="text-sm text-gray-500 mt-1">Ruang dipakai sebagai lokasi penyimpanan aset.</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 max-w-2xl">
    <?php if ($error): ?>
        <div class="mb-4 rounded-lg bg-red-50 border border-red-100 text-red-700 text-sm px-4 py-3"><?php echo e($error); ?></div>
    <?php endif; ?>

    <form method="post" class="space-y-4">
        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Ruang <span class="text-red-500">*</span></label>
            <input type="text" name="Nama_Ruang" maxlength="30" required
                   value="<?php echo e($data['Nama_Ruang']); ?>"
                   placeholder="Contoh: Ruang Guru, Kelas 1A"
                   class="w-full rounded-lg border border-gray-200 py-2.5 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand/30 focus:border-brand">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi</label>
            <input type="text" name="Lokasi" maxlength="30"
                   value="<?php echo e($data['Lokasi']); ?>"
                   placeholder="Contoh: Gedung Utama"
                   class="w-full rounded-lg border border-gray-200 py-2.5 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand/30 focus:border-brand">
        </div>

        <div class="flex justify-end gap-3 pt-2">
            <a href="<?php echo e(url('pages/master/index.php?tab=ruang')); ?>" class="px-4 py-2.5 rounded-lg border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50">Batal</a>
            <button type="submit" class="px-4 py-2.5 rounded-lg bg-brand hover:bg-brand-dark text-white text-sm font-semibold">
                <?php echo $isEdit ? 'Simpan Perubahan' : 'Simpan Ruang'; ?>
            </button>
        </div>
    </form>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
