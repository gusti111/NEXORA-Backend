<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
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

        if (!in_array($user->role, $roles)) {
            return response()->json([
                'message' => 'Kamu tidak memiliki akses ke fitur ini.',
            ], 403);
        }

        return $next($request);
    }
}
