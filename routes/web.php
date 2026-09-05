<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/private-files/{path}', function (string $path) {
    abort_unless(Storage::disk('private')->exists($path), 404);

    return Storage::disk('private')->response($path);
})
    ->where('path', '.*')
    ->name('private-files.show')
    ->middleware('signed');
