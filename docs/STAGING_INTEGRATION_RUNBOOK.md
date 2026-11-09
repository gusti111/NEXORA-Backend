# NEXORA — Staging Integration Runbook

Dokumen ini dipakai untuk menguji kontrak antara Flutter Mobile dan Laravel Backend. Phase 2 sudah terintegrasi ke branch `main` pada kedua repository; deploy commit `main` yang akan diuji dan catat SHA keduanya pada bukti pengujian.

## Status environment

Belum ada staging URL atau deployment workflow yang terdeteksi di repository ketika dokumen ini diperbarui. Karena itu, pengujian HTTP terhadap server nyata belum dijalankan. Gunakan environment staging terisolasi; jangan mengarahkan collection ini ke produksi.

## Local staging tanpa hosting (Windows)

Untuk pengujian awal, Laravel dapat berjalan langsung di PC Windows. Tidak diperlukan VPS, domain publik, atau layanan hosting.

### Prasyarat

- PHP 8.2+ dengan ekstensi `pdo_sqlite`, `fileinfo`, dan `gd` aktif.
- Composer 2.
- Repository NEXORA Backend sudah di-clone dan dependency Composer dapat diunduh.
- Flutter SDK dan Android SDK tersedia pada komputer yang menjalankan aplikasi.

### Jalankan backend lokal

Dari PowerShell pada folder repository Backend:

```powershell
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
.\scripts\start-local-staging.ps1
```

Script akan:
- Membuat `.env` dari `.env.example` hanya jika belum tersedia.
- Menolak berjalan jika `APP_ENV` bukan `local`, database bukan SQLite, atau ada `DB_URL` eksternal.
- Memastikan file SQLite lokal tersedia dan PHP extensions penting aktif.
- Memasang Composer dependencies bila `vendor/` belum ada.
- Membersihkan config cache, menjalankan migrasi tanpa menghapus database, serta menjalankan seeder dummy lokal.
- Menyalakan Laravel pada `0.0.0.0:8000`.

Script **tidak** menjalankan `migrate:fresh`, tidak menghapus database, dan tidak mengubah konfigurasi database yang sudah ada secara diam-diam. Jika file `.env` kamu memakai MySQL, script akan berhenti. Buat salinan kerja lokal dengan SQLite terlebih dahulu; jangan mengubah konfigurasi server bersama/produksi.

### Akun demo lokal

| Role | Email / login | Password |
| --- | --- | --- |
| Mahasiswa | `student@nexora.local` | `NexoraLocal!2026` |
| Dosen | `lecturer@nexora.local` | `NexoraLocal!2026` |
| Korlap | `coordinator@nexora.local` | `NexoraLocal!2026` |

Seeder membuat satu PKL aktif yang menghubungkan mahasiswa, dosen, dan korlap. Seeder boleh dijalankan ulang dan dibatasi pada `APP_ENV=local` atau `testing`. Credential ini bersifat publik/dummy: jangan gunakan pada server publik, staging bersama, atau produksi.

### URL koneksi

- Health check di PC: `http://127.0.0.1:8000/up`
- Postman di PC: `http://127.0.0.1:8000/api`
- Android Emulator (AVD): `http://10.0.2.2:8000/api`
- Android fisik: gunakan IP private PC yang ditampilkan oleh script, misalnya `http://192.168.1.20:8000/api` (ganti dengan IP aktual).

PC dan HP harus berada pada jaringan Wi-Fi yang sama. Jika Windows Firewall meminta izin, izinkan PHP pada **Private networks saja**; jangan mematikan firewall secara global dan jangan membuka port ini ke jaringan publik. Server PHP built-in ini hanya untuk pengembangan lokal.

Jalankan Flutter dari repository Mobile dengan URL yang sesuai:

```powershell
# Android Emulator
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api

# Android fisik — ganti dengan IPv4 private PC yang benar
flutter run --dart-define=API_BASE_URL=http://192.168.1.20:8000/api
```

Pastikan build yang dipakai adalah debug build. Release manifest tidak mengaktifkan cleartext HTTP; deployment publik wajib memakai HTTPS.

### Jalankan Postman pada local staging

Import collection lalu ubah `baseUrl` pada environment privat menjadi:

```text
http://127.0.0.1:8000/api
```

Isi kredensial mahasiswa/dosen dummy di atas. Set `activityDate` ke tanggal hari ini dan `idempotencyKey` ke UUID baru sebelum setiap sesi. Jalankan request 01–12 secara berurutan.

## 1. Siapkan backend staging

