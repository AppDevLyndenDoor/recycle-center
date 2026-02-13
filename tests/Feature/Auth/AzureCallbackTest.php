<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AzureCallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_sign_in_via_azure_callback_with_matching_user(): void
    {
        $user = User::factory()->create([
            'email' => 'azure.user@example.com',
        ]);

        $response = $this->postJson('/api/auth/azure/callback', [
            'code' => $this->fakeJwt(['preferred_username' => $user->email]),
        ]);

        $response->assertOk()->assertJson(['ok' => true]);
        $this->assertAuthenticatedAs($user);
    }

    public function test_azure_callback_returns_validation_error_when_user_does_not_exist(): void
    {
        $response = $this->postJson('/api/auth/azure/callback', [
            'code' => $this->fakeJwt(['preferred_username' => 'missing@example.com']),
        ]);

        $response->assertStatus(422);
    }

    private function fakeJwt(array $claims): string
    {
        $header = rtrim(strtr(base64_encode(json_encode(['alg' => 'none', 'typ' => 'JWT'])), '+/', '-_'), '=');
        $payload = rtrim(strtr(base64_encode(json_encode($claims)), '+/', '-_'), '=');

        return $header.'.'.$payload.'.signature';
    }
}
