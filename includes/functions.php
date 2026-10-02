<?php
/**
 * Helper umum: URL, flash message, CSRF, format tanggal.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Path URL relatif ke folder project (aman di subfolder XAMPP htdocs).
 */
function url(string $path = ''): string
{
    static $base = null;

    if ($base === null) {
        $doc = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
        $app = str_replace('\\', '/', APP_ROOT);
        $base = '';

        if ($doc && $app && strpos($app, $doc) === 0) {
            $base = substr($app, strlen($doc));
        }

        $base = rtrim($base, '/');
    }

    $path = ltrim($path, '/');
    return ($base === '' ? '' : $base) . '/' . $path;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function set_flash(string $key, string $message): void
{
    $_SESSION['flash'][$key] = $message;
}

function get_flash(string $key): ?string
{
    if (empty($_SESSION['flash'][$key])) {
        return null;
    }

    $message = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);
    return $message;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function format_tanggal(?string $datetime, string $format = 'd M Y'): string
{
    if (!$datetime) {
        return '-';
    }

    $ts = strtotime($datetime);
    if ($ts === false) {
        return '-';
    }

    $bulan = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
        5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
        9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
    ];

    $d = (int) date('j', $ts);
    $m = (int) date('n', $ts);
    $y = date('Y', $ts);

    if ($format === 'd M Y') {
        return $d . ' ' . $bulan[$m] . ' ' . $y;
    }

    return date($format, $ts);
}

function current_admin(): array
{
    return [
        'id'       => $_SESSION['admin_id'] ?? null,
        'username' => $_SESSION['admin_username'] ?? '',
        'nama'     => $_SESSION['admin_nama'] ?? 'Admin',
    ];
}

function label_kondisi(?string $kondisi): string
{
    $map = [
        'baik'         => 'Baik',
        'rusak_ringan' => 'Rusak Ringan',
        'rusak_berat'  => 'Rusak',
    ];
    return $map[$kondisi] ?? ($kondisi ?: '-');
}

function badge_kondisi(?string $kondisi): string
{
    $label = label_kondisi($kondisi);
    if ($kondisi === 'baik') {
        return '<span class="inline-flex rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold px-2.5 py-1">' . e($label) . '</span>';
    }
    if ($kondisi === 'rusak_ringan' || $kondisi === 'rusak_berat') {
        return '<span class="inline-flex rounded-full bg-red-50 text-red-600 text-xs font-semibold px-2.5 py-1">' . e($label) . '</span>';
    }
    return '<span class="text-gray-500 text-xs">' . e($label) . '</span>';
}

function badge_status(?string $status): string
{
    $map = [
        'tersedia'         => ['Tersedia', 'bg-brand/10 text-brand'],
        'dipinjam'         => ['Dipinjam', 'bg-blue-50 text-blue-700'],
        'perlu_perbaikan'  => ['Perlu Perbaikan', 'bg-amber-50 text-amber-700'],
        'dihapus'          => ['Nonaktif', 'bg-gray-100 text-gray-600'],
    ];
    $item = $map[$status] ?? [$status ?: '-', 'bg-gray-100 text-gray-600'];
    return '<span class="inline-flex rounded-full text-xs font-semibold px-2.5 py-1 ' . $item[1] . '">' . e($item[0]) . '</span>';
}

function sql_jumlah_dipinjam(string $aliasBarang = 'b'): string
{
    return "(SELECT COALESCE(SUM(dp.Jumlah_Pinjam), 0)
             FROM detail_peminjaman dp
             INNER JOIN peminjaman pj ON pj.ID_Peminjaman = dp.ID_Peminjaman
             WHERE dp.ID_Barang = {$aliasBarang}.ID_Barang
               AND pj.Status_Peminjaman = 'dipinjam')";
}

function status_operasional(array $row): string
{
    $status = $row['Status_Barang'] ?? 'tersedia';
    if (in_array($status, ['dihapus', 'perlu_perbaikan'], true)) {
        return $status;
    }
    $jumlah = (int) ($row['Jumlah'] ?? 0);
    $dipinjam = (int) ($row['jumlah_dipinjam'] ?? 0);
    if ($jumlah > 0 && $dipinjam >= $jumlah) {
        return 'dipinjam';
    }
    return 'tersedia';
}

