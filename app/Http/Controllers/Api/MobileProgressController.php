<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MobileAccessToken;
use App\Models\User;
use App\Services\Progress\MobileProgressSnapshotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileProgressController extends Controller
{
    public function __invoke(Request $request, MobileProgressSnapshotService $snapshotService): JsonResponse
    {
        $accessToken = $request->attributes->get('mobile_access_token');
        $user = $accessToken instanceof MobileAccessToken ? $accessToken->user : null;

        if (!$user instanceof User) {
            abort(401, 'The mobile session is no longer valid. Sign in again to sync progress.');
        }

        return response()->json($snapshotService->forUser($user));
    }
}
