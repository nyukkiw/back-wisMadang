<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $pengguna = $request->user();

        if (! $pengguna || ! in_array($pengguna->peran, $roles, true)) {
            return response()->json([
                'pesan' => 'Anda tidak memiliki akses ke endpoint ini.',
            ], 403);
        }

        return $next($request);
    }
}