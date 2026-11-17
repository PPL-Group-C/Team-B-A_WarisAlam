<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $roles = ['warga', 'moderator', 'instansi', 'admin'];
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        $admin = User::firstOrCreate(
            ['email' => 'admin@warisalam.test'],
            [
                'name' => 'Admin WarisAlam',
                'password' => 'password',
                'is_active' => true,
            ]
        );
        if (! $admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }
    }
}
