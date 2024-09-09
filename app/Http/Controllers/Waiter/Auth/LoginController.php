<?php

namespace App\Http\Controllers\Waiter\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = Auth::user();
            
            // Check if the user role is waiter
            if ($user->role === 'waiter') {
                $token = $user->createToken('waiter-auth-token')->plainTextToken;
                return response()->json(['token' => $token, 'user' => $user, 'status' => 'success']);
            } 
            else {
                return response()->json(['message' => __('Access denied'), 'status' => 'false']);
            }
        }

        return response()->json(['message' => __('Invalid credentials'), 'status' => 'false']);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => __('Logged out'), 'status' => 'success']);
    }
}
