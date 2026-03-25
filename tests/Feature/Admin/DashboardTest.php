<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\Admin\AdminUser;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private AdminUser $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $role = Role::create(['name' => 'Admin', 'description' => 'Super Admin']);
        $this->admin = AdminUser::create([
            'first_name' => 'Admin',
            'last_name'  => 'Test',
            'email'      => 'admin@test.com',
            'role_id'    => $role->id,
            'password'   => Hash::make('password'),
            'status'     => 'active',
        ]);
    }

    public function test_admin_can_access_dashboard(): void
    {
        $token = $this->admin->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/admin/dashboard');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'clients', 'drivers', 'trips', 'payments', 'sos_active',
                 ]);
    }

    public function test_unauthenticated_cannot_access_dashboard(): void
    {
        $response = $this->getJson('/api/admin/dashboard');
        $response->assertStatus(401);
    }
}
