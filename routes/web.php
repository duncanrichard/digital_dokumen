<?php

use Illuminate\Support\Facades\Route;

// ================= Controllers =================
// Dashboard
use App\Http\Controllers\dashboard\Analytics;

// Master Data
use App\Http\Controllers\Master\JenisDokumenController;
use App\Http\Controllers\Master\DepartmentController;

// Documents
use App\Http\Controllers\Documents\DocumentUploadController;
use App\Http\Controllers\Documents\DocumentDistributionController;
use App\Http\Controllers\Documents\DocumentRevisionController;

// User Access
use App\Http\Controllers\Access\UserController;

// Settings
use App\Http\Controllers\Settings\WatermarkController;

// Auth pages
use App\Http\Controllers\authentications\LoginBasic;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Catatan:
| - Halaman login dijadikan root ('/'), hanya bisa diakses guest.
| - Semua halaman aplikasi (dashboard, master, documents, access, settings) butuh auth.
| - Logout dilakukan via POST ke /logout.
|
*/

// Pola ID: Dokumen bisa UUID/ULID/INT, User UUID
$DOC_ID_REGEX = '([0-9a-fA-F-]{36}|[0-9A-HJKMNP-TV-Z]{26}|\d+)';
$UUID_REGEX   = '[0-9a-fA-F-]{36}';

/**
 * ================= AUTH (guest) =================
 * - Halaman login jadi halaman utama
 * - Tamu saja yang boleh akses halaman login
 */
Route::middleware('guest')->group(function () {
    // root -> login
    Route::get('/', [LoginBasic::class, 'index'])->name('login');

    // (opsional) akses langsung /login juga ke halaman yang sama
    Route::get('/login', [LoginBasic::class, 'index'])->name('login.page');

    // proses login (POST)
    Route::post('/login', [LoginBasic::class, 'authenticate'])->name('login.perform');
});

/**
 * ================= AUTH (authenticated) =================
 * - Semua halaman aplikasi diproteksi auth
 * - Termasuk Dashboard, Master, Documents, Access, Settings
 */
Route::middleware('auth')->group(function () use ($DOC_ID_REGEX, $UUID_REGEX) {

    // ================= DASHBOARD =================
    Route::get('/dashboard', [Analytics::class, 'index'])->name('dashboard-analytics');

    // ================= MASTER DATA =================
    Route::prefix('master')->name('master.')->group(function () {
        // Jenis Dokumen
        Route::get('/jenis-dokumen',  [JenisDokumenController::class, 'index'])->name('jenis-dokumen.index');
        Route::post('/jenis-dokumen', [JenisDokumenController::class, 'store'])->name('jenis-dokumen.store');
        Route::put('/jenis-dokumen/{jenisDokumen}',    [JenisDokumenController::class, 'update'])->name('jenis-dokumen.update');
        Route::delete('/jenis-dokumen/{jenisDokumen}', [JenisDokumenController::class, 'destroy'])->name('jenis-dokumen.destroy');

        // Departments
        Route::get('/departments',  [DepartmentController::class, 'index'])->name('departments.index');
        Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
        Route::put('/departments/{department}',    [DepartmentController::class, 'update'])->name('departments.update');
        Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');
    });

    // ================= NOTIFICATIONS (global) =================
    Route::post('/notifications/read-all', [DocumentUploadController::class, 'markAllNotificationsRead'])
        ->name('notifications.readAll');

    // ================= DOCUMENTS =================
    Route::prefix('documents')->name('documents.')->group(function () use ($DOC_ID_REGEX) {
        // Library & upload
        // Note: route name 'documents.index' dipakai di navbar untuk "View all documents"
        Route::get('/upload',  [DocumentUploadController::class, 'index'])->name('index');
        Route::post('/upload', [DocumentUploadController::class, 'store'])->name('store');

        // Open PDF + mark as read (buka di tab baru)
        Route::get('/{document}/open', [DocumentUploadController::class, 'open'])
            ->where('document', $DOC_ID_REGEX)->name('open');

        // Stream raw PDF (untuk PDF.js/iframe)
        Route::get('/{document}/file', [DocumentUploadController::class, 'stream'])
            ->where('document', $DOC_ID_REGEX)->name('file');

        // (Opsional) Download—jika kamu ingin pisahkan dari stream
        // Route::get('/{document}/download', [DocumentUploadController::class, 'download'])
        //     ->where('document', $DOC_ID_REGEX)->name('download');

        // Edit/Update/Delete
        Route::get('/{document}/edit', [DocumentUploadController::class, 'edit'])
            ->where('document', $DOC_ID_REGEX)->name('edit');

        Route::put('/{document}', [DocumentUploadController::class, 'update'])
            ->where('document', $DOC_ID_REGEX)->name('update');

        Route::delete('/{document}', [DocumentUploadController::class, 'destroy'])
            ->where('document', $DOC_ID_REGEX)->name('destroy');

        // Distribution
        Route::prefix('distribution')->name('distribution.')->group(function () {
            Route::get('/',  [DocumentDistributionController::class, 'index'])->name('index');
            Route::post('/', [DocumentDistributionController::class, 'store'])->name('store');
        });

        // Revisions
        Route::get('/revisions',  [DocumentRevisionController::class, 'index'])->name('revisions.index');
        Route::post('/revisions', [DocumentRevisionController::class, 'store'])->name('revisions.store');
    });

    // ================= USER ACCESS =================
    Route::prefix('access')->name('access.')->group(function () use ($UUID_REGEX) {
        Route::prefix('users')->name('users.')->group(function () use ($UUID_REGEX) {
            Route::get('/',          [UserController::class, 'index'])->name('index');
            Route::post('/',         [UserController::class, 'store'])->name('store');
            Route::put('/{user}',    [UserController::class, 'update'])->where('user', $UUID_REGEX)->name('update');
            Route::delete('/{user}', [UserController::class, 'destroy'])->where('user', $UUID_REGEX)->name('destroy');
        });
    });

    // ================= SETTINGS =================
    Route::prefix('settings')->group(function () {
        // Watermark/DRM
        Route::get('/watermark',  [WatermarkController::class, 'index'])->name('settings-watermark');
        Route::post('/watermark', [WatermarkController::class, 'update'])->name('settings-watermark.update');
    });

    // ================= LOGOUT =================
    // Logout via POST, akan diarahkan kembali ke route('login') oleh controller
    Route::post('/logout', [LoginBasic::class, 'logout'])->name('logout');
});
