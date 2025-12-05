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
use App\Http\Controllers\Documents\DocumentAccessApprovalController;

// User Access
use App\Http\Controllers\Access\UserController;
use App\Http\Controllers\Access\RoleController;
use App\Http\Controllers\Access\RolePermissionController; // <── Permission Settings

// Settings
use App\Http\Controllers\Settings\WatermarkController;
use App\Http\Controllers\Settings\DocumentAccessSettingController;

// Auth pages
use App\Http\Controllers\authentications\LoginBasic;

// System Framework
use App\Http\Controllers\System\FrameworkController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| - Halaman login dijadikan root ('/') dan hanya bisa diakses guest (guard web).
| - Semua halaman aplikasi wajib auth (guard web).
|
*/

// Pola ID: Dokumen bisa UUID/ULID/INT, User UUID
$DOC_ID_REGEX = '([0-9a-fA-F-]{36}|[0-9A-HJKMNP-TV-Z]{26}|\d+)';
$UUID_REGEX   = '[0-9a-fA-F-]{36}';

/*
|--------------------------------------------------------------------------
| AUTH (guest:web)
|--------------------------------------------------------------------------
*/
Route::middleware('guest:web')->group(function () {
    // root → login
    Route::get('/', [LoginBasic::class, 'index'])->name('login');

    Route::get('/login', [LoginBasic::class, 'index'])->name('login.page');
    Route::post('/login', [LoginBasic::class, 'authenticate'])->name('login.perform');
});

/*
|--------------------------------------------------------------------------
| AUTH (auth:web)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:web')->group(function () use ($DOC_ID_REGEX, $UUID_REGEX) {

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */
    Route::get('/dashboard', [Analytics::class, 'index'])->name('dashboard-analytics');

    /*
    |--------------------------------------------------------------------------
    | MASTER DATA
    |--------------------------------------------------------------------------
    */
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

    /*
    |--------------------------------------------------------------------------
    | NOTIFICATIONS
    |--------------------------------------------------------------------------
    */
    Route::post(
        '/notifications/read-all',
        [DocumentUploadController::class, 'markAllNotificationsRead']
    )->name('notifications.readAll');

    /*
    |--------------------------------------------------------------------------
    | DOCUMENTS
    |--------------------------------------------------------------------------
    */
    Route::prefix('documents')->name('documents.')->group(function () use ($DOC_ID_REGEX, $UUID_REGEX) {

        // Library & Upload
        Route::get('/upload',  [DocumentUploadController::class, 'index'])->name('index');
        Route::post('/upload', [DocumentUploadController::class, 'store'])->name('store');

        // Open PDF (tandai notifikasi, lalu redirect ke gate stream)
        Route::get('/{document}/open', [DocumentUploadController::class, 'open'])
            ->where('document', $DOC_ID_REGEX)
            ->name('open');

        // GATE AKSES: cek approval & masa berlaku
        Route::get('/{document}/file', [DocumentUploadController::class, 'stream'])
            ->where('document', $DOC_ID_REGEX)
            ->name('file');

        // RAW FILE PDF
        Route::get('/{document}/file/raw', [DocumentUploadController::class, 'rawFile'])
            ->where('document', $DOC_ID_REGEX)
            ->name('file.raw');

        // Edit
        Route::get('/{document}/edit', [DocumentUploadController::class, 'edit'])
            ->where('document', $DOC_ID_REGEX)
            ->name('edit');

        // Update
        Route::put('/{document}', [DocumentUploadController::class, 'update'])
            ->where('document', $DOC_ID_REGEX)
            ->name('update');

        // Delete
        Route::delete('/{document}', [DocumentUploadController::class, 'destroy'])
            ->where('document', $DOC_ID_REGEX)
            ->name('destroy');

        // Distribution
        Route::prefix('distribution')->name('distribution.')->group(function () {
            Route::get('/',  [DocumentDistributionController::class, 'index'])->name('index');
            Route::post('/', [DocumentDistributionController::class, 'store'])->name('store');
        });

        // Revisions
        Route::get('/revisions',  [DocumentRevisionController::class, 'index'])->name('revisions.index');
        Route::post('/revisions', [DocumentRevisionController::class, 'store'])->name('revisions.store');

        /*
        |--------------------------------------------------------------------------
        | ACCESS APPROVALS (menu: /documents/access-approvals)
        | route name prefix: documents.approvals.*
        |--------------------------------------------------------------------------
        */
        Route::prefix('access-approvals')->name('approvals.')->group(function () use ($UUID_REGEX) {
            // List request akses dokumen
            Route::get('/', [DocumentAccessApprovalController::class, 'index'])->name('index');
            // → documents.approvals.index

            // Approve request
            Route::put('/{accessRequest}/approve', [DocumentAccessApprovalController::class, 'approve'])
                ->where('accessRequest', $UUID_REGEX)
                ->name('approve');
            // → documents.approvals.approve

            // Reject request
            Route::put('/{accessRequest}/reject', [DocumentAccessApprovalController::class, 'reject'])
                ->where('accessRequest', $UUID_REGEX)
                ->name('reject');
            // → documents.approvals.reject
        });
    });

    /*
    |--------------------------------------------------------------------------
    | USER ACCESS
    |--------------------------------------------------------------------------
    */
    Route::prefix('access')->name('access.')->group(function () use ($UUID_REGEX) {

        // Permission Settings (Role-Permission Matrix)
        Route::get('/permissions',  [RolePermissionController::class, 'index'])->name('permissions.index');
        Route::post('/permissions', [RolePermissionController::class, 'update'])->name('permissions.update');

        // Users
        Route::prefix('users')->name('users.')->group(function () use ($UUID_REGEX) {
            Route::get('/',          [UserController::class, 'index'])->name('index');
            Route::post('/',         [UserController::class, 'store'])->name('store');
            Route::put('/{user}',    [UserController::class, 'update'])
                ->where('user', $UUID_REGEX)->name('update');
            Route::delete('/{user}', [UserController::class, 'destroy'])
                ->where('user', $UUID_REGEX)->name('destroy');
        });

        // Roles
        Route::prefix('roles')->name('roles.')->group(function () use ($UUID_REGEX) {
            Route::get('/',          [RoleController::class, 'index'])->name('index');
            Route::post('/',         [RoleController::class, 'store'])->name('store');
            Route::put('/{role}',    [RoleController::class, 'update'])
                ->where('role', $UUID_REGEX)->name('update');
            Route::delete('/{role}', [RoleController::class, 'destroy'])
                ->where('role', $UUID_REGEX)->name('destroy');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | SETTINGS
    |--------------------------------------------------------------------------
    */
    Route::prefix('settings')->name('settings.')->group(function () {
        // Watermark
        Route::get('/watermark',  [WatermarkController::class, 'index'])->name('watermark');
        Route::post('/watermark', [WatermarkController::class, 'update'])->name('watermark.update');

        // Document Access (pengaturan umum akses dokumen)
        Route::get('/document-access',  [DocumentAccessSettingController::class, 'index'])
            ->name('document-access');

        Route::post('/document-access', [DocumentAccessSettingController::class, 'update'])
            ->name('document-access.update');
    });

    /*
    |--------------------------------------------------------------------------
    | SYSTEM FRAMEWORK
    |--------------------------------------------------------------------------
    */
    Route::prefix('system')->name('system.')->group(function () {
        Route::get('/framework', [FrameworkController::class, 'index'])->name('framework');
    });

    /*
    |--------------------------------------------------------------------------
    | LOGOUT
    |--------------------------------------------------------------------------
    */
    Route::post('/logout', [LoginBasic::class, 'logout'])->name('logout');
});
