# Panduan Copywriting YAPISTA HRIS

## Nada Bahasa

Gunakan Bahasa Indonesia yang profesional, ramah, jelas, ringkas, dan berorientasi pada tugas. Hindari bahasa promosi, kalimat panjang, serta istilah teknis yang tidak membantu pengguna.

## Terminologi Utama

| Konsep | Gunakan | Hindari |
| --- | --- | --- |
| Pengguna organisasi | Pegawai | Employee, karyawan (kecuali berbeda secara bisnis) |
| Nomor pegawai | NUP | NUP / Nomor Pegawai |
| Organisasi | Unit Kerja, Jabatan | Institution, Position |
| Agenda | Kegiatan | Event, acara |
| Pencatatan hadir | Kehadiran | Absensi |
| Pemindaian | Scan Kehadiran, QR Code | Payload, token, resolver |
| Pemeriksaan data | Verifikasi, Peninjauan | Verified, Review |
| Akun | Masuk, Keluar, Informasi Akun | Login, Logout, Informasi Login |

`ID Card`, `QR Code`, `Import`, `Export`, `Excel`, dan `Password` dipertahankan karena merupakan istilah produk atau istilah operasional yang sudah digunakan secara resmi. Nilai internal seperti `verified`, `submitted`, `rejected`, `qr`, `manual`, dan `barcode` tidak diubah; tampilkan label Bahasa Indonesia pada antarmuka.

## Tombol

Gunakan kata kerja yang menjelaskan hasil: `Simpan`, `Simpan Perubahan`, `Tambah Pegawai`, `Kirim untuk Verifikasi`, `Unduh`, `Lihat`, `Edit`, `Hapus`, `Batal`, dan `Reset Filter`. Gunakan `Import Data` untuk memasukkan data dan `Export Data` untuk menghasilkan file laporan.

## Pesan Hasil

- Berhasil: `<objek> berhasil <tindakan>.`, misalnya `Kehadiran berhasil dicatat.`
- Gagal: jelaskan masalah dan langkah yang dapat dilakukan tanpa menampilkan detail teknis.
- Konfirmasi: sebutkan tindakan dan dampaknya secara singkat; tombol harus menggunakan nama tindakan.

Jangan menampilkan SQL, exception, path privat, NIK, raw QR token, hash, atau identifier internal dalam pesan pengguna.

## Empty State

- Data belum tersedia: `Belum ada data pegawai.`
- Koleksi kosong: `Belum ada data pendidikan.`
- Hasil filter kosong: `Tidak ada pegawai yang cocok dengan pencarian atau filter saat ini.`

Gunakan tanda titik untuk kalimat lengkap. Tampilkan aksi hanya jika memang tersedia pada halaman tersebut.

## Kehadiran dan Scanner

Gunakan `Total Peserta`, `Sudah Hadir`, `Belum Hadir`, `Tingkat Kehadiran`, `Daftar Kehadiran`, dan `Kehadiran Manual`. Instruksi scanner harus singkat: `Arahkan scanner ke QR Code pada ID Card pegawai.` Jangan menampilkan payload atau raw token.

## Pegawai dan Verifikasi

Gunakan `Data Pegawai`, `Verifikasi Pegawai`, `Terverifikasi`, `Belum Diverifikasi`, dan `Kelengkapan Profil`. Profil yang belum lengkap tidak boleh disebut sebagai pegawai tidak resmi. Gunakan `Peninjauan` untuk pemeriksaan profil dan `Disetujui` untuk status dokumen yang valid.

## Kapitalisasi

Gunakan kapitalisasi judul secara konsisten: `Data Pegawai`, `Unit Kerja`, dan `Status Verifikasi`. Hindari huruf kapital penuh pada literal; styling visual diserahkan kepada CSS. Helper text ditulis sebagai satu kalimat ringkas.

## Pola Audit v1.0.0

| Area | Sebelum | Masalah | Standar |
| --- | --- | --- | --- |
| Kehadiran | Absensi, Daftar Hadir, Waktu Scan | Istilah bercampur | Kehadiran, Daftar Kehadiran, Waktu Kehadiran |
| Pegawai | NUP / Nomor Pegawai | Duplikasi konsep | NUP |
| Akun | Login, Logout, Informasi Login | Bahasa bercampur | Masuk, Keluar, Informasi Akun |
| Dokumen | Upload, Download, Rejected | Jargon dan status mentah | Unggah, Unduh, Perlu Diperbaiki |
| Kegiatan | Generate Ulang Peserta | Jargon teknis | Buat Ulang Peserta |
| Profil | Review, Progress | Bahasa bercampur | Peninjauan, Kelengkapan |
| Filter | Reset semua | Tidak konsisten | Reset Filter |
| Import | Download Template | Bahasa bercampur | Unduh Template |

Area audit ini mencakup autentikasi, dashboard, master data, pegawai, verifikasi, undangan, profil, ID Card, dokumen, kegiatan, peserta, kehadiran, scanner, import/export, laporan, akun/password, navigasi, konfirmasi, empty state, dan halaman error.
