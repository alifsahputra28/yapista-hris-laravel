# YAPISTA HRIS Post-v1 Backlog

Dokumen ini memisahkan fitur/peningkatan dari bug UAT. Item tidak diimplementasikan selama Tahap 9 tanpa perubahan scope resmi.

## Deferred Existing Items

| ID | Request | Role | Reason | Priority Suggestion |
|---|---|---|---|---|
| BL-001 | Download PDF E-Card | Pegawai, HR/Admin | Route/action historis belum menjadi fitur final; browser E-Card tetap tersedia | Medium |
| BL-002 | Self-host Public Sans dan evaluasi CSP ketat | Semua | Mengurangi external font request dan memungkinkan CSP lebih ketat; memerlukan audit asset/inline script | Medium |
| BL-003 | Optimasi conflict lookup import untuk volume di atas batas saat ini | HR/Admin | Import saat ini dibatasi 1.000 row dan belum terbukti lambat | Low |
| BL-004 | Streaming/chunk writer untuk export XLSX sangat besar | HR/Admin | Belum ada bukti volume export saat ini melebihi kemampuan in-memory writer | Low |
| BL-005 | Cleanup asset Mantis dan legacy presentation yang tidak aktif | Developer/Operations | Perlu inventory referensi dinamis agar penghapusan tidak merusak UI | Low |

## New Requests From UAT

Belum ada request baru karena human UAT belum dimulai. Gunakan ID berikutnya `BL-006`.

| ID | Request | Role | Reason | Priority Suggestion |
|---|---|---|---|---|
| _Belum ada_ |  |  |  |  |

Request seperti WhatsApp notification, laporan baru, absensi harian, payroll, GPS, atau perubahan workflow diklasifikasikan sebagai requirement baru, bukan bug pada release ini.