function perbarui_status_stok(PDO $pdo, int $idBarang, ?string $kondisiBaru = null): void
{
    $stmt = $pdo->prepare('SELECT Jumlah, Status_Barang, Kondisi FROM barang WHERE ID_Barang = ?');
    $stmt->execute([$idBarang]);
    $barang = $stmt->fetch();
    if (!$barang || $barang['Status_Barang'] === 'dihapus') {
        return;
    }

    $dipinjamStmt = $pdo->prepare(
        "SELECT COALESCE(SUM(d.Jumlah_Pinjam), 0)
         FROM detail_peminjaman d
         JOIN peminjaman p ON p.ID_Peminjaman = d.ID_Peminjaman
         WHERE d.ID_Barang = ? AND p.Status_Peminjaman = 'dipinjam'"
    );
    $dipinjamStmt->execute([$idBarang]);
    $dipinjam = (int) $dipinjamStmt->fetchColumn();
    $jumlah = (int) $barang['Jumlah'];
    $kondisi = $kondisiBaru ?? $barang['Kondisi'];

    if ($kondisi === 'rusak_berat') {
        $status = 'perlu_perbaikan';
    } elseif ($jumlah > 0 && $dipinjam >= $jumlah) {
        $status = 'dipinjam';
    } else {
        $status = 'tersedia';
    }

    $upd = $pdo->prepare('UPDATE barang SET Status_Barang = ?, Kondisi = ? WHERE ID_Barang = ? AND Status_Barang <> ?');
    $upd->execute([$status, $kondisi, $idBarang, 'dihapus']);
}

function badge_peminjaman(string $status): string
{
    $map = [
        'dipinjam'      => ['Pinjam', 'bg-gray-100 text-gray-700'],
        'terlambat'     => ['Terlambat', 'bg-red-50 text-red-600'],
        'dikembalikan'  => ['Dikembalikan', 'bg-emerald-50 text-emerald-700'],
    ];
    $item = $map[$status] ?? [$status, 'bg-gray-100 text-gray-600'];
    return '<span class="inline-flex rounded-full text-xs font-semibold px-2.5 py-1 ' . $item[1] . '">' . e($item[0]) . '</span>';
}

function status_peminjaman_tampil(array $row): string
{
    if (($row['Status_Peminjaman'] ?? '') === 'dikembalikan') {
        return 'dikembalikan';
    }
    $rencana = $row['Tanggal_Rencana_Kembali'] ?? null;
    if ($rencana && $rencana < date('Y-m-d')) {
        return 'terlambat';
    }
    return 'dipinjam';
}

function badge_ba(?string $status): string
{
    if ($status === 'final') {
        return '<span class="inline-flex rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold px-2.5 py-1">Final</span>';
    }
    return '<span class="inline-flex rounded-full bg-amber-50 text-amber-700 text-xs font-semibold px-2.5 py-1">Draft</span>';
}

function generate_nomor_ba(PDO $pdo): string
{
    $tahun = date('Y');
    $prefix = 'BA-P/' . $tahun . '/';
    $stmt = $pdo->prepare('SELECT Nomor_BA FROM berita_acara_penghapusan WHERE Nomor_BA LIKE ? ORDER BY ID_BA DESC LIMIT 1');
    $stmt->execute([$prefix . '%']);
    $last = $stmt->fetchColumn();
    $next = 1;
    if ($last && preg_match('/(\d+)$/', $last, $m)) {
        $next = (int) $m[1] + 1;
    }
    return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
}

function peminjaman_terlambat(PDO $pdo, int $limit = 8): array
{
    try {
        $limit = max(1, $limit);
        $stmt = $pdo->query(
            "SELECT p.ID_Peminjaman, p.Tanggal_Rencana_Kembali, pm.Nama, pm.Status_Peminjam,
                    GROUP_CONCAT(CONCAT(d.Jumlah_Pinjam, ' ', b.Nama_Barang) SEPARATOR ', ') AS daftar_barang
             FROM peminjaman p
             JOIN peminjam pm ON pm.ID_Peminjam = p.ID_Peminjam
             JOIN detail_peminjaman d ON d.ID_Peminjaman = p.ID_Peminjaman
             JOIN barang b ON b.ID_Barang = d.ID_Barang
             WHERE p.Status_Peminjaman = 'dipinjam'
               AND p.Tanggal_Rencana_Kembali IS NOT NULL
               AND p.Tanggal_Rencana_Kembali < CURDATE()
             GROUP BY p.ID_Peminjaman, p.Tanggal_Rencana_Kembali, pm.Nama, pm.Status_Peminjam
             ORDER BY p.Tanggal_Rencana_Kembali ASC
             LIMIT {$limit}"
        );
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

function jumlah_peminjaman_terlambat(PDO $pdo): int
{
    try {
        return (int) $pdo->query(
            "SELECT COUNT(*) FROM peminjaman
             WHERE Status_Peminjaman = 'dipinjam'
               AND Tanggal_Rencana_Kembali IS NOT NULL
               AND Tanggal_Rencana_Kembali < CURDATE()"
        )->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}
