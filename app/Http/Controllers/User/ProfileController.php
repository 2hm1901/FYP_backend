<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\BankAccount;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'username' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'phone_number' => 'nullable|string|max:20',
            'skill_level' => 'nullable|string|max:50',
            'avatar' => 'nullable|string',
            'account_number' => $user->user_type === 'owner' ? 'required|string' : 'nullable|string',
            'bank_name' => $user->user_type === 'owner' ? 'required|string' : 'nullable|string',
            'qr_code' => 'nullable|string',
        ]);

        try {
            // Xử lý avatar nếu có
            if ($request->has('avatar') && $request->avatar !== null) {
                $avatarFilename = $this->saveAvatar($request->avatar);
                $user->avatar = $avatarFilename;
            }

            $user->username = $request->username;
            $user->email = $request->email;
            $user->phone_number = $request->phone_number;
            $user->skill_level = $request->skill_level;
            $user->save();

            // Xử lý thông tin ngân hàng nếu là owner
            if ($user->user_type === 'owner') {
                $bankAccount = BankAccount::where('user_id', $user->id)->first();
                
                if (!$bankAccount) {
                    $bankAccount = new BankAccount();
                    $bankAccount->user_id = $user->id;
                }

                $bankAccount->account_number = $request->account_number;
                $bankAccount->bank_name = $request->bank_name;

                // Xử lý QR code nếu có
                if ($request->has('qr_code') && $request->qr_code !== null) {
                    // Xóa QR code cũ nếu có
                    if ($bankAccount->qr_code) {
                        Storage::disk('public')->delete('qr_codes/' . $bankAccount->qr_code);
                    }
                    $bankAccount->qr_code = $this->saveQrCode($request->qr_code);
                }

                $bankAccount->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'user' => $user->load('bankAccount')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile: ' . $e->getMessage()
            ], 500);
        }
    }

    private function saveAvatar($base64Image)
    {
        // Xóa avatar cũ nếu có
        if (auth()->user()->avatar) {
            Storage::disk('public')->delete('avatars/' . auth()->user()->avatar);
        }

        // Xóa phần header của base64 string
        $image_parts = explode(";base64,", $base64Image);
        $image_base64 = base64_decode($image_parts[1]);

        // Tạo tên file ngẫu nhiên
        $filename = uniqid() . '.png';
        
        // Lưu file vào storage
        Storage::disk('public')->put('avatars/' . $filename, $image_base64);

        return $filename;
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
}