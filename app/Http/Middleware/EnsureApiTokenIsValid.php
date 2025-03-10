<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cookie;

class EnsureApiTokenIsValid
{
    public function handle(Request $request, Closure $next)
    {
        // Intentar obtener el token del header o de la cookie
        $apiToken = $request->header('Api-Token') ?? $request->cookie('auth_token');
    
        if (!$apiToken || $apiToken !== env('API_SERVER_HEADER_TOKENS')) {
           /* Log::warning('Acceso no autorizado... Token de API ausente o no válido.', [
                'Api-Token Enviado' => $apiToken
            ]);*/
            return response()->json(['message' => 'Unauthorized. Invalid API token.'], 401);
        }
    
        // 🔹 Configurar la cookie correctamente
        $secure = app()->environment('local') ? false : true;
    
        $response = $next($request);
        return $response->cookie(
            'auth_token', $apiToken, 60, '/', 'localhost', false, true
        );
        
    }
    
    
}

