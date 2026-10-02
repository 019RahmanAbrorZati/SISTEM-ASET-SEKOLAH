-- Kolom tambahan untuk business rule aplikasi (tidak mengubah relasi ERD).
-- Jalankan sekali di phpMyAdmin setelah tabel ERD sudah ada.

USE `sarpras`;

-- Soft delete / status operasional barang (tersedia, dipinjam, perlu_perbaikan, dihapus)
ALTER TABLE `barang`
  ADD COLUMN `Status_Barang` ENUM('tersedia','dipinjam','perlu_perbaikan','dihapus')
    NOT NULL DEFAULT 'tersedia' AFTER `Sumber_Dana`,
  ADD COLUMN `Foto` VARCHAR(255) DEFAULT NULL AFTER `Status_Barang`,
  ADD COLUMN `Keterangan` TEXT DEFAULT NULL AFTER `Foto`;

-- Status dokumen BA: draft dulu, final baru menandai barang dihapus (soft delete)
ALTER TABLE `berita_acara_penghapusan`
  ADD COLUMN `Status_BA` ENUM('draft','final') NOT NULL DEFAULT 'draft' AFTER `Kondisi_Barang`;

-- Perlebar nama barang agar sesuai mockup (contoh: Proyektor Epson EB-X400)
ALTER TABLE `barang`
  MODIFY `Nama_Barang` VARCHAR(150) NOT NULL,
  MODIFY `Kode_Barang` VARCHAR(50) NOT NULL;
