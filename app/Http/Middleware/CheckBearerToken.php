<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckBearerToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        Log::info('entro en el middleware');
        $authHeader = $request->header('Authorization');
        Log::info($authHeader);
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer')) {
            Log::info('Unauthorized por no tener authHeader');
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $token = substr($authHeader, 7);
        $internalToken = '3|'.config('app.internal_bearer_token', '42ccd0251d486204d262cc4b2f6412a53268f238d99d871e91f65457151d89c4');

        if ($token !== $internalToken) {
            Log::info('Unauthorized por no coincidir authHeader');
            Log::info("Token: $token , internalToken: $internalToken");
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        Log::info('Authorized');
        return $next($request);
    }
}
