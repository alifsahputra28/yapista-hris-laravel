<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>ID Card {{ $employee->employee_number }}</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            color: #111827;
        }

        .card {
            width: 320px;
            border: 1px solid #d1d5db;
            border-radius: 12px;
            overflow: hidden;
        }

        .header {
            padding: 14px;
            color: #ffffff;
            background: #1d4ed8;
            font-weight: 700;
        }

        .subtitle {
            font-size: 11px;
            letter-spacing: 1px;
        }

        .body {
            padding: 16px;
            text-align: center;
        }

        .name {
            margin-top: 8px;
            font-size: 18px;
            font-weight: 700;
        }

        .meta {
            margin-top: 6px;
            font-size: 12px;
        }

        .qr-code {
            padding: 14px;
            text-align: center;
            border-top: 1px dashed #cbd5e1;
            background: #f8fafc;
        }

        .qr-code svg {
            width: 145px;
            height: 145px;
            padding: 5px;
            background: #ffffff;
        }

        .number {
            margin-top: 6px;
            font-weight: 700;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            YAPISTA
            <div class="subtitle">KARTU PEGAWAI</div>
        </div>

        <div class="body">
            <div class="name">{{ $employee->full_name }}</div>
            <div class="meta">NUP / Nomor Pegawai: {{ $employee->employee_number }}</div>
            <div class="meta">Unit Kerja: {{ $employee->institution?->name ?? '-' }}</div>
            <div class="meta">Jabatan: {{ $employee->position?->name ?? '-' }}</div>
            <div class="meta">Status: Terverifikasi</div>
        </div>

        <div class="qr-code">
            @if ($qrCodeSvg)
                {!! $qrCodeSvg !!}
            @endif
            <div class="meta">Pindai QR Code untuk absensi kegiatan.</div>
        </div>
    </div>
</body>
</html>
