# YAPISTA HRIS Production Environment

Dokumen ini mendefinisikan prasyarat minimum untuk men-deploy release candidate source `ae40647d9dbcc6a43f5e3460813b786bef5032ac` beserta lockfile yang telah lulus remediasi dan gate final. Git tag belum dibuat dan production deployment tetap memerlukan approval operator terpisah. Nilai rahasia tidak boleh disimpan di repository, ticket, log, atau command history.

## Runtime

| Komponen | Baseline teruji | Kebutuhan produksi |
|---|---:|---|
| PHP | 8.3.16 | PHP 8.3+ 64-bit |
| Laravel | 13.25.0 | Gunakan versi pada `composer.lock` |
| Composer | 2.10.2 | Composer 2.10.2 atau lebih baru, hanya saat build/release |
| Node.js | 22.13.1 | Node 22 LTS, hanya diperlukan saat build asset |
| npm | 10.9.2 | Gunakan `npm ci` berdasarkan `package-lock.json` |
| Database | MySQL 8.4.3 | MySQL 8.x, InnoDB, `utf8mb4` |
| Web server | Belum dipilih | Nginx atau Apache dengan document root ke `public/` |

Ekstensi PHP yang telah lolos `composer check-platform-reqs --no-dev`: `ctype`, `dom`, `fileinfo`, `filter`, `gd`, `hash`, `iconv`, `json`, `libxml`, `mbstring`, `openssl`, `pcre`, `session`, `simplexml`, `tokenizer`, `xml`, `xmlreader`, `xmlwriter`, `zip`, dan `zlib`. Driver produksi juga memerlukan `pdo_mysql`; `intl`, `curl`, dan `bcmath` tersedia pada baseline dan sebaiknya dipertahankan.

## Environment Variables

Nilai berikut wajib ditetapkan pada secret/environment store produksi. Jangan menyalin `.env` development.

```dotenv
APP_NAME="YAPISTA HRIS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://hris.example.org
APP_TIMEZONE=Asia/Jakarta

LOG_CHANNEL=stack
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=...
DB_PORT=3306
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
CACHE_STORE=database
QUEUE_CONNECTION=database

FILESYSTEM_DISK=local

MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=...
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=...
MAIL_FROM_NAME="YAPISTA HRIS"

EMPLOYEE_NIK_LOOKUP_KEY=...
```

`APP_KEY` dan `EMPLOYEE_NIK_LOOKUP_KEY` wajib berupa dua secret berbeda, stabil, dan dicadangkan melalui secret escrow. Jangan mengganti salah satunya setelah data terenkripsi/blind index dipakai tanpa prosedur rotasi khusus. `APP_PREVIOUS_KEYS` hanya digunakan dalam rotasi yang direncanakan.

`EMPLOYEE_SEED_DEFAULT_PASSWORD` tidak dibutuhkan untuk runtime normal dan harus kosong kecuali ada prosedur provisioning yang disetujui. Seeder tidak dijalankan saat deployment produksi.

## Web And TLS

- DNS, domain final, dan sertifikat TLS valid harus tersedia sebelum UAT eksternal atau go-live.
- Paksa HTTPS di reverse proxy/web server dan teruskan header proxy secara benar.
- Document root harus menunjuk ke `<release>/public`, bukan root repository.
- Nonaktifkan directory listing; `public/.htaccess` sudah menetapkan `Options -Indexes` untuk Apache.
- Batasi ukuran request sesuai batas upload aplikasi dan tolak executable/script uploads di web server.
- Jangan log query string sensitif. Route undangan bertoken bertanda tangan perlu redaksi access log.

## Filesystem And Permissions

- User web server memerlukan read pada source dan write hanya pada `storage/` serta `bootstrap/cache/`.
- Jangan gunakan permission `0777`; gunakan ownership service account dan permission minimum.
- `storage/app/private` berisi foto/dokumen sensitif dan tidak boleh berada di document root atau symlink publik.
- `public/storage` hanya boleh mengarah ke `storage/app/public`. Sebelum go-live, pastikan tidak ada artefak pegawai sensitif/legacy di disk public.
- `public/build` adalah artifact build yang dapat dibuat ulang dan bukan backup data bisnis.

## Stateful Services

- Session dan cache menggunakan tabel database pada baseline; tabel terkait harus sudah termigrasi.
- Queue dikonfigurasi ke database, tetapi audit tidak menemukan job aplikasi aktif. Worker belum menjadi dependency runtime saat ini. Tambahkan supervisor/worker bila kelak ada job `ShouldQueue`.
- Scheduler aplikasi belum memiliki task bisnis. Cron `schedule:run` belum wajib, tetapi evaluasi ulang setiap release.
- Password reset memakai mail transport. `MAIL_MAILER=log` tidak boleh digunakan di produksi.

## Release Build

Build release dilakukan di CI/build host, bukan dengan dependency development pada web root:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm ci
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

`composer audit --locked --no-dev` dan `npm audit --omit=dev` wajib bernilai lulus sesuai risk policy sebelum artifact dipromosikan. Lockfile hasil Tahap 9.5 lulus kedua audit; CI/release harus mengulang pemeriksaan pada build yang bersih.

## Production Decisions Still Required

- Hosting, domain, TLS termination, dan service account final.
- SMTP/provider email dan pengujian password reset.
- Lokasi backup terenkripsi yang terpisah dari server aplikasi.
- RPO/RTO yang disetujui pemilik bisnis.
- Monitoring uptime, error, kapasitas disk, serta database.
- Kebijakan retensi log dan backup sesuai regulasi yayasan.
