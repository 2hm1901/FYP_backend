<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Venue;
use App\Models\BankAccount;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'user_type' => 'required|in:renter,owner',
            'account_number' => 'required_if:user_type,owner',
            'bank_name' => 'required_if:user_type,owner',
            'qr_code' => 'required_if:user_type,owner|string',
        ]);

        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'user_type' => $request->user_type,
        ]);

        if ($request->user_type === 'owner') {
            $qrCodeFilename = null;
            if ($request->has('qr_code')) {
                $qrCodeFilename = $this->saveQrCode($request->qr_code);
            }

            BankAccount::create([
                'user_id' => $user->id,
                'account_number' => $request->account_number,
                'bank_name' => $request->bank_name,
                'qr_code' => $qrCodeFilename,
            ]);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user
        ], 201);
    }

    private function saveQrCode($base64Image)
    {
        // Xóa phần header của base64 string
        $image_parts = explode(";base64,", $base64Image);
        $image_base64 = base64_decode($image_parts[1]);

        // Tạo tên file ngẫu nhiên
        $filename = uniqid() . '.png';
        
        // Lưu file vào storage
        Storage::disk('public')->put('qr_codes/' . $filename, $image_base64);

        return $filename;
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
