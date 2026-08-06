<?php

namespace App\Models;

use App\Support\Documents\EmployeeDocumentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeDocument extends Model
{
    use HasFactory;

    public const DOCUMENT_TYPES = EmployeeDocumentType::ALL;

    protected $fillable = [
        'employee_id',
        'employee_education_id',
        'employee_certification_id',
        'document_type',
        'document_slot',
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

    public function employeeEducation(): BelongsTo
    {
        return $this->belongsTo(EmployeeEducation::class);
    }

    public function employeeCertification(): BelongsTo
    {
        return $this->belongsTo(EmployeeCertification::class);
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
        return EmployeeDocumentType::label($this->document_type);
    }
}
