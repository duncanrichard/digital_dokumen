<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class MenuAccessService
{
    private array $requirements = [
        '/' => ['dashboard.analytics.view'],
        '/dashboard' => ['dashboard.analytics.view'],
        '/master/jenis-dokumen' => ['master.jenis-dokumen.view'],
        '/master/departments' => ['master.departments.view'],
        '/master/clinics' => ['master.clinics.view'],
        '/documents' => [
            'documents.upload.view',
            'documents.distribution.view',
            'documents.revisions.view',
            'documents.access-approvals.view',
        ],
        '/documents/upload' => ['documents.upload.view'],
        '/documents/distribution' => ['documents.distribution.view'],
        '/documents/access-approvals' => ['documents.access-approvals.view'],
        '/access/users' => ['access.users.view'],
        '/access/roles' => ['access.roles.view'],
        '/access/permissions' => ['access.permissions.view'],
        '/settings/watermark' => ['settings.watermark.view'],
        '/settings/document-access' => ['settings.document-access.view'],
        '/system/framework' => ['system.framework.view'],
    ];

    public function filter(iterable $menus, ?User $user): Collection
    {
        if (!$user) {
            return collect();
        }

        $role = $user->role;
        $isSuperadmin = strcasecmp((string) optional($role)->name, 'Superadmin') === 0;
        $permissions = $isSuperadmin
            ? collect()
            : ($role ? $role->permissions()->pluck('name') : collect());

        return collect($menus)->map(function ($menu) use ($user, $isSuperadmin, $permissions) {
            $copy = clone $menu;

            if (isset($copy->submenu)) {
                $copy->submenu = $this->filterItems($copy->submenu, $isSuperadmin, $permissions)->values()->all();
                return count($copy->submenu) > 0 ? $copy : null;
            }

            return $this->allowed((string) ($copy->url ?? ''), $isSuperadmin, $permissions) ? $copy : null;
        })->filter()->values();
    }

    private function filterItems(iterable $items, bool $isSuperadmin, Collection $permissions): Collection
    {
        return collect($items)->map(function ($item) use ($isSuperadmin, $permissions) {
            $copy = clone $item;

            if (isset($copy->submenu)) {
                $copy->submenu = $this->filterItems($copy->submenu, $isSuperadmin, $permissions)->values()->all();
                return count($copy->submenu) > 0 ? $copy : null;
            }

            return $this->allowed((string) ($copy->url ?? ''), $isSuperadmin, $permissions) ? $copy : null;
        })->filter();
    }

    private function allowed(string $url, bool $isSuperadmin, Collection $permissions): bool
    {
        if ($isSuperadmin || $url === '' || str_starts_with($url, 'javascript:')) {
            return true;
        }

        $required = $this->requirements[$url] ?? [];
        if ($required === []) {
            return true;
        }

        // Beberapa landing page dapat dibuka bila role memiliki salah satu
        // permission fitur di dalam kelompok tersebut.
        return collect($required)->contains(fn ($permission) => $permissions->contains($permission));
    }
}
