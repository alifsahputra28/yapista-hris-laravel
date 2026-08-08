@props(['employee'])

<span>NUP {{ $employee->formatted_employee_number }}</span>
<span aria-hidden="true">&bull;</span>
<span>{{ $employee->institution?->name ?? 'Unit belum ditetapkan' }}</span>
<span aria-hidden="true">&bull;</span>
<span>{{ $employee->position?->name ?? 'Jabatan belum ditetapkan' }}</span>
