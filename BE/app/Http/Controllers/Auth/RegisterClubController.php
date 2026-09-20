<?php

namespace App\Http\Controllers\Auth;

use App\Actions\RegisterClub;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterClubRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;

class RegisterClubController extends Controller
{
    public function __invoke(RegisterClubRequest $request, RegisterClub $registerClub): JsonResponse
    {
        ['user' => $user] = $registerClub($request->validated());

        $token = $user->createToken($request->input('device_name', 'web'))->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user->load('clubMembers.club')),
        ], 201);
    }
}
