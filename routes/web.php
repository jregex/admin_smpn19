<?php

use App\Http\Controllers\Api\UjianController;
use App\Livewire\Auth\Login;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', Login::class)->name('login')->middleware('ignore-login');
// Route::post('/administrator', [AuthController::class, 'check_login']);
Route::prefix('admin')->middleware(['auth', 'verified', 'tahun-ajaran'])->group(function () {
    Route::livewire('/dashboard', 'admin.dashboard')->name('admin.dashboard');
    Route::livewire('/settings', 'admin.settings')->name('admin.settings');
    Route::livewire('/profile', 'admin.profile')->name('admin.profile');
    Route::livewire('/guru', 'admin.guru.index')->name('master.guru')->middleware('akses-guru');
    Route::livewire('/ekskul', 'admin.ekskuls.index')->name('admin.ekskul');
    Route::livewire('/report-siswa', 'admin.report.lapor')->name('report.siswa');
    Route::livewire('/ujians', 'admin.ujians.index')->name('ujian.index');
    Route::livewire('/ujians/edit/{id}', 'admin.ujians.ujian-wizard')->name('ujian.edit')->whereNumber('id');
    Route::post('/ujians/start/{id}', [UjianController::class, 'aktivasiUjian'])->name('ujian.start')->whereNumber('id');
    Route::post('/ujians/stop/{id}', [UjianController::class, 'stopUjian'])->name('ujian.start')->whereNumber('id');
    Route::get('/ujians/{id}/export-nilai', [UjianController::class, 'exportNilaiSiswa'])->name('ujian.export-nilai')->whereNumber('id');
    Route::livewire('/ujians/buat', 'admin.ujians.ujian-wizard')->name('ujian.create');
    Route::livewire('/jadwal', 'admin.jadwal.index')->name('jadwal');
    Route::livewire('/info', 'admin.info.index')->name('admin.info');
    Route::middleware('akses-admin')->group(function () {
        Route::livewire('/tahun-ajaran', 'admin.tahun-ajaran.index')->name('master.tahun');
        Route::livewire('/siswa', 'admin.siswa.index')->name('master.siswa');
        Route::livewire('/jurusan', 'admin.jurusan.index')->name('master.jurusan');
        Route::livewire('/kelas', 'admin.kelas.index')->name('master.kelas');
        Route::livewire('/rombel', 'admin.rombel.index')->name('master.rombel');
        Route::livewire('/mapel', 'admin.mapel.index')->name('master.mapel');
    });
    Route::middleware('akses-superadmin')->group(function () {
        // Route::livewire('/users', 'admin.users.index')->name('data.users');
        Route::livewire('/sekolah', 'admin.sekolah.index')->name('data.sekolah');
    });
    // logout
    Route::post('/logout', function (Request $request) {
        $request->session()->flush();

        return to_route('login');
    })->name('logout');
});
