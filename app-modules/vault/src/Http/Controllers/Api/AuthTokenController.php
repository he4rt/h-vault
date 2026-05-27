<?php

declare(strict_types=1);

namespace He4rt\Vault\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthTokenController
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:255'],
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(
                ['error' => 'Invalid credentials'],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        $token = $request->user()->createToken($request->string('device_name')->toString());

        return response()->json([
            'token' => $token->plainTextToken,
        ], Response::HTTP_CREATED);
    }
}
