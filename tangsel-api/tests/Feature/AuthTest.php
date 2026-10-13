<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_membuat_akun_dan_mengembalikan_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'nama' => 'Budi Warga',
            'email' => 'budi@example.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'telepon' => '081234567890',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['id', 'nama', 'email', 'role'], 'token'])
            ->assertJsonPath('data.role', 'warga');

        $this->assertDatabaseHas('users', [
            'email' => 'budi@example.com',
            'role' => 'warga',
        ]);
    }

    public function test_register_dengan_data_tidak_lengkap_gagal_422(): void
    {
        $this->postJson('/api/auth/register', [
            'email' => 'bukan-email',
        ])->assertStatus(422)->assertJsonValidationErrors(['nama', 'email', 'password']);
    }

    public function test_login_lalu_me_mengembalikan_data_pengguna(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $login = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $login->assertOk()->assertJsonStructure(['data', 'token']);

        $this->withToken($login->json('token'))
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_login_admin_lalu_me_mengembalikan_role_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $login = $this->postJson('/api/auth/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $login->assertOk();

        $this->withToken($login->json('token'))
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.role', 'admin');
    }

    public function test_login_dengan_password_salah_gagal_422(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'salah-banget',
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_me_tanpa_token_ditolak_401(): void
    {
        $this->getJson('/api/auth/me')->assertStatus(401);
    }

    public function test_me_dengan_token_tidak_valid_ditolak_401(): void
    {
        $this->withToken('token-palsu')->getJson('/api/auth/me')->assertStatus(401);
    }

    public function test_logout_mencabut_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('aplikasi');
        $tokenId = $token->accessToken->id;

        $this->withToken($token->plainTextToken)
            ->postJson('/api/auth/logout')
            ->assertOk();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);
    }
}
