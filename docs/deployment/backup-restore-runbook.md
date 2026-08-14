# Backup And Restore Runbook

Runbook ini mencakup database MySQL dan file pegawai. Jalankan oleh operator berwenang. Jangan menaruh password pada argumen command, output CI, atau repository.

## Scope

Backup wajib mencakup:

1. Database MySQL lengkap, termasuk tabel `migrations`, users, employees, QR token, events, attendance, dan metadata dokumen.
2. `storage/app/private/` untuk foto dan dokumen pegawai.
3. `storage/app/public/` hanya selama masih ada file legacy yang belum diklasifikasi/migrasi.
4. Secret escrow terpisah untuk `APP_KEY` dan `EMPLOYEE_NIK_LOOKUP_KEY`.

Tidak perlu mencadangkan `vendor/`, `node_modules/`, `public/build/`, cache, session, queue sementara, atau log sebagai bagian restore aplikasi. Source dipulihkan dari commit/tag release.

## Provisional Policy

Sebelum pemilik sistem menetapkan kebijakan final, gunakan rekomendasi sementara:

- Database: backup harian dan sebelum setiap deployment.
- Private storage: backup harian dan sebelum setiap deployment.
- Retensi: 7 harian, 4 mingguan, dan 12 bulanan.
- Target sementara: RPO 24 jam, RTO 4 jam.
- Backup harus terenkripsi, memiliki checksum SHA-256, dan disalin ke lokasi terpisah/off-host.
- Restore drill minimal per kuartal dan sebelum go-live pertama.

RPO/RTO ini belum disetujui bisnis dan tidak boleh dianggap SLA final.

## Database Backup

Siapkan credential file di lokasi aman dengan permission hanya untuk operator, misalnya `/secure/mysql-backup.cnf`:

```ini
[client]
host=127.0.0.1
port=3306
user=backup_user
password=REDACTED
```

Backup konsisten untuk tabel InnoDB:

```bash
umask 077
STAMP=$(date +%Y%m%d-%H%M%S)
BACKUP_DIR=/secure/backups/yapista/$STAMP
mkdir -p "$BACKUP_DIR"

mysqldump \
  --defaults-extra-file=/secure/mysql-backup.cnf \
  --single-transaction \
  --routines \
  --triggers \
  --events \
  --hex-blob \
  --default-character-set=utf8mb4 \
  --no-tablespaces \
  "$DB_DATABASE" > "$BACKUP_DIR/database.sql"

sha256sum "$BACKUP_DIR/database.sql" > "$BACKUP_DIR/database.sql.sha256"
```

Validasi bahwa dump tidak kosong, checksum dapat diverifikasi, dan command berakhir dengan exit code 0. Jangan mencetak isi dump karena mengandung data pribadi.

## Private Storage Backup

Jalankan dari root release/shared storage yang benar:

```bash
tar --create --file "$BACKUP_DIR/private-storage.tar" -C storage/app private
sha256sum "$BACKUP_DIR/private-storage.tar" > "$BACKUP_DIR/private-storage.tar.sha256"
```

Selama legacy public employee files belum dibereskan:

```bash
tar --create --file "$BACKUP_DIR/legacy-public-employee-files.tar" -C storage/app/public employees
sha256sum "$BACKUP_DIR/legacy-public-employee-files.tar" > "$BACKUP_DIR/legacy-public-employee-files.tar.sha256"
```

Enkripsi archive menggunakan mekanisme organisasi sebelum upload off-host. Jangan memasukkan archive ke repository atau direktori `public/`.

## Isolated Restore Drill

Restore tidak boleh diuji pada database development/production aktif.

1. Buat database sementara dengan nama berprefix `yapista_restore_test_`.
2. Verifikasi checksum backup.
3. Restore dump ke database sementara.
4. Arahkan environment proses verifikasi hanya ke database sementara.
5. Jalankan `php artisan migrate:status` tanpa migration.
6. Bandingkan jumlah tabel dan count agregat tabel kritis dengan sumber backup.
7. Verifikasi decrypt NIK melalui `php artisan employee-security:verify-nik`; jangan tampilkan nilai NIK.
8. Extract private storage ke direktori sementara, bandingkan daftar file, ukuran, dan checksum.
9. Lakukan smoke test menggunakan data sintetis/non-pribadi.
10. Hapus database dan direktori sementara setelah hasil dicatat.

Contoh restore database:

```bash
sha256sum --check database.sql.sha256
mysql --defaults-extra-file=/secure/mysql-backup.cnf -e \
  'CREATE DATABASE yapista_restore_test_YYYYMMDD CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
mysql --defaults-extra-file=/secure/mysql-backup.cnf \
  yapista_restore_test_YYYYMMDD < database.sql
```

Gunakan account restore khusus dengan izin terbatas. Jangan menggunakan nama database aktif pada command restore.

## Production Restore

Production restore membutuhkan persetujuan incident commander/pemilik sistem:

1. Nyatakan maintenance window dan hentikan write traffic.
2. Ambil backup terakhir dari kondisi rusak sebagai bukti/rollback tambahan.
3. Pilih restore point berdasarkan timestamp dan checksum.
4. Restore database dan private storage dari snapshot yang sama atau paling dekat konsistensinya.
5. Deploy source release yang kompatibel dengan schema backup.
6. Jalankan cache clear/build cache, bukan seeder.
7. Jalankan migration hanya jika prosedur pemulihan memang memerlukan forward migration yang sudah diuji.
8. Jalankan verification command dan smoke test.
9. Buka traffic secara bertahap dan monitor error.

Jangan menjalankan `migrate:fresh`, `db:wipe`, rollback migration massal, atau seeder saat pemulihan.

## Evidence Template

Catat tanpa data sensitif:

- Timestamp dan operator.
- Release commit/tag.
- Backup ID dan lokasi logical (bukan credential/path rahasia).
- Database dump size dan SHA-256 verification result.
- File archive count/size dan SHA-256 verification result.
- Migration count/status.
- Aggregate record counts untuk tabel kritis.
- Smoke-test result.
- Cleanup confirmation untuk database/directory sementara.

## Stage 8 Drill Result

- Client `mysqldump` dan `mysql` tidak tersedia pada host audit, sehingga full database backup/restore drill belum dijalankan.
- Restore archive storage sintetis: **PASS**; 2 file diperiksa, 0 checksum mismatch, archive memiliki hash SHA-256 64 karakter, dan seluruh artifact sementara dihapus.
- Drill database dengan tool dan target infrastruktur produksi tetap menjadi blocker sebelum go-live.
