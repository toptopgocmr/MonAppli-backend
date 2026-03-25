<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\Driver\Driver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class DriverAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_can_register(): void
    {
        $response = $this->postJson('/api/driver/auth/register', [
            'first_name'            => 'Pierre',
            'last_name'             => 'Chauffeur',
            'birth_date'            => '1990-01-01',
            'birth_place'           => 'Brazzaville',
            'country_birth'         => 'Congo',
            'phone'                 => '+242070000001',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure(['token', 'driver']);

        $this->assertDatabaseHas('wallets', ['driver_id' => $response->json('driver.id')]);
    }

    public function test_driver_can_login(): void
    {
        $driver = Driver::create([
            'first_name'    => 'Pierre',
            'last_name'     => 'Chauffeur',
            'birth_date'    => '1990-01-01',
            'birth_place'   => 'Brazzaville',
            'country_birth' => 'Congo',
            'phone'         => '+242070000002',
            'password'      => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/driver/auth/login', [
            'phone'    => '+242070000002',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)->assertJsonStructure(['token', 'driver']);
    }
}
