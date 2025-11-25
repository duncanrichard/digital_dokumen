<?php

use Illuminate\Support\Facades\Auth;

if (! function_exists('user_roles')) {
    /**
     * Ambil list nama role user saat ini.
     * Sesuaikan dengan struktur User/Role di project kamu.
     */
    function user_roles(): array
    {
        $user = Auth::user();

        if (! $user) {
            return [];
        }

        // CASE 1: Kalau User punya relasi roles() (many to many)
        if (method_exists($user, 'roles')) {
            return $user->roles->pluck('name')->map(fn ($r) => strtolower($r))->all();
        }

        // CASE 2: Kalau User punya field 'role' biasa
        if (isset($user->role)) {
            return [strtolower($user->role)];
        }

        return [];
    }
}

if (! function_exists('filterMenuByRole')) {
    /**
     * Filter menu berdasarkan role user.
     *
     * @param  array  $menu
     * @param  array  $roles  list nama role lowercase
     * @return array
     */
    function filterMenuByRole(array $menu, array $roles): array
    {
        $filtered = [];
        $isSuperAdmin = in_array('superadmin', $roles, true);

        foreach ($menu as $item) {
            $slug = $item['slug'] ?? null;

            // ---------------------------
            // BATASAN MENU UNTUK NON-SUPERADMIN
            // ---------------------------

            // Sembunyikan Master Data utk non-superadmin
            if ($slug === 'master-data' && ! $isSuperAdmin) {
                continue;
            }

            // (Opsional) Sembunyikan User Access utk non-superadmin
            if ($slug === 'user-access' && ! $isSuperAdmin) {
                continue;
            }

            // (Opsional) Sembunyikan Settings utk non-superadmin
            if ($slug === 'settings' && ! $isSuperAdmin) {
                continue;
            }

            // ---------------------------
            // SUPPOR "roles" DI JSON
            // ---------------------------
            // Kalau di JSON kamu isi: "roles": ["superadmin", "manager"]
            if (isset($item['roles']) && is_array($item['roles']) && ! empty($item['roles'])) {
                $allowedRoles = array_map('strtolower', $item['roles']);

                // Kalau tidak ada irisan role user dengan roles menu → hide
                if (empty(array_intersect($roles, $allowedRoles))) {
                    continue;
                }
            }

            // Kalau punya submenu, filter juga submenu-nya
            if (isset($item['submenu']) && is_array($item['submenu'])) {
                $item['submenu'] = filterMenuByRole($item['submenu'], $roles);

                // Kalau setelah difilter, submenu kosong → jangan tampilkan menu utama
                if (empty($item['submenu'])) {
                    continue;
                }
            }

            $filtered[] = $item;
        }

        return $filtered;
    }
}

if (! function_exists('getMenuData')) {
    /**
     * Dibaca di layout Materio untuk render sidebar.
     *
     * @return array
     */
    function getMenuData(): array
    {
        // Baca file JSON yang kamu kirim tadi
        $jsonPath = resource_path('menu/verticalMenu.json');

        if (! file_exists($jsonPath)) {
            return [];
        }

        $json = file_get_contents($jsonPath);
        $data = json_decode($json, true);

        $menu = $data['menu'] ?? [];

        $roles = user_roles();

        return filterMenuByRole($menu, $roles);
    }
}
