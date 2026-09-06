<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PemohonProfileController;
use Illuminate\Support\Facades\Storage;

Route::view('/', 'welcome')->name('landing');

Route::middleware('auth')->group(function (): void {
    Route::get('/pemohon/profil', [PemohonProfileController::class, 'edit'])->name('pemohon.profil');
    Route::post('/pemohon/profil', [PemohonProfileController::class, 'update'])->name('pemohon.profil.update');
});

Route::get('/private-files/{path}/download', function (string $path) {
    abort_unless(Storage::disk('private')->exists($path), 404);

    return Storage::disk('private')->download($path);
})
    ->where('path', '.*')
    ->name('private-files.download')
    ->middleware('signed');

Route::get('/private-files/{path}', function (string $path) {
    abort_unless(Storage::disk('private')->exists($path), 404);

    return Storage::disk('private')->response($path);
})
    ->where('path', '.*')
    ->name('private-files.show')
    ->middleware('signed');
