<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Kamu belum login.',
            ], 401);
        }

        if (!$user->is_active) {
            $user->currentAccessToken()?->delete();

            return response()->json([
                'message' => 'Akun kamu sedang dinonaktifkan. Hubungi Admin.',
            ], 403);
        }

        return $next($request);
    }
}
