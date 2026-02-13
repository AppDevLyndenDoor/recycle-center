<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AzureCallbackController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $claims = $this->extractJwtClaims($validated['code']);
        $email = $claims['preferred_username']
            ?? $claims['upn']
            ?? $claims['email']
            ?? $claims['unique_name']
            ?? null;
        $email = is_string($email) ? strtolower(trim($email)) : null;

        if (! is_string($email) || $email === '') {
            $claimKeys = implode(', ', array_keys($claims));

            throw ValidationException::withMessages([
                'code' => 'Unable to determine a Microsoft account email from the token. Claims: '.($claimKeys ?: 'none'),
            ]);
        }

        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => "No local account exists for this Microsoft user ({$email}).",
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(['ok' => true]);
    }

    /**
     * Parse claims from a JWT-like token payload.
     *
     * Note: signature verification is not performed here.
     *
     * @return array<string, mixed>
     */
    private function extractJwtClaims(string $jwt): array
    {
        $parts = explode('.', $jwt);

        if (count($parts) < 2) {
            return [];
        }

        $payload = strtr($parts[1], '-_', '+/');
        $payload .= str_repeat('=', (4 - strlen($payload) % 4) % 4);

        $decoded = base64_decode($payload, true);

        if ($decoded === false) {
            return [];
        }

        $claims = json_decode($decoded, true);

        return is_array($claims) ? $claims : [];
    }
}
