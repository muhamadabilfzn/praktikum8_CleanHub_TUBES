<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLoggedIn
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! session('is_login')) {
            // Request AJAX/JSON mendapat 401 (bukan redirect HTML) agar bisa ditangani oleh JavaScript.
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Sesi login berakhir. Silakan login kembali.'], 401);
            }

            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        return $next($request);
    }
}
