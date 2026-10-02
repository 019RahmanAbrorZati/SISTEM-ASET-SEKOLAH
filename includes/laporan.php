<?php
/**
 * Query bersama untuk halaman laporan dan export PDF.
 */

function laporan_input(): array
{
    $tahun = trim($_GET['tahun'] ?? '');
    $kategori = (int) ($_GET['kategori'] ?? 0);
    $kondisi = trim($_GET['kondisi'] ?? '');
    if (!in_array($kondisi, ['', 'baik', 'rusak_ringan', 'rusak_berat'], true)) {
        $kondisi = '';
    }
    return compact('tahun', 'kategori', 'kondisi');
}

function laporan_where(array $filter, string $alias = 'b'): array
{
    $where = ["{$alias}.Status_Barang <> 'dihapus'"];
    $params = [];

    if ($filter['tahun'] !== '' && ctype_digit($filter['tahun'])) {
        $where[] = "{$alias}.Tahun_Pengadaan = ?";
        $params[] = (int) $filter['tahun'];
    }
    if ($filter['kategori'] > 0) {
        $where[] = "{$alias}.ID_Kategori = ?";
        $params[] = $filter['kategori'];
    }
    if ($filter['kondisi'] !== '') {
        $where[] = "{$alias}.Kondisi = ?";
        $params[] = $filter['kondisi'];
    }

    return [implode(' AND ', $where), $params];
}

function laporan_rekap(PDO $pdo, array $filter): array
{
    $join = ["b.ID_Kategori = k.ID_Kategori", "b.Status_Barang <> 'dihapus'"];
    $params = [];

    if ($filter['tahun'] !== '' && ctype_digit($filter['tahun'])) {
        $join[] = 'b.Tahun_Pengadaan = ?';
        $params[] = (int) $filter['tahun'];
    }
    if ($filter['kondisi'] !== '') {
        $join[] = 'b.Kondisi = ?';
        $params[] = $filter['kondisi'];
    }

    $sql = 'SELECT k.ID_Kategori, k.Nama_Kategori,
                   COUNT(b.ID_Barang) AS jumlah,
                   SUM(CASE WHEN b.Kondisi = \'baik\' THEN 1 ELSE 0 END) AS jml_baik,
                   SUM(CASE WHEN b.Kondisi IN (\'rusak_ringan\', \'rusak_berat\') THEN 1 ELSE 0 END) AS jml_rusak,
                   SUM(CASE WHEN b.Status_Barang = \'perlu_perbaikan\' THEN 1 ELSE 0 END) AS jml_perbaikan
            FROM kategori_barang k
            LEFT JOIN barang b ON ' . implode(' AND ', $join);

    if ($filter['kategori'] > 0) {
        $sql .= ' WHERE k.ID_Kategori = ?';
        $params[] = $filter['kategori'];
    }

    $sql .= ' GROUP BY k.ID_Kategori, k.Nama_Kategori ORDER BY k.Nama_Kategori';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    foreach ($rows as &$row) {
        $jumlah = (int) $row['jumlah'];
        $rusak = (int) $row['jml_rusak'];
        $perbaikan = (int) $row['jml_perbaikan'];
        if ($jumlah === 0) {
            $row['status_sesuai'] = 'tidak_tersedia';
            $row['status_label'] = 'Tidak Tersedia';
        } elseif ($perbaikan > 0 || $rusak > 0) {
            $row['status_sesuai'] = 'perlu_perbaikan';
            $row['status_label'] = 'Perlu Perbaikan';
        } else {
            $row['status_sesuai'] = 'sesuai';
            $row['status_label'] = 'Sesuai Standar';
        }
        $row['standar'] = 'Tercatat di inventaris sekolah';
        $row['kondisi_riil'] = $jumlah === 0
            ? 'Belum ada aset'
            : ((int) $row['jml_baik'] . ' baik, ' . $rusak . ' rusak (total ' . $jumlah . ')');
    }
    unset($row);

    return $rows;
}

function badge_kesesuaian(string $kode, string $label): string
{
    $class = [
        'sesuai'           => 'bg-emerald-50 text-emerald-700',
        'perlu_perbaikan'  => 'bg-amber-50 text-amber-700',
        'tidak_tersedia'   => 'bg-red-50 text-red-600',
    ][$kode] ?? 'bg-gray-100 text-gray-600';

    return '<span class="inline-flex rounded-full text-xs font-semibold px-2.5 py-1 ' . $class . '">' . e($label) . '</span>';
}

function pdf_latin(?string $text): string
{
    $text = (string) $text;
    $converted = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text);
    return $converted !== false ? $converted : $text;
}
