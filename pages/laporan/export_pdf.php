<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/laporan.php';
require_once dirname(__DIR__, 2) . '/libs/fpdf/fpdf.php';

$filter = laporan_input();
$rekap = laporan_rekap($pdo, $filter);

[$where, $params] = laporan_where($filter);
$asetStmt = $pdo->prepare(
    "SELECT b.Kode_Barang, b.Nama_Barang, b.Kondisi, b.Status_Barang, b.Jumlah, b.Tahun_Pengadaan,
            k.Nama_Kategori, r.Nama_Ruang
     FROM barang b
     JOIN kategori_barang k ON k.ID_Kategori = b.ID_Kategori
     JOIN ruang r ON r.ID_Ruang = b.ID_Ruang
     WHERE {$where}
     ORDER BY k.Nama_Kategori, b.Nama_Barang
     LIMIT 200"
);
$asetStmt->execute($params);
$aset = $asetStmt->fetchAll();

$pdf = new FPDF('L', 'mm', 'A4');
$pdf->SetTitle(pdf_latin('Laporan Akreditasi Sarpras SDN 1'));
$pdf->AddPage();
$pdf->SetMargins(12, 12, 12);

$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 6, pdf_latin('PEMERINTAH KABUPATEN/KOTA'), 0, 1, 'C');
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 6, pdf_latin('SDN 1'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(0, 5, pdf_latin('Jl. Pendidikan No. 1'), 0, 1, 'C');
$pdf->Ln(2);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 7, pdf_latin('LAPORAN SARANA DAN PRASARANA UNTUK AKREDITASI'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(0, 5, pdf_latin('Dicetak: ' . date('d/m/Y H:i') . ' oleh ' . (current_admin()['nama'] ?? 'Admin')), 0, 1, 'C');
$pdf->Ln(4);

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 6, pdf_latin('A. Rekapitulasi per Kategori'), 0, 1);
$pdf->SetFont('Arial', 'B', 8);
$pdf->SetFillColor(11, 31, 74);
$pdf->SetTextColor(255, 255, 255);
$w = [12, 55, 60, 80, 65];
$pdf->Cell($w[0], 7, 'No', 1, 0, 'C', true);
$pdf->Cell($w[1], 7, pdf_latin('Jenis / Kategori'), 1, 0, 'C', true);
$pdf->Cell($w[2], 7, 'Standar Minimal', 1, 0, 'C', true);
$pdf->Cell($w[3], 7, pdf_latin('Kondisi Sebenarnya'), 1, 0, 'C', true);
$pdf->Cell($w[4], 7, 'Status', 1, 1, 'C', true);

$pdf->SetFont('Arial', '', 8);
$pdf->SetTextColor(0, 0, 0);
$no = 1;
foreach ($rekap as $row) {
    $pdf->Cell($w[0], 6, (string) $no++, 1, 0, 'C');
    $pdf->Cell($w[1], 6, pdf_latin($row['Nama_Kategori']), 1);
    $pdf->Cell($w[2], 6, pdf_latin($row['standar']), 1);
    $pdf->Cell($w[3], 6, pdf_latin($row['kondisi_riil'] ?? ''), 1);
    $pdf->Cell($w[4], 6, pdf_latin($row['status_label']), 1, 1);
}

$pdf->Ln(6);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 6, pdf_latin('B. Daftar Aset Aktif (maks. 200 baris)'), 0, 1);
$pdf->SetFont('Arial', 'B', 8);
$pdf->SetFillColor(11, 31, 74);
$pdf->SetTextColor(255, 255, 255);
$c = [28, 55, 40, 40, 28, 28, 22, 31];
$headers = ['Kode', 'Nama Barang', 'Kategori', 'Ruang', 'Kondisi', 'Status', 'Jml', 'Tahun'];
foreach ($headers as $i => $h) {
    $pdf->Cell($c[$i], 7, pdf_latin($h), 1, $i === 7 ? 1 : 0, 'C', true);
}

$pdf->SetFont('Arial', '', 7);
$pdf->SetTextColor(0, 0, 0);
if (!$aset) {
    $pdf->Cell(array_sum($c), 6, pdf_latin('Tidak ada data aset untuk filter ini.'), 1, 1, 'C');
} else {
    foreach ($aset as $row) {
        $pdf->Cell($c[0], 6, pdf_latin($row['Kode_Barang']), 1);
        $pdf->Cell($c[1], 6, pdf_latin($row['Nama_Barang']), 1);
        $pdf->Cell($c[2], 6, pdf_latin($row['Nama_Kategori']), 1);
        $pdf->Cell($c[3], 6, pdf_latin($row['Nama_Ruang']), 1);
        $pdf->Cell($c[4], 6, pdf_latin(label_kondisi($row['Kondisi'])), 1);
        $pdf->Cell($c[5], 6, pdf_latin($row['Status_Barang']), 1);
        $pdf->Cell($c[6], 6, (string) $row['Jumlah'], 1, 0, 'C');
        $pdf->Cell($c[7], 6, (string) ($row['Tahun_Pengadaan'] ?: '-'), 1, 1, 'C');
    }
}

$pdf->Ln(10);
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(130, 5, '', 0, 0);
$pdf->Cell(60, 5, pdf_latin(date('d/m/Y')), 0, 1, 'C');
$pdf->Cell(130, 5, '', 0, 0);
$pdf->Cell(60, 5, pdf_latin('Petugas Inventaris'), 0, 1, 'C');
$pdf->Ln(16);
$pdf->Cell(130, 5, '', 0, 0);
$pdf->SetFont('Arial', 'U', 9);
$pdf->Cell(60, 5, pdf_latin(current_admin()['nama'] ?? 'Admin'), 0, 1, 'C');

$namaFile = 'Laporan_Akreditasi_Sarpras_' . date('Ymd') . '.pdf';
$pdf->Output('I', $namaFile);
exit;
