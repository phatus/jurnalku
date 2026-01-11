<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReportController;

Route::get('/', function () {
    return redirect()->route('filament.admin.auth.login');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/report/download', [ReportController::class, 'download'])->name('report.download');
});
