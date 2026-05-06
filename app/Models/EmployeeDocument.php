<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeDocument extends Model
{
    use HasFactory;

    public const DOCUMENT_TYPES = [
        'ktp' => 'KTP',
        'kk' => 'Kartu Keluarga',
        'ijazah' => 'Ijazah',
        'sk_kontrak' => 'SK/Kontrak',
        'sertifikat' => 'Sertifikat Pendukung',
    ];

    protected $fillable = [
        'employee_id',
        'document_type',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
        'status',
        'note',
        'uploaded_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isValid(): bool
    {
        return $this->status === 'valid';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function getDocumentTypeLabelAttribute(): string
    {
        return self::DOCUMENT_TYPES[$this->document_type] ?? $this->document_type;
    }
}
