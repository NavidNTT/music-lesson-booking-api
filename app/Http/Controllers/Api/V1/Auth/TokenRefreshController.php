<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TokenRefreshController extends Controller
{
    use ApiResponse;

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentToken = $request->user()->currentAccessToken();

        if (! $currentToken) {
            return $this->error('No active token found.', 401);
        }

        $tokenAge = $currentToken->created_at->diffInDays(now());
        $maxRefreshDays = config('sanctum.refresh_window_days', 7);

        if ($tokenAge > $maxRefreshDays) {
            $currentToken->delete();
            return $this->error('Token expired beyond refresh window. Please log in again.', 401);
        }

        $deviceName = $currentToken->name;
        $currentToken->delete();
        $newToken = $user->createToken($deviceName)->plainTextToken;

        return $this->success(
            data: [
                'token' => $newToken,
                'token_type' => 'Bearer',
            ],
            message: 'Token refreshed successfully.'
        );
    }
}