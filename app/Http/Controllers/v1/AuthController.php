<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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
        /*if(!auth()->attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => 'Invalid credentials'
            ]);
        } */
    }

    public function logout(Request $request) {
        request()->session()->invalidate();
        request()->session()->regenerateToken();
    }
}
