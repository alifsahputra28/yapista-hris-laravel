<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventAttendance extends Model
{
    use HasFactory;

    public const ATTENDANCE_STATUSES = [
        'present' => 'Hadir',
        'late' => 'Terlambat',
        'manual' => 'Manual',
        'invalid' => 'Tidak Valid',
    ];

    public const SCAN_METHODS = [
        'barcode' => 'Barcode',
        'qr' => 'QR Code',
        'qr_code' => 'QR Code',
        'manual' => 'Manual',
    ];

    protected $fillable = [
        'event_id',
        'employee_id',
        'qr_token_id',
        'scanned_by',
        'scanned_at',
        'attendance_status',
        'scan_method',
        'note',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scanned_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function qrToken(): BelongsTo
    {
        return $this->belongsTo(EmployeeQrToken::class, 'qr_token_id');
    }

    public function scanner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scanned_by');
    }

    public function isPresent(): bool
    {
        return $this->attendance_status === 'present';
    }

    public function isLate(): bool
    {
        return $this->attendance_status === 'late';
    }

    public function isManual(): bool
    {
        return $this->scan_method === 'manual' || $this->attendance_status === 'manual';
    }

    public function isQrCode(): bool
    {
        return in_array($this->scan_method, ['qr', 'qr_code'], true);
    }

    public function isBarcode(): bool
    {
        return $this->scan_method === 'barcode';
    }

    public function getAttendanceStatusLabelAttribute(): string
    {
        return self::ATTENDANCE_STATUSES[$this->attendance_status] ?? $this->attendance_status;
    }

    public function getScanMethodLabelAttribute(): string
    {
        return self::SCAN_METHODS[$this->scan_method] ?? $this->scan_method;
    }
}
