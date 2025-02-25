<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;


class EnsureApiTokenIsValid
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $apiToken = $request->header('Api-Token');
    
        // Log para verificar si el encabezado se está recibiendo y la variable de entorno
       /* Log::info('Verificando Api-Token en la solicitud', [
            'Api-Token Enviado' => $apiToken,
            'Api-Token Esperado' => env('API_SERVER_HEADER_TOKENS'),
        ]);*/
    
        // Validar el token usando la variable de entorno
        if (!$apiToken || $apiToken !== env('API_SERVER_HEADER_TOKENS')) {
            Log::warning('Acceso no autorizado... Token de API ausente o no válido.', [
                'Api-Token Enviado' => $apiToken
            ]);
            return response()->json(['message' => 'Unauthorized. Invalid API token.'], 401);
        }
    
        return $next($request);
    }
}
