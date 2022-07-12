<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller {

    public function login(Request $request) {
        $data= $request->all();
        $auth = auth()->attempt($request->only('email', 'password'));

        if ($auth) {
            $currentUser = auth()->user();
            if ($currentUser->status == 2 || $currentUser->status == 3) {
                throw ValidationException::withMessages([
                    'email' => 'Invalid credentials'
                ]);
            }
        }else{
            throw ValidationException::withMessages([
                'email' => 'Invalid credentials'
            ]);
        }

        // return response()->json(['access_token' => $token, 'token_type' => 'Bearer', ]);
        /*if(!auth()->attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => 'Invalid credentials'
            ]);
        } */
    }

    public function appLogin(Request $request) {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'required',
        ]);

        // $user = User::with('resident')->where('email', $request->email)->first();
        $user = User::with('resident')->where([
            ['email', $request->email],
            ['role', 'user'],
        ])->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials'],
            ]);
        }

        $token = $user->createToken($request->device_name)->plainTextToken;
        $user['access_token'] = $token;

        return response()->json(['user' => $user]);
    }

    public function appLogout(Request $request) {
        $user = User::where('id', $request->id)->first();
        $user->tokens()->delete();
        // $request->user()->currentAccessToken()->delete();

        return response()->json(['success' => true]);
    }

    public function logout(Request $request) {
        request()->session()->invalidate();
        request()->session()->regenerateToken();
    }
}
