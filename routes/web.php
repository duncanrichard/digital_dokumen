<?php

use Illuminate\Support\Facades\Route;

// ================= Controllers =================
use App\Http\Controllers\dashboard\Analytics;

// Master Data
use App\Http\Controllers\Master\JenisDokumenController;
use App\Http\Controllers\Master\DepartmentController;

// Documents
use App\Http\Controllers\Documents\DocumentUploadController;
use App\Http\Controllers\Documents\DocumentDistributionController;
use App\Http\Controllers\Documents\DocumentRevisionController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| Tambahkan middleware auth/role sesuai kebutuhan.
*/

// ================= DASHBOARD =================
Route::get('/', [Analytics::class, 'index'])->name('dashboard-analytics');

// Pola ID dokumen: UUID(36), ULID(26), atau INT
$DOC_ID_REGEX = '([0-9a-fA-F-]{36}|[0-9A-HJKMNP-TV-Z]{26}|\d+)';

// ================= MASTER DATA =================
Route::prefix('master')->name('master.')->group(function () {
    // Jenis Dokumen
    Route::get('/jenis-dokumen', [JenisDokumenController::class, 'index'])->name('jenis-dokumen.index');
    Route::post('/jenis-dokumen', [JenisDokumenController::class, 'store'])->name('jenis-dokumen.store');
    Route::put('/jenis-dokumen/{jenisDokumen}', [JenisDokumenController::class, 'update'])->name('jenis-dokumen.update');
    Route::delete('/jenis-dokumen/{jenisDokumen}', [JenisDokumenController::class, 'destroy'])->name('jenis-dokumen.destroy');

    // Departments
    Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
    Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
    Route::put('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
    Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');
});

// ================= DOCUMENTS =================
Route::prefix('documents')->name('documents.')->group(function () use ($DOC_ID_REGEX) {

    // Upload & Library (tetap di /documents/upload)
    Route::get('/upload',  [DocumentUploadController::class, 'index'])->name('index');
    Route::post('/upload', [DocumentUploadController::class, 'store'])->name('store');

    // Stream file PDF same-origin (dipakai oleh viewer PDF.js)
    Route::get('/{document}/file', [DocumentUploadController::class, 'stream'])
        ->where('document', $DOC_ID_REGEX)
        ->name('file');
        
    // Edit / Update / Delete
    Route::get('/{document}/edit', [DocumentUploadController::class, 'edit'])
        ->where('document', $DOC_ID_REGEX)
        ->name('edit');

    Route::put('/{document}', [DocumentUploadController::class, 'update'])
        ->where('document', $DOC_ID_REGEX)
        ->name('update');

    Route::delete('/{document}', [DocumentUploadController::class, 'destroy'])
        ->where('document', $DOC_ID_REGEX)
        ->name('destroy');

    // Notifications (navbar bell)
    Route::post('/notifications/mark-all', [DocumentUploadController::class, 'markAllNotifications'])
        ->name('notifications.markAll');

    Route::post('/notifications/{document}/read', [DocumentUploadController::class, 'markNotificationRead'])
        ->where('document', $DOC_ID_REGEX)
        ->name('notifications.read');

    // Distribution
    Route::prefix('distribution')->name('distribution.')->group(function () {
        Route::get('/',  [DocumentDistributionController::class, 'index'])->name('index');
        Route::post('/', [DocumentDistributionController::class, 'store'])->name('store');
    });

    // Revisions
    Route::get('/revisions',  [DocumentRevisionController::class, 'index'])->name('revisions.index');
    Route::post('/revisions', [DocumentRevisionController::class, 'store'])->name('revisions.store');
});

// (Opsional) Fallback 404
// Route::fallback(fn() => abort(404));
