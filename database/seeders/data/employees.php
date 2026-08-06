<?php

/*
| Employee onboarding data
| employee_number must contain exactly 10 digits and login_email must be unique.
| institution_name and position_name must already exist in their master seeders.
| Profile fields are intentionally omitted; employees complete them in the wizard.
| New-account passwords come from EMPLOYEE_SEED_DEFAULT_PASSWORD.
*/

return [
    ['employee_number' => '7770923822', 'full_name' => 'Ahmad Fauzi', 'login_email' => 'pegawai@yapista.test', 'personal_email' => null, 'institution_name' => 'SMK Ibnu Sina', 'position_name' => 'Guru', 'employee_type' => 'guru', 'employment_status' => 'aktif', 'join_date' => '2026-05-10'],
    ['employee_number' => '7770923823', 'full_name' => 'Siti Aminah', 'login_email' => 'siti.aminah@yapista.test', 'personal_email' => null, 'institution_name' => 'SD Ibnu Sina', 'position_name' => 'Guru', 'employee_type' => 'guru', 'employment_status' => 'aktif', 'join_date' => '2026-05-11'],
    ['employee_number' => '7770923824', 'full_name' => 'Budi Santoso', 'login_email' => 'budi.santoso@yapista.test', 'personal_email' => null, 'institution_name' => 'Universitas Ibnu Sina', 'position_name' => 'Dosen', 'employee_type' => 'dosen', 'employment_status' => 'aktif', 'join_date' => '2026-05-12'],
    ['employee_number' => '7770923825', 'full_name' => 'Nurul Huda', 'login_email' => 'nurul.huda@yapista.test', 'personal_email' => null, 'institution_name' => 'Kantor Yayasan', 'position_name' => 'Staff Yayasan', 'employee_type' => 'staff_yayasan', 'employment_status' => 'aktif', 'join_date' => '2026-05-13'],
    ['employee_number' => '7770923826', 'full_name' => 'Rina Marlina', 'login_email' => 'rina.marlina@yapista.test', 'personal_email' => null, 'institution_name' => 'STAI Ibnu Sina', 'position_name' => 'Dosen', 'employee_type' => 'dosen', 'employment_status' => 'aktif', 'join_date' => '2026-05-14'],
    ['employee_number' => '7770923827', 'full_name' => 'Andi Pratama', 'login_email' => 'andi.pratama@yapista.test', 'personal_email' => null, 'institution_name' => 'SMP Ibnu Sina', 'position_name' => 'Staff TU', 'employee_type' => 'tenaga_kependidikan', 'employment_status' => 'aktif', 'join_date' => '2026-05-15'],
    ['employee_number' => '7770923828', 'full_name' => 'Dewi Lestari', 'login_email' => 'dewi.lestari@yapista.test', 'personal_email' => null, 'institution_name' => 'TK Ibnu Sina', 'position_name' => 'Guru', 'employee_type' => 'guru', 'employment_status' => 'aktif', 'join_date' => '2026-05-16'],
    ['employee_number' => '7770923829', 'full_name' => 'Fajar Ramadhan', 'login_email' => 'fajar.ramadhan@yapista.test', 'personal_email' => null, 'institution_name' => 'Kantor Yayasan', 'position_name' => 'Staff IT', 'employee_type' => 'teknisi', 'employment_status' => 'aktif', 'join_date' => '2026-05-17'],
    ['employee_number' => '7770923830', 'full_name' => 'Maya Sari', 'login_email' => 'maya.sari@yapista.test', 'personal_email' => null, 'institution_name' => 'SMK Ibnu Sina', 'position_name' => 'Operator Sekolah', 'employee_type' => 'tenaga_kependidikan', 'employment_status' => 'kontrak', 'join_date' => '2026-06-01'],
    ['employee_number' => '7770923831', 'full_name' => 'Rahmat Hidayat', 'login_email' => 'rahmat.hidayat@yapista.test', 'personal_email' => null, 'institution_name' => 'SD Ibnu Sina', 'position_name' => 'Kepala Sekolah', 'employee_type' => 'tenaga_kependidikan', 'employment_status' => 'aktif', 'join_date' => '2026-06-02'],
    ['employee_number' => '7770923832', 'full_name' => 'Putri Aulia', 'login_email' => 'putri.aulia@yapista.test', 'personal_email' => null, 'institution_name' => 'STAI Ibnu Sina', 'position_name' => 'Staff Akademik', 'employee_type' => 'tenaga_kependidikan', 'employment_status' => 'aktif', 'join_date' => '2026-06-03'],
    ['employee_number' => '7770923833', 'full_name' => 'Hendra Wijaya', 'login_email' => 'hendra.wijaya@yapista.test', 'personal_email' => null, 'institution_name' => 'Universitas Ibnu Sina', 'position_name' => 'Kaprodi', 'employee_type' => 'dosen', 'employment_status' => 'aktif', 'join_date' => '2026-06-04'],
];