1. Deploy branch `main` dari [NEXORA Backend](https://github.com/gusti111/NEXORA-Backend) ke staging. Jangan deploy branch fitur Phase 2 yang sudah selesai di-merge.
2. Gunakan HTTPS, `APP_ENV=staging` (atau konfigurasi production-like yang disetujui), dan `APP_DEBUG=false`.
3. Konfigurasikan database staging khusus dan backup sebelum migrasi.
4. Pastikan private local storage tersedia dan tidak diekspos melalui web server.
5. Jalankan:
   ```bash
   php artisan down --render="errors::503"
   php artisan migrate --force
   php artisan optimize:clear
   php artisan up
   ```
   Sesuaikan maintenance procedure dengan platform deployment. Jangan menjalankan migrasi pada database produksi untuk tes ini.
6. Pastikan endpoint health Laravel `/up` merespons HTTP 200 dan API memakai prefix `/api`.

## 2. Siapkan akun dan data dummy

Gunakan data dummy khusus staging:
- satu akun mahasiswa aktif;
- satu dosen aktif yang ditugaskan pada penempatan PKL tersebut;
- satu penempatan PKL berstatus `berlangsung`;
- tanggal kegiatan di dalam rentang penempatan dan tidak melewati hari ini.

Jangan memakai data mahasiswa, dosen, perusahaan, foto, token, atau password produksi. Pastikan akun dosen yang dipakai memang dosen pada PKL mahasiswa.

## 3. Jalankan Postman collection

Import `postman/NEXORA-Phase-2-Staging.postman_collection.json`, lalu buat environment privat dengan variabel:
- `baseUrl`: URL staging lengkap dengan suffix `/api`, misalnya `https://staging.example.test/api`;
- `studentLogin` dan `studentPassword`;
- `lecturerLogin` dan `lecturerPassword`;
- `idempotencyKey`: UUID baru untuk setiap sesi pengujian;
- `activityDate`: tanggal yang valid di periode PKL.

Jalankan request berurutan 01–12. Collection akan menyimpan token, PKL ID, logbook ID, dan notification ID di environment lokal Postman. Jangan commit environment export karena berisi credential/token.

Ekspektasi penting:
- submit pertama: HTTP 201;
- replay key yang sama pada PKL yang sama: HTTP 200 dan `duplicate: true`, dengan ID logbook identik;
- dosen menerima event `logbook_submitted`;
- review dosen: HTTP 200;
- mahasiswa menerima event `logbook_reviewed`;
- mark-as-read hanya mengubah notifikasi milik akun yang sedang digunakan.

## 4. Uji aplikasi Android

Build APK debug dengan base URL staging:
```bash
flutter build apk --debug --dart-define=API_BASE_URL=https://STAGING-HOST/api
```

Install pada perangkat Android fisik. Pastikan perangkat dapat menjangkau host staging melalui HTTPS. Login sebagai akun mahasiswa dummy.

Skenario:
1. Buka dashboard dan pastikan PKL aktif tampil.
2. Matikan koneksi data/Wi-Fi.
3. Buat logbook dummy dengan foto JPG/PNG/WEBP dan lokasi dummy yang wajar.
4. Pastikan aplikasi menampilkan status menunggu sinkronisasi dan item tetap ada setelah aplikasi ditutup paksa lalu dibuka kembali.
5. Aktifkan koneksi kembali dan buka aplikasi atau tekan sinkronisasi manual.
6. Pastikan draft berpindah dari antrean lokal ke daftar logbook server.
7. Verifikasi di database/API bahwa hanya ada satu record dengan submission tersebut; replay tidak membuat duplikat.
8. Login sebagai dosen yang ditugaskan, buka inbox, review logbook.
9. Login kembali sebagai mahasiswa, pastikan notifikasi hasil review terlihat.
10. Uji foto melalui endpoint privat dan pastikan akun lain yang tidak terkait menerima 404.

Jangan memakai data atau foto orang sungguhan untuk tes ini.

## 5. Negative / reliability checks

- Token tidak valid: endpoint privat mengembalikan 401 dan mobile meminta login ulang.
- Akun nonaktif: sesi harus ditolak sesuai kontrak middleware.
- PKL bukan milik mahasiswa: submit harus ditolak.
- Tanggal di luar periode PKL: submit ditolak HTTP 422.
- Idempotency key sama untuk PKL berbeda: HTTP 409.
- Request replay setelah response pertama hilang: hanya satu logbook dan satu notifikasi dosen.
- Notification write gagal: transaksi submit/review harus rollback secara utuh.
- Network/5xx failure: draft dan foto lokal tetap ada.
- 4xx permanen: draft masuk daftar gagal, tidak dicoba ulang tanpa perubahan.

## 6. Bukti yang harus disimpan

Sebelum menyatakan staging integration passed, simpan:
- commit SHA backend dan mobile yang diuji;
- versi APK/build number;
- environment staging (host saja, tanpa secret);
- Postman run report yang sudah menghapus token dan password;
- jumlah logbook sebelum/sesudah replay;
- tangkapan layar status offline → pending → synced;
- bukti dosen menerima notifikasi dan mahasiswa menerima hasil review;
- hasil akses foto oleh pemilik dan penolakan akses oleh akun tak terkait;
- log backend yang sudah disanitasi.

## Exit criteria

Integration test hanya boleh diberi status **PASS** jika semua skenario utama dan negatif di atas berhasil pada staging, serta tidak ada duplikasi logbook, kehilangan foto, kebocoran lintas akun, atau perbedaan status review/notifikasi.
