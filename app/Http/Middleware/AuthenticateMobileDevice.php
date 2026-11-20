<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\MobileAccessToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMobileDevice
{
    public function handle(Request $request, Closure $next): Response
    {
        $plainTextToken = $request->bearerToken();

        if (! is_string($plainTextToken) || $plainTextToken === '') {
            return response()->json(['message' => 'A valid mobile access token is required.'], 401);
        }

        $accessToken = MobileAccessToken::query()
            ->with('user')
            ->where('token_hash', hash('sha256', $plainTextToken))
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->first();

        if ($accessToken === null || $accessToken->user === null) {
            return response()->json(['message' => 'The mobile session expired. Sign in again to sync.'], 401);
        }

        $accessToken->forceFill(['last_used_at' => now()])->save();
        $request->setUserResolver(fn () => $accessToken->user);
        $request->attributes->set('mobile_access_token', $accessToken);

        return $next($request);
    }
}
