<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MobileAccessToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class MobileSessionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:120'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();

        if ($user === null || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The supplied credentials are incorrect.'],
            ]);
        }

        MobileAccessToken::query()
            ->where('user_id', $user->id)
            ->where('device_name', $validated['device_name'])
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        $plainTextToken = bin2hex(random_bytes(32));
        $expiresAt = now()->addDays(90);

        MobileAccessToken::query()->create([
            'user_id' => $user->id,
            'device_name' => $validated['device_name'],
            'token_hash' => hash('sha256', $plainTextToken),
            'last_used_at' => now(),
            'expires_at' => $expiresAt,
        ]);

        return response()->json([
            'token' => $plainTextToken,
            'expires_at' => $expiresAt->toIso8601String(),
            'user_name' => $user->name,
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->attributes->get('mobile_access_token')?->forceFill(['revoked_at' => now()])->save();

        return response()->json(['message' => 'Mobile session signed out.']);
    }
}
