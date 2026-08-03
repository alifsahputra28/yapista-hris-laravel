<?php

namespace App\Support\Attendances;

use App\Models\EventAttendance;

class AttendanceResult
{
    public const SUCCESS = 'success';

    public const ALREADY_ATTENDED = 'already_attended';

    public const REJECTED = 'rejected';

    private function __construct(
        public readonly string $status,
        public readonly string $message,
        public readonly ?EventAttendance $attendance = null,
    ) {}

    public static function success(EventAttendance $attendance): self
    {
        return new self(self::SUCCESS, 'Absensi berhasil dicatat.', $attendance);
    }

    public static function alreadyAttended(EventAttendance $attendance, string $message): self
    {
        return new self(self::ALREADY_ATTENDED, $message, $attendance);
    }

    public static function rejected(string $message): self
    {
        return new self(self::REJECTED, $message);
    }

    public function isSuccess(): bool
    {
        return $this->status === self::SUCCESS;
    }

    public function isAlreadyAttended(): bool
    {
        return $this->status === self::ALREADY_ATTENDED;
    }
}
