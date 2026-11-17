<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProvince;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'instansi', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'moderator', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'warga', 'guard_name' => 'web']);
    }

    public function test_admin_can_access_user_resource(): void
    {
        $admin = User::factory()->create([
            'is_active' => true,
        ]);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertSuccessful();
    }

    public function test_non_admin_cannot_access_user_resource(): void
    {
        $warga = User::factory()->create([
            'is_active' => true,
        ]);
        $warga->assignRole('warga');

        $this->actingAs($warga)
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_inactive_user_cannot_access_filament_panel(): void
    {
        $inactive = User::factory()->create([
            'is_active' => false,
        ]);
        $inactive->assignRole('admin');

        $this->actingAs($inactive)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_admin_can_create_active_user(): void
    {
        $user = User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'budi@example.com',
            'is_active' => 1,
        ]);
    }

    public function test_admin_can_deactivate_user(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->update(['is_active' => false]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_active' => 0,
        ]);
    }

    public function test_province_assignment_saved_for_instansi_user(): void
    {
        $instansiUser = User::factory()->create(['is_active' => true]);
        $instansiUser->assignRole('instansi');

        $provinces = ['Jawa Barat', 'DKI Jakarta'];
        foreach ($provinces as $prov) {
            $instansiUser->provinces()->create(['province' => $prov]);
        }

        $this->assertDatabaseHas('user_provinces', [
            'user_id' => $instansiUser->id,
            'province' => 'Jawa Barat',
        ]);
        $this->assertDatabaseHas('user_provinces', [
            'user_id' => $instansiUser->id,
            'province' => 'DKI Jakarta',
        ]);
        $this->assertCount(2, $instansiUser->fresh()->provinces);
    }
}
