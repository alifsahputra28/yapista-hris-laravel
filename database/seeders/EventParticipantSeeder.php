<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\EventParticipant;
use App\Models\Institution;
use Database\Seeders\Support\SyntheticSeed;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;

class EventParticipantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SyntheticSeed::guard(['local', 'testing']);
        $this->seedParticipants(
            'Rapat Koordinasi Yayasan',
            $this->eligibleEmployeeIds(Employee::query()->eligibleForEvents())
        );

        $this->seedParticipants(
            'Workshop Guru dan Dosen',
            $this->eligibleEmployeeIds(Employee::query()
                ->eligibleForEvents()
                ->whereIn('employee_type', ['guru', 'dosen']))
        );

        $this->seedParticipants(
            'Halal Bihalal YAPISTA',
            $this->eligibleEmployeeIds(Employee::query()->eligibleForEvents())
        );

        $smk = Institution::where('name', 'SMK Ibnu Sina')->first();

        $this->seedParticipants(
            'Rapat Internal SMK',
            $smk
                ? $this->eligibleEmployeeIds(Employee::query()->eligibleForEvents()->where('institution_id', $smk->id))
                : []
        );

        $selected = $this->eligibleEmployeeIds(Employee::query()
            ->eligibleForEvents()
            ->whereHas('user', function ($query): void {
                $query->whereIn('email', ['pegawai@yapista.test', 'budi.santoso@yapista.test']);
            }));

        $this->seedParticipants('Sosialisasi Program Dibatalkan', $selected, 'cancelled');
    }

    /**
     * @param  Builder<Employee>  $query
     * @return array<int, int>
     */
    private function eligibleEmployeeIds($query): array
    {
        return $query
            ->whereIn('id', SyntheticSeed::employees()->select('employees.id'))
            ->get(['id', 'employee_number'])
            ->filter(fn (Employee $employee): bool => $employee->hasValidEmployeeNumber())
            ->pluck('id')
            ->all();
    }

    /**
     * @param  array<int, int>  $employeeIds
     */
    private function seedParticipants(string $eventName, array $employeeIds, string $status = 'invited'): void
    {
        $event = SyntheticSeed::developmentEvent($eventName);

        if (! $event) {
            return;
        }

        foreach (array_unique($employeeIds) as $employeeId) {
            EventParticipant::firstOrCreate(
                [
                    'event_id' => $event->id,
                    'employee_id' => $employeeId,
                ],
                [
                    'participant_status' => $status,
                ],
            );
        }
    }
}
