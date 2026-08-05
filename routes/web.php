<?php

use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeAdministrativeDetailController;
use App\Http\Controllers\EmployeeCertificationController;
use App\Http\Controllers\EmployeeDocumentAccessController;
use App\Http\Controllers\EmployeeDocumentController;
use App\Http\Controllers\EmployeeEducationController;
use App\Http\Controllers\EmployeeFamilyMemberController;
use App\Http\Controllers\EmployeeIdCardController;
use App\Http\Controllers\EmployeeInvitationController;
use App\Http\Controllers\EmployeeProfileController;
use App\Http\Controllers\EmployeeProfileWizardController;
use App\Http\Controllers\EmployeeVerificationController;
use App\Http\Controllers\EventAttendanceController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventParticipantController;
use App\Http\Controllers\InstitutionController;
use App\Http\Controllers\PegawaiIdCardController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'role:super_admin,hr_admin'])->name('dashboard');

Route::get('/scanner/dashboard', function () {
    return view('scanner.dashboard');
})->middleware(['auth', 'role:panitia'])->name('scanner.dashboard');

Route::get('/pegawai/dashboard', function () {
    return view('pegawai.dashboard');
})->middleware(['auth', 'role:pegawai'])->name('pegawai.dashboard');

Route::middleware(['auth', 'role:super_admin,hr_admin'])->group(function () {
    Route::resource('institutions', InstitutionController::class)->except(['show']);
    Route::resource('positions', PositionController::class)->except(['show']);
    Route::resource('employees', EmployeeController::class);
    Route::get('/employees/{employee}/id-card', [EmployeeIdCardController::class, 'show'])->name('employees.id-card.show');
    Route::get('/employees/{employee}/id-card/download', [EmployeeIdCardController::class, 'download'])->name('employees.id-card.download');
    Route::get('/invitations', [EmployeeInvitationController::class, 'index'])->name('invitations.index');
    Route::post('/employees/{employee}/invitations/generate', [EmployeeInvitationController::class, 'generate'])->name('employees.invitations.generate');
    Route::patch('/invitations/{invitation}/revoke', [EmployeeInvitationController::class, 'revoke'])->name('invitations.revoke');

    Route::get('/verifications', [EmployeeVerificationController::class, 'index'])->name('verifications.index');
    Route::get('/verifications/{employee}', [EmployeeVerificationController::class, 'show'])->name('verifications.show');
    Route::post('/verifications/{employee}/approve', [EmployeeVerificationController::class, 'approve'])->name('verifications.approve');
    Route::post('/verifications/{employee}/reject', [EmployeeVerificationController::class, 'reject'])->name('verifications.reject');
    Route::patch('/employee-documents/{document}/status', [EmployeeVerificationController::class, 'updateDocumentStatus'])->name('employee-documents.update-status');

    Route::resource('events', EventController::class);
    Route::post('/events/{event}/activate', [EventController::class, 'activate'])->name('events.activate');
    Route::post('/events/{event}/close', [EventController::class, 'close'])->name('events.close');
    Route::post('/events/{event}/cancel', [EventController::class, 'cancel'])->name('events.cancel');

    Route::get('/events/{event}/participants', [EventParticipantController::class, 'index'])->name('events.participants.index');
    Route::post('/events/{event}/participants/generate', [EventParticipantController::class, 'generate'])->name('events.participants.generate');
    Route::post('/events/{event}/participants/manual', [EventParticipantController::class, 'storeManual'])->name('events.participants.manual');
    Route::delete('/event-participants/{participant}', [EventParticipantController::class, 'destroy'])->name('event-participants.destroy');
});

Route::middleware(['auth', 'role:super_admin,hr_admin,panitia'])->group(function () {
    Route::get('/events/{event}/attendances', [EventAttendanceController::class, 'index'])->name('events.attendances.index');
    Route::get('/events/{event}/scanner', [EventAttendanceController::class, 'scanner'])->name('events.scanner');
    Route::post('/events/{event}/scan', [EventAttendanceController::class, 'scan'])->name('events.scan');
    Route::post('/events/{event}/attendances/manual', [EventAttendanceController::class, 'manual'])->name('events.attendances.manual');
});

Route::middleware(['auth', 'role:super_admin,hr_admin'])->group(function () {
    Route::delete('/event-attendances/{attendance}', [EventAttendanceController::class, 'destroy'])->name('event-attendances.destroy');
});

