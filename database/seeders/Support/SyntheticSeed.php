<?php

namespace Database\Seeders\Support;

use App\Models\Employee;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;

final class SyntheticSeed
{
    /** @param list<string> $environments */
    public static function guard(array $environments = ['local', 'testing', 'staging', 'uat']): void
    {
        if (! app()->environment($environments)) {
            throw new RuntimeException('Synthetic seeder refused: environment is not allowed.');
        }
    }

    public static function password(?string $supplied = null): string
    {
        self::guard();
        $password = $supplied ?? config('seeding.uat_password');

        if (! is_string($password) || strlen(trim($password)) < 12) {
            throw new RuntimeException('Set UAT_SEED_PASSWORD securely (at least 12 characters) before creating synthetic accounts.');
        }

        return $password;
    }

    public static function user(string $email, string $role): ?User
    {
        $user = User::where('email', $email)->first();
        if ($user && $user->role !== $role) {
            throw new RuntimeException('Synthetic account collision; existing account was not changed.');
        }

        return $user;
    }

    /** Limit demo side effects to the exact account/NUP pairs in the fixture file. */
    public static function employees(): Builder
    {
        $rows = require database_path('seeders/data/employees.php');

        return Employee::query()->where(function (Builder $query) use ($rows): void {
            foreach ($rows as $row) {
                $query->orWhere(function (Builder $fixture) use ($row): void {
                    $fixture->where('employee_number', $row['employee_number'])
                        ->whereHas('user', fn (Builder $user) => $user
                            ->where('email', $row['login_email'])->where('role', 'pegawai'));
                });
            }
        });
    }

    public static function developmentEvent(string $name): ?Event
    {
        $event = Event::where('name', '[DEV] '.$name)->first();
        if ($event && ! str_starts_with((string) $event->description, '[DEV FIXTURE] ')) {
            throw new RuntimeException('Development event collision; existing event was not changed.');
        }

        return $event;
    }
}
