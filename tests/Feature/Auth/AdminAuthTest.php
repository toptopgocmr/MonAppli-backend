<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\Admin\AdminUser;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    private AdminUser $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'Admin', 'description' => 'Super Admin']);

        $this->admin = AdminUser::create([
            'first_name' => 'Test',
            'last_name'  => 'Admin',
            'email'      => 'admin@test.com',
            'role_id'    => $role->id,
            'password'   => Hash::make('password123'),
            'status'     => 'active',
        ]);
    }

    public function test_admin_can_login_with_valid_credentials(): void
    {
        $response = $this->postJson('/api/admin/auth/login', [
            'email'    => 'admin@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['token', 'admin']);
    }

    public function test_admin_login_fails_with_wrong_password(): void
    {
        $response = $this->postJson('/api/admin/auth/login', [
            'email'    => 'admin@test.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
    }

    public function test_admin_can_get_own_profile(): void
    {
        $token = $this->admin->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/admin/auth/me');

        $response->assertStatus(200)
                 ->assertJsonFragment(['email' => 'admin@test.com']);
    }

    public function test_admin_can_logout(): void
    {
        $token = $this->admin->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/admin/auth/logout');

        $response->assertStatus(200)
                 ->assertJsonFragment(['message' => 'Déconnecté avec succès.']);
    }
}