Route::middleware(['auth', 'role:super_admin,hr_admin'])->prefix('reports')->name('reports.')->group(function () {
    Route::get('/employees', [ReportController::class, 'employees'])->name('employees');
    Route::get('/employees/export', [ReportController::class, 'exportEmployees'])->name('employees.export');

    Route::get('/events', [ReportController::class, 'events'])->name('events');
    Route::get('/events/export', [ReportController::class, 'exportEvents'])->name('events.export');

    Route::get('/events/{event}/attendances', [ReportController::class, 'eventAttendances'])->name('events.attendances');
    Route::get('/events/{event}/attendances/export', [ReportController::class, 'exportEventAttendances'])->name('events.attendances.export');
});

Route::middleware(['auth', 'role:super_admin,hr_admin,pegawai'])->group(function () {
    Route::get('/employee-documents/{employeeDocument}/view', [EmployeeDocumentAccessController::class, 'view'])
        ->name('employee-documents.view');
    Route::get('/employee-documents/{employeeDocument}/download', [EmployeeDocumentAccessController::class, 'download'])
        ->name('employee-documents.download');
});

Route::middleware(['auth', 'role:pegawai'])->prefix('pegawai')->name('pegawai.')->group(function () {
    Route::get('/profile', [EmployeeProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [EmployeeProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [EmployeeProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/submit', [EmployeeProfileController::class, 'submit'])->name('profile.submit');
    Route::get('/profile/family-members/create', [EmployeeFamilyMemberController::class, 'create'])->name('profile.family-members.create');
    Route::post('/profile/family-members', [EmployeeFamilyMemberController::class, 'store'])->name('profile.family-members.store');
    Route::get('/profile/family-members/{familyMember}/edit', [EmployeeFamilyMemberController::class, 'edit'])->name('profile.family-members.edit');
    Route::put('/profile/family-members/{familyMember}', [EmployeeFamilyMemberController::class, 'update'])->name('profile.family-members.update');
    Route::delete('/profile/family-members/{familyMember}', [EmployeeFamilyMemberController::class, 'destroy'])->name('profile.family-members.destroy');
    Route::get('/profile/educations/create', [EmployeeEducationController::class, 'create'])->name('profile.educations.create');
    Route::post('/profile/educations', [EmployeeEducationController::class, 'store'])->name('profile.educations.store');
    Route::get('/profile/educations/{education}/edit', [EmployeeEducationController::class, 'edit'])->name('profile.educations.edit');
    Route::put('/profile/educations/{education}', [EmployeeEducationController::class, 'update'])->name('profile.educations.update');
    Route::delete('/profile/educations/{education}', [EmployeeEducationController::class, 'destroy'])->name('profile.educations.destroy');
    Route::get('/profile/certifications/create', [EmployeeCertificationController::class, 'create'])->name('profile.certifications.create');
    Route::post('/profile/certifications', [EmployeeCertificationController::class, 'store'])->name('profile.certifications.store');
    Route::get('/profile/certifications/{certification}/edit', [EmployeeCertificationController::class, 'edit'])->name('profile.certifications.edit');
    Route::put('/profile/certifications/{certification}', [EmployeeCertificationController::class, 'update'])->name('profile.certifications.update');
    Route::delete('/profile/certifications/{certification}', [EmployeeCertificationController::class, 'destroy'])->name('profile.certifications.destroy');
    Route::get('/profile/administrative-details/edit', [EmployeeAdministrativeDetailController::class, 'edit'])->name('profile.administrative-details.edit');
    Route::match(['put', 'patch'], '/profile/administrative-details', [EmployeeAdministrativeDetailController::class, 'update'])->name('profile.administrative-details.update');
    Route::get('/profile/complete', [EmployeeProfileWizardController::class, 'index'])->name('profile.wizard.index');
    Route::match(['put', 'patch'], '/profile/complete/identification', [EmployeeProfileWizardController::class, 'updateIdentification'])->name('profile.wizard.identification.update');
    Route::match(['put', 'patch'], '/profile/complete/contact-address', [EmployeeProfileWizardController::class, 'updateContactAddress'])->name('profile.wizard.contact-address.update');
    Route::match(['put', 'patch'], '/profile/complete/family', [EmployeeProfileWizardController::class, 'updateEmergencyContact'])->name('profile.wizard.family.update');
    Route::get('/profile/complete/{step}', [EmployeeProfileWizardController::class, 'show'])->name('profile.wizard.show');

    Route::get('/documents', [EmployeeDocumentController::class, 'index'])->name('documents.index');
    Route::post('/documents', [EmployeeDocumentController::class, 'store'])->name('documents.store');
    Route::delete('/documents/{document}', [EmployeeDocumentController::class, 'destroy'])->name('documents.destroy');

    Route::get('/id-card', [PegawaiIdCardController::class, 'show'])->name('id-card.show');
    Route::get('/id-card/download', [PegawaiIdCardController::class, 'download'])->name('id-card.download');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
