<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Institution;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('email', 'admin@yapista.test')->first();
        $pegawaiUser = User::where('email', 'pegawai@yapista.test')->first();

        $employees = [
            [
                'full_name' => 'Ahmad Fauzi',
                'email' => 'ahmad.fauzi@yapista.test',
                'institution' => 'SMK Ibnu Sina',
                'position' => 'Guru',
                'employee_type' => 'guru',
                'employment_status' => 'aktif',
                'verification_status' => 'verified',
                'join_date' => '2026-05-10',
                'employee_number' => '7770923822',
                'gender' => 'male',
                'birth_place' => 'Batam',
                'birth_date' => '1990-01-12',
                'phone' => '081277709001',
                'nik' => '2171011201900001',
            ],
            [
                'full_name' => 'Siti Aminah',
                'email' => 'siti.aminah@yapista.test',
                'institution' => 'SD Ibnu Sina',
                'position' => 'Guru',
                'employee_type' => 'guru',
                'employment_status' => 'aktif',
                'verification_status' => 'submitted',
                'join_date' => '2026-05-11',
                'employee_number' => '7770923823',
                'gender' => 'female',
                'birth_place' => 'Tanjungpinang',
                'birth_date' => '1992-02-14',
                'phone' => '081277709002',
                'nik' => '2171011402920002',
            ],
            [
                'full_name' => 'Budi Santoso',
                'email' => 'budi.santoso@yapista.test',
                'institution' => 'Universitas Ibnu Sina',
                'position' => 'Dosen',
                'employee_type' => 'dosen',
                'employment_status' => 'aktif',
                'verification_status' => 'verified',
                'join_date' => '2026-05-12',
                'employee_number' => '7770923824',
                'gender' => 'male',
                'birth_place' => 'Medan',
                'birth_date' => '1988-03-16',
                'phone' => '081277709003',
                'nik' => '2171011603880003',
            ],
            [
                'full_name' => 'Nurul Huda',
                'email' => 'nurul.huda@yapista.test',
                'institution' => 'Kantor Yayasan',
                'position' => 'Staff Yayasan',
                'employee_type' => 'staff_yayasan',
                'employment_status' => 'aktif',
                'verification_status' => 'draft',
                'join_date' => '2026-05-13',
                'employee_number' => '7770923825',
                'gender' => 'male',
                'birth_place' => 'Batam',
                'birth_date' => '1991-04-18',
                'phone' => '081277709004',
                'nik' => '2171011804910004',
            ],
            [
                'full_name' => 'Rina Marlina',
                'email' => 'rina.marlina@yapista.test',
                'institution' => 'STAI Ibnu Sina',
                'position' => 'Dosen',
                'employee_type' => 'dosen',
                'employment_status' => 'aktif',
                'verification_status' => 'rejected',
                'verification_note' => 'Dokumen KTP belum jelas.',
                'join_date' => '2026-05-14',
                'employee_number' => '7770923826',
                'gender' => 'female',
                'birth_place' => 'Padang',
                'birth_date' => '1993-05-20',
                'phone' => '081277709005',
                'nik' => '2171012005930005',
            ],
            [
                'full_name' => 'Andi Pratama',
                'email' => 'andi.pratama@yapista.test',
                'institution' => 'SMP Ibnu Sina',
                'position' => 'Staff TU',
                'employee_type' => 'tenaga_kependidikan',
                'employment_status' => 'aktif',
                'verification_status' => 'verified',
                'join_date' => '2026-05-15',
                'employee_number' => '7770923827',
                'gender' => 'male',
                'birth_place' => 'Batam',
                'birth_date' => '1989-06-22',
                'phone' => '081277709006',
                'nik' => '2171012206890006',
            ],
            [
                'full_name' => 'Dewi Lestari',
                'email' => 'dewi.lestari@yapista.test',
                'institution' => 'TK Ibnu Sina',
                'position' => 'Guru',
                'employee_type' => 'guru',
                'employment_status' => 'aktif',
                'verification_status' => 'verified',
                'join_date' => '2026-05-16',
                'employee_number' => '7770923828',
                'gender' => 'female',
                'birth_place' => 'Pekanbaru',
                'birth_date' => '1994-07-24',
                'phone' => '081277709007',
                'nik' => '2171012407940007',
            ],
            [
                'full_name' => 'Fajar Ramadhan',
                'email' => 'fajar.ramadhan@yapista.test',
                'institution' => 'Kantor Yayasan',
                'position' => 'Staff IT',
                'employee_type' => 'teknisi',
                'employment_status' => 'aktif',
                'verification_status' => 'verified',
                'join_date' => '2026-05-17',
                'employee_number' => '7770923829',
                'gender' => 'male',
                'birth_place' => 'Batam',
                'birth_date' => '1995-08-26',
                'phone' => '081277709008',
                'nik' => '2171012608950008',
            ],
            [
                'full_name' => 'Maya Sari',
                'email' => 'maya.sari@yapista.test',
                'institution' => 'SMK Ibnu Sina',
                'position' => 'Operator Sekolah',
                'employee_type' => 'tenaga_kependidikan',
                'employment_status' => 'kontrak',
                'verification_status' => 'submitted',
                'join_date' => '2026-06-01',
                'employee_number' => '7770923830',
                'gender' => 'female',
                'birth_place' => 'Batam',
                'birth_date' => '1996-09-28',
                'phone' => '081277709009',
                'nik' => '2171012809960009',
            ],
            [
                'full_name' => 'Rahmat Hidayat',
                'email' => 'rahmat.hidayat@yapista.test',
                'institution' => 'SD Ibnu Sina',
                'position' => 'Kepala Sekolah',
                'employee_type' => 'tenaga_kependidikan',
                'employment_status' => 'aktif',
                'verification_status' => 'verified',
                'join_date' => '2026-06-02',
                'employee_number' => '7770923831',
                'gender' => 'male',
                'birth_place' => 'Bukittinggi',
                'birth_date' => '1985-10-30',
                'phone' => '081277709010',
                'nik' => '2171013010850010',
            ],
            [
                'full_name' => 'Putri Aulia',
                'email' => 'putri.aulia@yapista.test',
                'institution' => 'STAI Ibnu Sina',
                'position' => 'Staff Akademik',
                'employee_type' => 'tenaga_kependidikan',
                'employment_status' => 'aktif',
                'verification_status' => 'draft',
                'join_date' => '2026-06-03',
                'employee_number' => '7770923832',
                'gender' => 'female',
                'birth_place' => 'Batam',
                'birth_date' => '1997-11-02',
                'phone' => '081277709011',
                'nik' => '2171010211970011',
            ],
            [
                'full_name' => 'Hendra Wijaya',
                'email' => 'hendra.wijaya@yapista.test',
                'institution' => 'Universitas Ibnu Sina',
                'position' => 'Kaprodi',
                'employee_type' => 'dosen',
                'employment_status' => 'aktif',
                'verification_status' => 'verified',
                'join_date' => '2026-06-04',
                'employee_number' => '7770923833',
                'gender' => 'male',
                'birth_place' => 'Jakarta',
                'birth_date' => '1984-12-04',
                'phone' => '081277709012',
                'nik' => '2171010412840012',
            ],
        ];

        foreach ($employees as $employee) {
            $institution = Institution::where('name', $employee['institution'])->first();

            if (! $institution) {
                continue;
            }

            $position = Position::where('institution_id', $institution->id)
                ->where('name', $employee['position'])
                ->first();

            if (! $position) {
                continue;
            }

            $payload = [
                'institution_id' => $institution->id,
                'position_id' => $position->id,
                'employee_number' => $employee['employee_number'],
                'full_name' => $employee['full_name'],
                'email' => $employee['email'],
                'nik' => $employee['nik'],
                'gender' => $employee['gender'],
                'birth_place' => $employee['birth_place'],
                'birth_date' => $employee['birth_date'],
                'phone' => $employee['phone'],
                'address' => 'Komplek Yayasan Ibnu Sina, Batam',
                'employee_type' => $employee['employee_type'],
                'employment_status' => $employee['employment_status'],
                'join_date' => $employee['join_date'],
                'photo' => null,
                'verification_status' => $employee['verification_status'],
                'verification_note' => $employee['verification_note'] ?? null,
                'verified_by' => $employee['verification_status'] === 'verified' ? $admin?->id : null,
                'verified_at' => $employee['verification_status'] === 'verified'
                    ? Carbon::parse($employee['join_date'])->addDays(3)
                    : null,
            ];

            Employee::updateOrCreate(
                ['email' => $employee['email']],
                $payload,
            );
        }

        if ($pegawaiUser && ($ahmad = Employee::where('email', 'ahmad.fauzi@yapista.test')->first())) {
            if ($ahmad->user_id !== null && $ahmad->user_id !== $pegawaiUser->id) {
                return;
            }

            $linkedEmployee = Employee::where('user_id', $pegawaiUser->id)->first();
            $demoEmails = array_column($employees, 'email');

            if ($linkedEmployee && $linkedEmployee->isNot($ahmad)) {
                if (! in_array($linkedEmployee->email, $demoEmails, true)) {
                    return;
                }

                $linkedEmployee->update(['user_id' => null]);
            }

            $ahmad->update(['user_id' => $pegawaiUser->id]);
        }
    }
}
