<?php

use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeDocumentController;
use App\Http\Controllers\EmployeeInvitationController;
use App\Http\Controllers\EmployeeProfileController;
use App\Http\Controllers\EmployeeVerificationController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventParticipantController;
use App\Http\Controllers\InstitutionController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\ProfileController;
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

Route::middleware(['auth', 'role:pegawai'])->prefix('pegawai')->name('pegawai.')->group(function () {
    Route::get('/profile', [EmployeeProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [EmployeeProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [EmployeeProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/submit', [EmployeeProfileController::class, 'submit'])->name('profile.submit');

    Route::get('/documents', [EmployeeDocumentController::class, 'index'])->name('documents.index');
    Route::post('/documents', [EmployeeDocumentController::class, 'store'])->name('documents.store');
    Route::delete('/documents/{document}', [EmployeeDocumentController::class, 'destroy'])->name('documents.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
