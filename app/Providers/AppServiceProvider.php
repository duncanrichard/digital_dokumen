<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\Paginator;
use App\Models\Document;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Pastikan pagination Laravel memakai Bootstrap 5 (cocok dengan Materio)
        Paginator::useBootstrapFive();

        // Supply data notifikasi ke semua view (bisa dibatasi ke layout tertentu jika perlu)
        View::composer('*', function ($view) {
            // Saat proses awal (sebelum migrate), hindari query error jika tabel belum ada
            if (!Schema::hasTable('documents')) {
                $view->with([
                    'notifItems' => collect(),
                    'notifCount' => 0,
                ]);
                return;
            }

            // Guest: kosongkan notifikasi
            if (!Auth::check()) {
                $view->with([
                    'notifItems' => collect(),
                    'notifCount' => 0,
                ]);
                return;
            }

            $user = Auth::user();
            $deptId = $user->department_id; // bisa null

            // Cache singkat biar ringan; akan invalid saat flag read_notifikasi berubah,
            // jadi TTL rendah (mis. 15 detik) cukup aman.
            $cacheKey = sprintf(
                'notif:%s:%s',
                $user->getAuthIdentifier(),
                $deptId ?: 'all'
            );

            [$notifItems, $notifCount] = Cache::remember($cacheKey, now()->addSeconds(15), function () use ($deptId) {
                // Base: hanya notifikasi yang belum dibaca
                $query = Document::query()
                    ->where('read_notifikasi', false);

                // Jika user punya department:
                // - dokumen milik departemen tsb, ATAU
                // - dokumen yang terdistribusi ke departemen tsb
                if (!empty($deptId)) {
                    $query->where(function ($q) use ($deptId) {
                        $q->where('department_id', $deptId)
                          ->orWhereHas('distributedDepartments', function ($qq) use ($deptId) {
                              $qq->where('departments.id', $deptId);
                          });
                    });
                }
                // Jika user TIDAK punya department → jangan difilter (tampilkan semua)

                $items = (clone $query)
                    ->orderByDesc('created_at')
                    ->limit(10)
                    ->get(['id', 'name', 'document_number', 'revision', 'created_at']);

                $count = (clone $query)->count();

                return [$items, $count];
            });

            $view->with(compact('notifItems', 'notifCount'));
        });
    }
}
