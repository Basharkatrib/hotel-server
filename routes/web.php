<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// CSRF cookie route for Sanctum (must be in web routes, not api)
Route::get('/sanctum/csrf-cookie', function () {
    return response()->json(['message' => 'CSRF cookie set']);
});

Route::get('/financial-report/export', [App\Http\Controllers\FinancialReportController::class, 'export'])
    ->name('financial.report.export')
    ->middleware('auth');
