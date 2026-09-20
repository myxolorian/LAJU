<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    /**
     * User yang login + seluruh keanggotaan klubnya. FE: 1 klub → auto masuk,
     * lebih dari 1 → club switcher.
     */
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($request->user()->load('clubMembers.club')),
        ]);
    }
}
