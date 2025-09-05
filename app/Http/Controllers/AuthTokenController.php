<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
}
