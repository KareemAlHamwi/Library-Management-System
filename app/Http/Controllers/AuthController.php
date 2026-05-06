<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{



    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        $user = Auth::user();


        // if (!$user->hasVerifiedEmail()) {
        //     return response()->json([
        //         'message' => 'Email not verified'
        //     ], 403);
        // }
        //
        // if ($user->tokens()->count() > 0) {
        //     return response()->json([
        //         'message' => 'User already logged in on another device'
        //     ], 403);
        // }
        //$user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user
        ]);
    }


    ///////////////////////////////////
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }
}
