<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dapat_mengakses_rute_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('aplikasi')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/admin/ping')
            ->assertOk()
            ->assertJsonPath('data.role', 'admin');
    }

    public function test_warga_ditolak_403_pada_rute_admin(): void
    {
        $warga = User::factory()->create();
        $token = $warga->createToken('aplikasi')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/admin/ping')
            ->assertStatus(403);
    }

    public function test_tanpa_token_ditolak_401_pada_rute_admin(): void
    {
        $this->getJson('/api/admin/ping')->assertStatus(401);
    }
}
