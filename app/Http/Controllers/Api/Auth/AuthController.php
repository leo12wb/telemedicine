<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * AuthController — placeholder da Fase 4.
 *
 * A implementação completa será feita na Fase 5 (módulo Auth),
 * incluindo Form Requests, AuthService e respostas via API Resources.
 */
class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Módulo Auth será implementado na Fase 5.'], 501);
    }

    public function login(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Módulo Auth será implementado na Fase 5.'], 501);
    }

    public function logout(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Módulo Auth será implementado na Fase 5.'], 501);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Módulo Auth será implementado na Fase 5.'], 501);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Módulo Auth será implementado na Fase 5.'], 501);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Módulo Auth será implementado na Fase 5.'], 501);
    }
}
