<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class UserAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/user/auth/register', [
            'first_name'            => 'Jean',
            'last_name'             => 'Dupont',
            'phone'                 => '+242060000001',
            'country'               => 'Congo',
            'city'                  => 'Brazzaville',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure(['token', 'user']);
    }

    public function test_user_cannot_register_with_duplicate_phone(): void
    {
        User::create([
            'first_name' => 'Jean',
            'last_name'  => 'Dupont',
            'phone'      => '+242060000001',
            'country'    => 'Congo',
            'city'       => 'Brazzaville',
            'password'   => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/user/auth/register', [
            'first_name'            => 'Paul',
            'last_name'             => 'Martin',
            'phone'                 => '+242060000001',
            'country'               => 'Congo',
            'city'                  => 'Brazzaville',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['phone']);
    }

    public function test_user_can_login(): void
    {
        User::create([
            'first_name' => 'Jean',
            'last_name'  => 'Dupont',
            'phone'      => '+242060000002',
            'country'    => 'Congo',
            'city'       => 'Brazzaville',
            'password'   => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/user/auth/login', [
            'phone'    => '+242060000002',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['token', 'user']);
    }
}
