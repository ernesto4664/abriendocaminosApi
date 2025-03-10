<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class TokenController extends Controller
{
    public function setApiToken()
    {
        $token = env('API_SERVER_HEADER_TOKENS'); // Token de seguridad desde .env
        $minutes = 60 * 24; // 1 día de duración

        return Response::json(['message' => 'Token Set'])
            ->cookie('api_token', $token, $minutes, '/', null, true, true);
            // HttpOnly: true -> No accesible desde JS
            // Secure: true -> Solo en HTTPS (quitar en desarrollo)
    }
}
