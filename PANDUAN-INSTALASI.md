# Panduan Instalasi — Sistem Aset SDN 1

Web ini **tidak perlu hosting**. Cukup dijalankan di laptop admin dengan XAMPP (Apache + MySQL).

## Yang perlu disiapkan

- Laptop Windows
- File folder **WEB SARPRAS** (lengkap)
- Cadangan database `.sql` (jika data sekolah sudah ada), **atau** file di folder `database/`
- Program **XAMPP**: https://www.apachefriends.org/

## 1. Instal XAMPP

1. Unduh dan instal XAMPP (pilih komponen Apache dan MySQL).
2. Buka **XAMPP Control Panel**.
3. Klik **Start** pada **Apache** dan **MySQL** (keduanya harus berwarna hijau).

Jika Apache gagal start, biasanya port 80 dipakai aplikasi lain. Bisa ganti port Apache ke `8080` di pengaturan XAMPP.

## 2. Salin folder web

1. Salin seluruh folder `WEB SARPRAS` ke:

   `C:\xampp\htdocs\WEB SARPRAS`

2. Pastikan ikut tersalin:
   - folder `assets\img\barang` (foto aset)
   - folder `libs\fpdf`
   - file `config\database.php`

## 3. Buat / isi database

Buka di browser:

- `http://localhost/phpmyadmin`
- atau jika Apache di port 8080: `http://localhost:8080/phpmyadmin`

### Cara A — pakai data yang sudah ada (disarankan saat serah terima)

1. Di phpMyAdmin, buat database baru bernama `sarpras` (utf8mb4).
2. Pilih database `sarpras` → tab **Import**.
3. Pilih file cadangan `.sql` yang diberikan pengembang.
4. Klik **Import**.

### Cara B — instal kosong (web baru, belum ada data)

1. Di phpMyAdmin, tab **Import**.
2. Jalankan dulu: `database/schema.sql`
3. Lalu jalankan: `database/alter_tambahan.sql`
4. Buka Command Prompt, jalankan:

```text
cd C:\xampp\htdocs\WEB SARPRAS
C:\xampp\php\php.exe database\seed_admin.php
```

## 4. Buka aplikasi

Di browser:

`http://localhost/WEB SARPRAS/login.php`

Jika Apache memakai port 8080:

`http://localhost:8080/WEB SARPRAS/login.php`

**Login**

| Isian    | Nilai             |
|----------|-------------------|
| Username | `SDN 1`                                      |
| Password | sesuai nilai `$passwordBaru` di `seed_admin.php` |

Sandi tidak disertakan di repositori. Isi sendiri `$passwordBaru` sebelum menjalankan `seed_admin.php`.

Untuk mengganti sandi di kemudian hari, buka `database/seed_admin.php`, ubah nilai `$passwordBaru` (dan `$usernameBaru` bila perlu), lalu jalankan:

```text
cd C:\xampp\htdocs\WEB SARPRAS
C:\xampp\php\php.exe database\seed_admin.php
```

Sandi tidak bisa diketik langsung di phpMyAdmin karena disimpan sebagai hash bcrypt.

Pengaturan database default ada di `config/database.php`:

- Host: `127.0.0.1`
- Database: `sarpras`
- User: `root`
- Password: *(kosong)*

Jika MySQL diberi password, ubah `$dbPass` di file itu.

## 5. Setiap kali memakai web

1. Buka XAMPP Control Panel.
2. Start **Apache** dan **MySQL**.
3. Buka alamat login di browser.
4. Setelah selesai, boleh Stop Apache dan MySQL.

Jika Apache/MySQL tidak dinyalakan, web tidak bisa dibuka.

## 6. Cadangan data (penting)

Karena tidak di-hosting, data hanya ada di laptop ini.

**Cadangkan setiap bulan (atau sebelum komputer diformat):**

1. Nyalakan MySQL.
2. Buka phpMyAdmin → pilih database `sarpras`.
3. Tab **Export** → **Go** → simpan file `.sql`.
4. Salin juga folder `C:\xampp\htdocs\WEB SARPRAS\assets\img\barang` (foto aset).
5. Simpan ke flashdisk atau Google Drive.

## 7. Jika pindah ke laptop lain

1. Instal XAMPP di laptop baru.
2. Salin folder `WEB SARPRAS` ke `htdocs`.
3. Buat database `sarpras` dan **Import** file cadangan `.sql`.
4. Salin folder foto `assets\img\barang`.

## 8. Masalah yang sering terjadi

| Gejala | Perbaikan |
|--------|-----------|
| Halaman tidak terbuka | Apache belum Start |
| “Koneksi database gagal” | MySQL belum Start, atau database `sarpras` belum diimpor |
| Foto aset tidak muncul | Folder `assets\img\barang` belum disalin |
| Apache tidak bisa Start | Port 80 dipakai Skype/IIS; ganti port ke 8080 |
| Login ditolak | Jalankan `database/seed_admin.php` untuk menyetel ulang akun admin |

## Kontak teknis

Untuk bantuan instalasi atau pemulihan cadangan, hubungi pengembang yang menyerahkan sistem ini.
