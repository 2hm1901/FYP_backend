<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $fields = $request->validate([
            'username' => 'required|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed',
            'user_type' => 'required|in:renter,owner',
        ]);

        $user = User::create($fields);

        $user->sendEmailVerificationNotification();

        return [
            'user' => $user,
            'message' => 'Đăng ký thành công! Vui lòng kiểm tra email để xác thực tài khoản.',
        ];
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return [
                'errors' => [
                    'email' => ['The provided credentials are incorrect.']
                ]
            ];
            // return [
            //     'message' => 'The provided credentials are incorrect.' 
            // ];
        }

        if (!$user->hasVerifiedEmail()) {
            return [
                'errors' => [
                    'email' => ['Vui lòng xác nhận email trước khi đăng nhập.']
                ]
            ];
        }

        $token = $user->createToken($user->username);

        return [
            'user' => $user,
            'token' => $token->plainTextToken
        ];
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return [
            'message' => 'You are logged out.' 
        ];
    }
    public function verifyEmail(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

    if (!hash_equals((string) $hash, sha1($user->email))) {
        return [
            'errors' => ['message' => 'Liên kết không hợp lệ']
        ];
    }

    if ($user->hasVerifiedEmail()) {
        return [
            'message' => 'Email đã được xác nhận'
        ];
    }

    $user->markEmailAsVerified();
    $user->save();

    $token = $user->createToken($user->username)->plainTextToken;

    return [
        'message' => 'Email đã được xác nhận thành công',
        'token' => $token,
        'user' => $user
    ];
    }
    
}
