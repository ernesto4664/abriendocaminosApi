<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class Cors
{
    public function handle(Request $request, Closure $next)
    {
       // Log::info('Middleware CORS ejecutándose', ['origin' => $request->header('Origin')]);

        $allowedOrigins = ['http://localhost:4200'];
        $origin = $request->header('Origin');

        if (!in_array($origin, $allowedOrigins)) {
            return response()->json(['message' => 'Origen no permitido'], 403);
        }

        $headers = [
            'Access-Control-Allow-Origin' => $origin, // ✅ Permitir solo orígenes específicos
            'Access-Control-Allow-Credentials' => 'true', // ✅ Permitir envío de cookies
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With, Api-Token, Set-Cookie',
            'Access-Control-Expose-Headers' => 'Authorization, Set-Cookie', // ✅ Exponer headers en el navegador
        ];

        if ($request->isMethod('OPTIONS')) {
            return response()->json('CORS Preflight OK', 200, $headers);
        }

        $response = $next($request);

        // 🔹 Asegurar que las cookies se envíen correctamente
        $response->headers->set('Set-Cookie', 'auth_token=BASEAPISYSTEMDocker; Path=/; HttpOnly; SameSite=None; Secure');

        foreach ($headers as $key => $value) {
            $response->header($key, $value);
        }

        return $response;
    }
}
