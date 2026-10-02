-- ============================================================
-- Skema database: sarpras
-- Disesuaikan dengan ERD phpMyAdmin (PowerDesigner)
-- Engine: InnoDB | Collation: utf8mb4_general_ci
--
-- Jika tabel SUDAH ada di phpMyAdmin, JANGAN jalankan CREATE.
-- Cukup jalankan bagian INSERT admin di paling bawah,
-- lalu file database/alter_tambahan.sql (kolom pendukung aplikasi).
-- ============================================================

CREATE DATABASE IF NOT EXISTS `sarpras`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE `sarpras`;

CREATE TABLE IF NOT EXISTS `admin` (
  `ID_Admin` INT(11) NOT NULL AUTO_INCREMENT,
  `Username` VARCHAR(20) NOT NULL,
  `Password` VARCHAR(255) NOT NULL,
  `Nama_Lengkap` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`ID_Admin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `kategori_barang` (
  `ID_Kategori` INT(11) NOT NULL AUTO_INCREMENT,
  `Nama_Kategori` VARCHAR(30) NOT NULL,
  `Keterangan_Kategori` VARCHAR(100) DEFAULT NULL,
  PRIMARY KEY (`ID_Kategori`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `ruang` (
  `ID_Ruang` INT(11) NOT NULL AUTO_INCREMENT,
  `Nama_Ruang` VARCHAR(30) NOT NULL,
  `Lokasi` VARCHAR(30) DEFAULT NULL,
  PRIMARY KEY (`ID_Ruang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `barang` (
  `ID_Barang` INT(11) NOT NULL AUTO_INCREMENT,
  `ID_Ruang` INT(11) NOT NULL,
  `ID_Kategori` INT(11) NOT NULL,
  `Kode_Barang` VARCHAR(20) NOT NULL,
  `Nama_Barang` VARCHAR(30) NOT NULL,
  `Jumlah` INT(11) NOT NULL,
  `Satuan` VARCHAR(25) DEFAULT NULL,
  `Kondisi` VARCHAR(20) DEFAULT NULL,
  `Tahun_Pengadaan` INT(11) DEFAULT NULL,
  `Sumber_Dana` VARCHAR(30) DEFAULT NULL,
  PRIMARY KEY (`ID_Barang`),
  KEY `FK_BARANG_MENEMPATK_RUANG` (`ID_Ruang`),
  KEY `FK_BARANG_MEMILIKI_KATEGORI` (`ID_Kategori`),
  CONSTRAINT `FK_BARANG_MEMILIKI_KATEGORI`
    FOREIGN KEY (`ID_Kategori`) REFERENCES `kategori_barang` (`ID_Kategori`) ON UPDATE CASCADE,
  CONSTRAINT `FK_BARANG_MENEMPATK_RUANG`
    FOREIGN KEY (`ID_Ruang`) REFERENCES `ruang` (`ID_Ruang`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `peminjam` (
  `ID_Peminjam` INT(11) NOT NULL AUTO_INCREMENT,
  `Nama` VARCHAR(25) NOT NULL,
  `Status_Peminjam` VARCHAR(25) NOT NULL,
  `Kontak` VARCHAR(25) DEFAULT NULL,
  PRIMARY KEY (`ID_Peminjam`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `peminjaman` (
  `ID_Peminjaman` INT(11) NOT NULL AUTO_INCREMENT,
  `ID_Admin` INT(11) NOT NULL,
  `ID_Peminjam` INT(11) NOT NULL,
  `Tanggal_Pinjam` DATE NOT NULL,
  `Tanggal_Rencana_Kembali` DATE DEFAULT NULL,
  `Keperluan` VARCHAR(50) DEFAULT NULL,
  `Status_Peminjaman` VARCHAR(30) NOT NULL,
  PRIMARY KEY (`ID_Peminjaman`),
  KEY `FK_PEMINJAM_MENCATAT_ADMIN` (`ID_Admin`),
  KEY `FK_PEMINJAM_MENGAJUKA_PEMINJAM` (`ID_Peminjam`),
  CONSTRAINT `FK_PEMINJAM_MENCATAT_ADMIN`
    FOREIGN KEY (`ID_Admin`) REFERENCES `admin` (`ID_Admin`) ON UPDATE CASCADE,
  CONSTRAINT `FK_PEMINJAM_MENGAJUKA_PEMINJAM`
    FOREIGN KEY (`ID_Peminjam`) REFERENCES `peminjam` (`ID_Peminjam`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `detail_peminjaman` (
  `ID_Detail_Pinjam` INT(11) NOT NULL AUTO_INCREMENT,
  `ID_Peminjaman` INT(11) NOT NULL,
  `ID_Barang` INT(11) NOT NULL,
  `Jumlah_Pinjam` INT(11) NOT NULL,
  `Kondisi_Saat_Pinjam` VARCHAR(30) DEFAULT NULL,
  PRIMARY KEY (`ID_Detail_Pinjam`),
  KEY `FK_DETAIL_P_TERDIRI_D_PEMINJAM` (`ID_Peminjaman`),
  KEY `FK_DETAIL_P_DIPINJAM__BARANG` (`ID_Barang`),
  CONSTRAINT `FK_DETAIL_P_DIPINJAM__BARANG`
    FOREIGN KEY (`ID_Barang`) REFERENCES `barang` (`ID_Barang`) ON UPDATE CASCADE,
  CONSTRAINT `FK_DETAIL_P_TERDIRI_D_PEMINJAM`
    FOREIGN KEY (`ID_Peminjaman`) REFERENCES `peminjaman` (`ID_Peminjaman`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `pengembalian` (
  `ID_Pengembalian` INT(11) NOT NULL AUTO_INCREMENT,
  `ID_Peminjaman` INT(11) NOT NULL,
  `ID_Admin` INT(11) NOT NULL,
  `Tanggal_Kembali` DATE NOT NULL,
  `Keterangan_Pengembalian` VARCHAR(30) DEFAULT NULL,
  PRIMARY KEY (`ID_Pengembalian`),
  KEY `FK_PENGEMBALIAN_DISELESAI_PEMINJAMAN` (`ID_Peminjaman`),
  KEY `FK_PENGEMBALIAN_MEMPROSES_ADMIN` (`ID_Admin`),
  CONSTRAINT `FK_PENGEMBALIAN_DISELESAI_PEMINJAMAN`
    FOREIGN KEY (`ID_Peminjaman`) REFERENCES `peminjaman` (`ID_Peminjaman`) ON UPDATE CASCADE,
  CONSTRAINT `FK_PENGEMBALIAN_MEMPROSES_ADMIN`
    FOREIGN KEY (`ID_Admin`) REFERENCES `admin` (`ID_Admin`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `detail_pengembalian` (
  `ID_Detail_Kembali` INT(11) NOT NULL AUTO_INCREMENT,
  `ID_Detail_Pinjam` INT(11) NOT NULL,
  `ID_Pengembalian` INT(11) NOT NULL,
  `Jumlah_Kembali` INT(11) NOT NULL,
  `Kondisi_Saat_Kembali` VARCHAR(20) DEFAULT NULL,
  PRIMARY KEY (`ID_Detail_Kembali`),
  KEY `FK_DETAIL_P_DIKEMBALI_DETAIL_P` (`ID_Detail_Pinjam`),
  KEY `FK_DETAIL_P_MENCATAT__PENGEMBA` (`ID_Pengembalian`),
  CONSTRAINT `FK_DETAIL_P_DIKEMBALI_DETAIL_P`
    FOREIGN KEY (`ID_Detail_Pinjam`) REFERENCES `detail_peminjaman` (`ID_Detail_Pinjam`) ON UPDATE CASCADE,
  CONSTRAINT `FK_DETAIL_P_MENCATAT__PENGEMBA`
    FOREIGN KEY (`ID_Pengembalian`) REFERENCES `pengembalian` (`ID_Pengembalian`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `kerusakan` (
  `ID_Kerusakan` INT(11) NOT NULL AUTO_INCREMENT,
  `ID_Barang` INT(11) NOT NULL,
  `ID_Admin` INT(11) NOT NULL,
  `Tanggal_Lapor` DATE NOT NULL,
  `Jenis_Kerusakan` VARCHAR(20) DEFAULT NULL,
  `Tingkat_Kerusakan` VARCHAR(20) DEFAULT NULL,
  `Status_Kerusakan` VARCHAR(20) NOT NULL,
  PRIMARY KEY (`ID_Kerusakan`),
  KEY `FK_KERUSAKAN_MENGALAMI_BARANG` (`ID_Barang`),
  KEY `FK_KERUSAKAN_MELAPORKA_ADMIN` (`ID_Admin`),
  CONSTRAINT `FK_KERUSAKAN_MELAPORKA_ADMIN`
    FOREIGN KEY (`ID_Admin`) REFERENCES `admin` (`ID_Admin`) ON UPDATE CASCADE,
  CONSTRAINT `FK_KERUSAKAN_MENGALAMI_BARANG`
    FOREIGN KEY (`ID_Barang`) REFERENCES `barang` (`ID_Barang`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Berita acara hanya terhubung ke kerusakan (sisi many).
-- Status barang diubah ke "dihapus" SETELAH BA berstatus final — bukan hapus baris.
CREATE TABLE IF NOT EXISTS `berita_acara_penghapusan` (
  `ID_BA` INT(11) NOT NULL AUTO_INCREMENT,
  `ID_Kerusakan` INT(11) NOT NULL,
  `Nomor_BA` VARCHAR(20) NOT NULL,
  `Tanggal_BA` DATE NOT NULL,
  `Alasan_Penghapusan` VARCHAR(100) DEFAULT NULL,
  `Kondisi_Barang` VARCHAR(30) DEFAULT NULL,
  PRIMARY KEY (`ID_BA`),
  KEY `FK_BERITA_A_MEMICU_KERUSAKA` (`ID_Kerusakan`),
  CONSTRAINT `FK_BERITA_A_MEMICU_KERUSAKA`
    FOREIGN KEY (`ID_Kerusakan`) REFERENCES `kerusakan` (`ID_Kerusakan`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Akun sementara. Username dan sandi final disetel oleh database/seed_admin.php.
INSERT INTO `admin` (`Username`, `Password`, `Nama_Lengkap`)
SELECT 'irma', '$2y$12$E4VrXORln/0AdCGv0X0IAO6DVJ/qLvQ6S.YTlJQQzQarjVfyMukVq', 'Administrator'
WHERE NOT EXISTS (SELECT 1 FROM `admin` WHERE `Username` = 'irma');
