<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Event;
use App\Models\Institution;
use App\Models\Position;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DevelopmentSeeder;
use Database\Seeders\EmployeeDocumentSeeder;
use Database\Seeders\EmployeeInvitationSeeder;
use Database\Seeders\EmployeeQrTokenSeeder;
use Database\Seeders\EmployeeSeeder;
use Database\Seeders\EventAttendanceSeeder;
use Database\Seeders\EventParticipantSeeder;
use Database\Seeders\EventSeeder;
use Database\Seeders\InitialUserSeeder;
use Database\Seeders\UatSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class SeederEnvironmentSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function seed($class = DatabaseSeeder::class)
    {
        // Exercise --force as well: production refusal must come from the seeder,
        // not only Laravel's interactive confirmation. All writes stay in SQLite.
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $this->artisan('db:seed', ['--class' => $class, '--force' => true, '--no-interaction' => true]);

        return $this;
    }

    public function test_production_default_seed_is_master_only_and_preserves_operator_edits(): void
    {
        $this->app->instance('env', 'production');
        config(['seeding.uat_password' => null]);
        $this->seed(DatabaseSeeder::class);
        $institution = Institution::firstOrFail();
        $position = Position::firstOrFail();
        $institution->update(['status' => 'inactive', 'level' => 'Operator']);
        $position->update(['status' => 'inactive', 'type' => 'teknis']);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(7, Institution::count());
        $this->assertSame(37, Position::count());
        $this->assertSame('inactive', $institution->fresh()->status);
        $this->assertSame('Operator', $institution->fresh()->level);
        $this->assertSame('inactive', $position->fresh()->status);
        $this->assertSame('teknis', $position->fresh()->type);
        foreach (['users', 'employees', 'events', 'event_participants', 'event_attendances',
            'employee_documents', 'employee_invitations', 'employee_qr_tokens'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
        $this->assertSame(0, Position::whereDoesntHave('institution')->count());
    }

    public function test_all_synthetic_entry_points_refuse_production_before_writing(): void
    {
        config(['seeding.uat_password' => 'test-only-seed-password']);
        $this->app->instance('env', 'production');
        foreach ([UatSeeder::class, DevelopmentSeeder::class, UserSeeder::class, InitialUserSeeder::class,
            EmployeeSeeder::class, EmployeeQrTokenSeeder::class, EmployeeInvitationSeeder::class,
            EmployeeDocumentSeeder::class, EventSeeder::class, EventParticipantSeeder::class,
            EventAttendanceSeeder::class] as $seeder) {
            try {
                $this->seed($seeder);
                $this->fail($seeder.' must refuse production.');
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString('environment is not allowed', $exception->getMessage());
            }
        }
        try {
            app(EmployeeSeeder::class)->seedRows([]);
            $this->fail('Public seedRows must also refuse production.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('environment is not allowed', $exception->getMessage());
        }
        foreach (['users', 'employees', 'institutions', 'positions', 'events', 'employee_qr_tokens'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
    }

    public function test_uat_seed_works_in_testing_staging_and_uat_without_duplicates_or_secret_rotation(): void
    {
        config(['seeding.uat_password' => 'test-only-seed-password']);
        $this->seed(UatSeeder::class);
        $passwords = User::pluck('password', 'id')->all();
        $qrIds = DB::table('employee_qr_tokens')->pluck('id')->all();
        $admin = User::where('role', 'super_admin')->firstOrFail();
        $admin->update(['status' => 'inactive']);
        $event = Event::firstOrFail();
        $event->update(['status' => 'closed']);
        DB::table('event_participants')->where('id', DB::table('event_participants')->min('id'))
            ->update(['participant_status' => 'cancelled']);

        config(['seeding.uat_password' => null]);
        foreach (['staging', 'uat'] as $environment) {
            $this->app->instance('env', $environment);
            $this->seed(UatSeeder::class);
        }

        $this->assertDatabaseCount('users', 16);
        $this->assertDatabaseCount('employees', 13);
        $this->assertDatabaseCount('events', 1);
        $this->assertDatabaseCount('event_participants', 11);
        $this->assertDatabaseCount('event_attendances', 0);
        $this->assertDatabaseCount('employee_documents', 0);
        $this->assertDatabaseCount('employee_invitations', 0);
        $this->assertSame($passwords, User::pluck('password', 'id')->all());
        $this->assertSame($qrIds, DB::table('employee_qr_tokens')->pluck('id')->all());
        $this->assertSame('inactive', $admin->fresh()->status);
        $this->assertSame('closed', $event->fresh()->status);
        $this->assertSame(1, DB::table('event_participants')->where('participant_status', 'cancelled')->count());
        $this->assertSame(['hr_admin', 'panitia', 'pegawai', 'super_admin'], User::distinct()->orderBy('role')->pluck('role')->all());
        $this->assertSame(12, Employee::where('verification_status', 'verified')->count());
        $new = Employee::whereNull('employee_number')->firstOrFail();
        $this->assertSame('draft', $new->verification_status);
        $this->assertFalse($new->qrTokens()->exists());
        $this->assertSame(0, DB::table('employees as e')->join('positions as p', 'p.id', '=', 'e.position_id')
            ->whereColumn('e.institution_id', '!=', 'p.institution_id')->count());
        $this->assertSame(0, DB::table('employee_qr_tokens')->select('employee_id')->where('is_active', true)
            ->whereNull('revoked_at')->groupBy('employee_id')->havingRaw('COUNT(*) > 1')->get()->count());
        $this->assertSame(0, DB::table('event_participants')->select('event_id', 'employee_id')
            ->groupBy('event_id', 'employee_id')->havingRaw('COUNT(*) > 1')->get()->count());
    }

    public function test_missing_or_weak_uat_secret_refuses_without_partial_dataset(): void
    {
        foreach ([null, '', 'short'] as $password) {
            config(['seeding.uat_password' => $password]);
            try {
                $this->seed(UatSeeder::class);
                $this->fail('Missing/weak synthetic secret must be refused.');
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString('UAT_SEED_PASSWORD', $exception->getMessage());
            }
            foreach (['users', 'institutions', 'positions', 'employees', 'events'] as $table) {
                $this->assertDatabaseCount($table, 0);
            }
        }
    }

    public function test_unknown_environments_and_staging_development_components_are_refused(): void
    {
        foreach (['production', 'prod', 'preview'] as $environment) {
            $this->app->instance('env', $environment);
            try {
                $this->seed(UatSeeder::class);
                $this->fail('Unapproved environment must be refused.');
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString('environment is not allowed', $exception->getMessage());
            }
        }
        $this->app->instance('env', 'staging');
        foreach ([DevelopmentSeeder::class, EventSeeder::class, EventParticipantSeeder::class,
            EventAttendanceSeeder::class, EmployeeInvitationSeeder::class, EmployeeDocumentSeeder::class] as $seeder) {
            try {
                $this->seed($seeder);
                $this->fail('Development components must refuse staging.');
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString('environment is not allowed', $exception->getMessage());
            }
        }
        $this->assertDatabaseCount('users', 0);
    }

    public function test_uat_event_collision_rolls_back_new_seed_data_and_preserves_unknown_event(): void
    {
        config(['seeding.uat_password' => 'test-only-seed-password']);
        $event = Event::create(['name' => 'UAT Kegiatan Internal 001', 'event_date' => today(),
            'status' => 'draft', 'target_type' => 'all', 'description' => 'Operator-owned record']);
        try {
            $this->seed(UatSeeder::class);
            $this->fail('Event collision must be refused.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('event collision', $exception->getMessage());
        }
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('employees', 0);
        $this->assertDatabaseCount('institutions', 0);
        $this->assertDatabaseCount('events', 1);
        $this->assertSame('Operator-owned record', $event->fresh()->description);
    }

    public function test_development_participants_and_qr_seed_do_not_touch_unknown_employees(): void
    {
        config(['seeding.uat_password' => 'test-only-seed-password']);
        $this->seed(DatabaseSeeder::class);
        $position = Position::firstOrFail();
        $unknown = Employee::create(['full_name' => 'Unknown protected fixture', 'employee_number' => '0012345678',
            'institution_id' => $position->institution_id, 'position_id' => $position->id,
            'employee_type' => 'guru', 'employment_status' => 'aktif', 'verification_status' => 'verified']);
        $existingEvent = Event::create(['name' => 'Rapat Koordinasi Yayasan', 'event_date' => today(),
            'status' => 'draft', 'target_type' => 'all', 'description' => 'Operator-owned event']);
        $this->seed(DevelopmentSeeder::class);
        $this->seed(EmployeeQrTokenSeeder::class);
        $this->assertFalse($unknown->eventParticipants()->exists());
        $this->assertFalse($unknown->eventAttendances()->exists());
        $this->assertFalse($unknown->qrTokens()->exists());
        $this->assertSame('Unknown protected fixture', $unknown->fresh()->full_name);
        $this->assertDatabaseCount('event_attendances', 9);
        $this->assertSame('draft', $existingEvent->fresh()->status);
        $this->assertSame('Operator-owned event', $existingEvent->description);
        $this->assertSame(0, DB::table('event_participants')->where('event_id', $existingEvent->id)->count());
    }

    public function test_nup_collision_does_not_claim_an_unknown_unlinked_employee(): void
    {
        config(['seeding.uat_password' => 'test-only-seed-password']);
        $this->seed(DatabaseSeeder::class);
        $position = Position::firstOrFail();
        $unknown = Employee::create(['full_name' => 'Unknown protected fixture', 'employee_number' => '7770923822',
            'institution_id' => $position->institution_id, 'position_id' => $position->id,
            'employee_type' => 'guru', 'employment_status' => 'aktif', 'verification_status' => 'draft']);
        try {
            $this->seed(UatSeeder::class);
            $this->fail('Unlinked NUP collision must be refused.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('tanpa bukti fixture', $exception->getMessage());
        }
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('employees', 1);
        $this->assertNull($unknown->fresh()->user_id);
        $this->assertSame('draft', $unknown->verification_status);
    }

    public function test_synthetic_account_collision_never_changes_roles_or_passwords(): void
    {
        config(['seeding.uat_password' => 'test-only-seed-password']);
        $user = User::factory()->create(['email' => 'hr@yapista.test', 'role' => 'pegawai']);
        $password = $user->password;
        try {
            $this->seed(UserSeeder::class);
            $this->fail('Role collision must be refused.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('account collision', $exception->getMessage());
        }
        $this->assertDatabaseCount('users', 1);
        $this->assertSame('pegawai', $user->fresh()->role);
        $this->assertSame($password, $user->password);
    }

    public function test_production_seeding_does_not_call_factories_or_contain_credential_defaults(): void
    {
        foreach (['DatabaseSeeder', 'InstitutionSeeder', 'PositionSeeder'] as $name) {
            $source = file_get_contents(database_path('seeders/'.$name.'.php'));
            foreach (['factory(', '@yapista.test', 'Hash::make(', 'UatSeeder::class', 'UserSeeder::class',
                'EmployeeSeeder::class', 'EventSeeder::class'] as $forbidden) {
                $this->assertStringNotContainsString($forbidden, $source);
            }
        }
        foreach (['UserSeeder', 'EmployeeSeeder'] as $name) {
            $source = file_get_contents(database_path('seeders/'.$name.'.php'));
            $this->assertStringNotContainsString("Hash::make('password", $source);
        }
        $this->assertStringContainsString('UAT_SEED_PASSWORD=', file_get_contents(base_path('.env.example')));
    }
}
