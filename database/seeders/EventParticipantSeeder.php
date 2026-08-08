<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Institution;
use Illuminate\Database\Seeder;

class EventParticipantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
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
     * @param  \Illuminate\Database\Eloquent\Builder<Employee>  $query
     * @return array<int, int>
     */
    private function eligibleEmployeeIds($query): array
    {
        return $query
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
        $event = Event::where('name', $eventName)->first();

        if (! $event) {
            return;
        }

        foreach (array_unique($employeeIds) as $employeeId) {
            EventParticipant::updateOrCreate(
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
