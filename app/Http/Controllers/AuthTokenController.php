<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class AuthTokenController extends Controller
{
    public function issue(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'abilities' => ['array']
        ]);
        $token = $request->user()->createToken(
            $request->input('name'),
            $request->input('abilities', ['*'])
        );
        return response()->json(['token' => $token->plainTextToken]);
    }

    /**
     * @OA\Get(
     *     path="/api/generate-token",
     *     tags={"Authentication"},
     *     summary="Generate or return existing bearer token",
     *     description="Creates a user (admin@example.com) if not exists, and returns the bearer token. If token already exists, retrieves it from DB.",
     *     @OA\Response(
     *         response=200,
     *         description="Bearer token generated or retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string", example="1|xYzAbcDefGhIjKlMnOpQrStUvWxYz")
     *         )
     *     )
     * )
     */
    public function createBearerToken(): JsonResponse
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['password' => Hash::make('Password')]
        );

        // Look for existing token
        $existingToken = $user->tokens()->where('name', 'admin')->first();

        if ($existingToken && $existingToken->token) {
            return response()->json([
                'token' => $existingToken->token
            ]);
        }
        // Create new token and save plain version
        $newToken = $user->createToken('admin');
        $token = $newToken->token;

        // Store plain token for future retrieval
        $tokenModel = PersonalAccessToken::find($newToken->accessToken->id);
        $tokenModel->token = $token;
        $tokenModel->save();

        return response()->json([
            'token' => $token
        ]);
    }
}
