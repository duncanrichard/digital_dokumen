<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\Paginator;
use App\Models\Document;
use App\Services\DocumentNotificationBroadcaster;
use Illuminate\Support\Facades\DB;

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

        Document::created(function (Document $document) {
            DB::afterCommit(fn () => app(DocumentNotificationBroadcaster::class)->broadcast($document->fresh()));
        });

        Document::updated(function (Document $document) {
            if ($document->wasChanged(['file_path', 'is_active'])) {
                DB::afterCommit(fn () => app(DocumentNotificationBroadcaster::class)->broadcast($document->fresh()));
            }
        });

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
            $isSuperadmin = strcasecmp((string) optional($user->role)->name, 'Superadmin') === 0;

            // Cache sangat singkat; navbar juga memperbarui data lewat feed realtime.
            $cacheKey = sprintf(
                'notif:%s:%s',
                $user->getAuthIdentifier(),
                $deptId ?: 'all'
            );

            [$notifItems, $notifCount] = Cache::remember($cacheKey, now()->addSeconds(3), function () use ($deptId, $user, $isSuperadmin) {
                $query = Document::query()
                    ->whereDoesntHave('notificationReaders', function ($readers) use ($user) {
                        $readers->where('users.id', $user->id);
                    });

                // Jika user punya department:
                // - dokumen milik departemen tsb, ATAU
                // - dokumen yang terdistribusi ke departemen tsb
                if ($isSuperadmin) {
                    // Superadmin may view all notification items.
                } elseif (!empty($deptId)) {
                    $query->where(function ($q) use ($deptId, $user) {
                        $q->where('department_id', $deptId)
                          ->orWhereHas('distributedDepartments', function ($qq) use ($deptId) {
                              $qq->where('departments.id', $deptId);
                          })
                          ->orWhereHas('distributedUsers', function ($qq) use ($user) {
                              $qq->where('users.id', $user->id);
                          });
                    });
                }
                if (empty($deptId)) {
                    $query->whereHas('distributedUsers', function ($qq) use ($user) {
                        $qq->where('users.id', $user->id);
                    });
                }

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
