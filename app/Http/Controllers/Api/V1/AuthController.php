<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RefreshRequest;
use App\Services\Api\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    /**
     * POST /api/v1/auth/login
     *
     * Valide les identifiants et retourne une paire de tokens (access + refresh).
     * Retourne 401 générique en cas d'échec (ne révèle pas quel champ est invalide).
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (! Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Les identifiants fournis sont incorrects.',
            ], 401);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $tokenPair = $this->authService->issueTokenPair($user);

        return response()->json([
            'data' => [
                'access_token'  => $tokenPair['access_token'],
                'refresh_token' => $tokenPair['refresh_token'],
                'expires_in'    => $tokenPair['expires_in'],
                'token_type'    => 'Bearer',
            ],
        ]);
    }

    /**
     * POST /api/v1/auth/refresh
     *
     * Valide le refresh token (ability `refresh`), émet un nouveau access token.
     */
    public function refresh(RefreshRequest $request): JsonResponse
    {
        $tokenValue = $request->input('refresh_token');

        // Trouver le token dans personal_access_tokens via son hash
        $token = PersonalAccessToken::findToken($tokenValue);

        // Vérifier que le token existe, qu'il a l'ability "refresh",
        // et qu'il n'est pas expiré
        if (
            $token === null
            || ! $token->can('refresh')
            || ($token->expires_at !== null && $token->expires_at->isPast())
        ) {
            return response()->json([
                'message' => 'Le token de rafraîchissement est invalide ou expiré.',
                'errors'  => ['refresh_token' => ['refresh_token_invalid']],
            ], 401);
        }

        /** @var \App\Models\User $user */
        $user = $token->tokenable;

        $result = $this->authService->refreshAccessToken($user);

        return response()->json([
            'data' => [
                'access_token' => $result['access_token'],
                'expires_in'   => $result['expires_in'],
                'token_type'   => 'Bearer',
            ],
        ]);
    }

    /**
     * POST /api/v1/auth/logout
     *
     * Révoque le token d'accès courant et retourne 204 No Content.
     */
    public function logout(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $this->authService->revokeAccessToken($user);

        return response()->json(null, 204);
    }

    /**
     * GET /api/v1/auth/me
     *
     * Retourne les informations de l'utilisateur authentifié.
     */
    public function me(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        return response()->json([
            'data' => [
                'id'         => $user->id,
                'nom'        => $user->nom,
                'prenom'     => $user->prenom,
                'email'      => $user->email,
                'role'       => $user->role_cache,
                'role_label' => $user->role_label,
            ],
        ]);
    }
}
