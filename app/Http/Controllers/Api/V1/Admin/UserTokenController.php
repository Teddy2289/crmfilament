<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserTokenController extends ApiController
{
    /**
     * DELETE /api/v1/admin/users/{user}/tokens
     *
     * Revokes all Sanctum tokens for the given user.
     * Restricted to roles: super_admin, administrateur (enforced by route middleware).
     *
     * Requirements: 15.3
     */
    public function revokeAll(Request $request, User $user): JsonResponse
    {
        $user->tokens()->delete();

        return response()->json(null, 204);
    }
}
