# Sistem Aset Sekolah

Sistem Manajemen berbasis web untuk mengelola sarana dan prasarana sekolah dasar: pendataan aset, peminjaman, pelaporan kerusakan, berita acara penghapusan, serta laporan dalam bentuk PDF. Dirancang untuk berjalan pada satu komputer sekolah menggunakan XAMPP, tanpa hosting dan tanpa koneksi internet.

> Proyek ini dikembangkan dalam program Kuliah Kerja Nyata Tematik (KKNT) Universitas Negeri Surabaya 2026, sebagai Mini Project konversi mata kuliah Analisis dan Desain Perangkat Lunak serta Manajemen Proyek. Sistem telah dipasang dan digunakan oleh sekolah dasar negeri mitra. Identitas sekolah, data aset, dan kredensial asli sengaja tidak disertakan dalam repositori ini.

## Fitur

- **Dashboard** — ringkasan jumlah aset, sebaran kategori, status peminjaman, dan aset yang perlu perbaikan.
- **Data Aset** — kode barang, kategori, ruang, jumlah, satuan, kondisi, tahun pengadaan, sumber dana, dan foto, dilengkapi pencarian dan filter.
- **Peminjaman** — pencatatan peminjaman dan pengembalian beserta kondisi barang saat kembali. Hanya barang berstatus tersedia yang dapat dipinjam.
- **Kerusakan & Berita Acara** — kerusakan dicatat lebih dulu, dan barang baru berstatus *dihapus* setelah berita acara penghapusan difinalisasi. Data tidak dihapus permanen sehingga riwayatnya tetap dapat ditelusuri.
- **Laporan** — rekap per kategori dan daftar aset yang dapat diunduh sebagai PDF.
- **Master Data** — kategori dan ruang. Data yang masih dipakai aset tidak dapat dihapus.
- Mode terang dan gelap.

## Teknologi

- PHP native dengan PDO
- MySQL 
- Tailwind CSS (CDN) dan JavaScript
- FPDF untuk pembuatan PDF
- XAMPP sebagai server lokal

## Instalasi

Kebutuhan: XAMPP dengan PHP 7.4 atau lebih baru.

1. Salin project ke folder `htdocs`:

   ```bash
   cd C:\xampp\htdocs
   git clone https://github.com/019RahmanAbrorZati/SISTEM-ASET-SEKOLAH.git
   ```

2. Jalankan **Apache** dan **MySQL** dari XAMPP Control Panel.
3. Buka `http://localhost/phpmyadmin`, lalu impor berurutan:
   1. `database/schema.sql` — membuat database `sarpras` beserta tabelnya
   2. `database/alter_tambahan.sql` — menambahkan kolom pendukung aplikasi
4. Buka `database/seed_admin.php`, isi `$passwordBaru` dengan sandi pilihan Anda, lalu jalankan:

   ```bash
   cd C:\xampp\htdocs\SISTEM-ASET-SEKOLAH
   C:\xampp\php\php.exe database\seed_admin.php
   ```

5. Buka `http://localhost/SISTEM-ASET-SEKOLAH/login.php` dan masuk dengan username `SDN 1` serta sandi yang Anda isi.

Pengaturan koneksi database ada di `config/database.php` (bawaan: user `root` tanpa sandi). Panduan lengkap, termasuk pencadangan data dan penanganan kendala, tersedia di [PANDUAN-INSTALASI.md](PANDUAN-INSTALASI.md).

## Struktur Folder

```
├── config/          Koneksi database
├── includes/        Header, footer, autentikasi, fungsi pembantu
├── pages/
│   ├── barang/      Data aset
│   ├── peminjaman/  Peminjaman dan pengembalian
│   ├── kerusakan/   Kerusakan dan berita acara
│   ├── laporan/     Laporan dan ekspor PDF
│   └── master/      Kategori dan ruang
├── database/        Skema SQL dan skrip akun admin
├── assets/          CSS, JavaScript, dan gambar
└── libs/fpdf/       Pustaka PDF
```

## Keamanan

- Sandi admin disimpan sebagai hash bcrypt, bukan teks biasa.
- Setelah mengisi `$passwordBaru` di `seed_admin.php`, kembalikan ke nilai placeholder sebelum commit.
- Hasil export database dan foto aset sudah dikecualikan melalui `.gitignore`.

## Tim Pengembang

- **Muhamad Fauzan** — antarmuka dan frontend
- **Rahman Abror Zati** — backend dan basis data

Program Studi S1 Teknik Informatika, Universitas Negeri Surabaya.