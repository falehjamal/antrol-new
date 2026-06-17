<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $username = $request->header('x-username');
        $password = $request->header('x-password');

        if (! isset($username) || ! isset($password)) {
            return ResponseFormatter::error([], 'Username dan password harus diisi.', 201);
        }

        $credentials = [
            'username' => $username,
            'password' => $password,
        ];

        if (! $token = Auth::guard('api')->attempt($credentials)) {
            return ResponseFormatter::error([], 'Username atau Password Tidak Sesuai', 201);
        }

        return ResponseFormatter::success([
            'token' => 'Bearer '.$token,
        ], 'Ok');
    }
}
