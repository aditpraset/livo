<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * Buat 3 role (admin, tutor, siswa) dan sinkronkan role spatie
     * untuk semua user yang sudah ada berdasarkan kolom `role`.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['admin', 'tutor', 'siswa'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        User::all()->each(fn (User $user) => $user->syncRoleFromColumn());
    }
}
