# Portfolio Bayu Satria — PHP + MySQL + Multi Photo Gallery

Versi ini mengganti `localStorage` dengan backend PHP + MySQL.

## Fitur
- Admin login password `110724`.
- Tambah project dari HP/laptop.
- Upload banyak foto sekaligus (maks. 30 foto/project, 10 MB/foto).
- Foto tersimpan di server.
- Edit project dan tambah foto baru.
- Hapus foto individual (minimal tersisa 1 foto).
- Hapus project beserta seluruh fotonya.
- Halaman project menampilkan gallery banyak foto.
- Semua pengunjung melihat data yang sama dari database.

## Jalankan di XAMPP
1. Copy folder `portfolio-bayu` ke `C:/xampp/htdocs/`.
2. Buka XAMPP, nyalakan **Apache** dan **MySQL**.
3. Buka `http://localhost/phpmyadmin`.
4. Import file `database.sql`.
5. Pastikan `api/config.php` sesuai dengan MySQL kamu. Default XAMPP:
   - host: `127.0.0.1`
   - database: `bayu_portfolio`
   - user: `root`
   - password: kosong
6. Buka `http://localhost/portfolio-bayu/`.
7. Admin: `http://localhost/portfolio-bayu/admin.html`.

## Deploy online
Upload seluruh folder ke hosting yang mendukung PHP 8+ dan MySQL/MariaDB. Buat database dari cPanel/hosting, import `database.sql`, lalu ubah `DB_NAME`, `DB_USER`, `DB_PASS`, dan `DB_HOST` di `api/config.php`.

Pastikan folder `uploads/projects` dapat ditulis oleh PHP (permission umumnya 755/775 tergantung hosting).

## Catatan keamanan
Password admin diverifikasi di server menggunakan `password_verify()`, bukan dibandingkan di JavaScript. Untuk website publik yang serius, tambahkan HTTPS, CSRF protection, rate limiting, dan user table dengan password hash per akun.
