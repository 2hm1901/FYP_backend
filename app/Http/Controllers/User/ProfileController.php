<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\User;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        $user = Auth::user();

        $validatedData = $request->validate([
            'username' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'phone_number' => 'nullable|string|max:15',
            'skill_level' => 'nullable|string|max:255',
            'avatar' => 'nullable|string|regex:/^data:image\/[a-z]+;base64,/',
        ]);

        if ($request->has('avatar')) {
            try {
                $avatarUrl = $this->saveAvatar($request->avatar); // Lưu URL đầy đủ
                $validatedData['avatar'] = $avatarUrl; // Gán URL đầy đủ vào validatedData
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to save avatar: ' . $e->getMessage(),
                ], 400);
            }
        }

        $user->update($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $user,
        ]);
    }

    private function saveAvatar($avatarBase64)
    {
        if (!preg_match('#^data:image/\w+;base64,#i', $avatarBase64)) {
            throw new \Exception('Invalid base64 image data');
        }

        $mime = explode(';', $avatarBase64)[0];
        $extension = explode('/', $mime)[1];
        $avatar = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $avatarBase64));

        if ($avatar === false) {
            throw new \Exception('Failed to decode base64 image');
        }

        $avatarPath = 'avatars/' . uniqid() . '.' . $extension;
        Storage::disk('public')->put($avatarPath, $avatar);

        // Xóa ảnh cũ nếu có
        $user = Auth::user();
        if ($user->avatar && Storage::disk('public')->exists(basename($user->avatar))) {
            Storage::disk('public')->delete(basename($user->avatar));
        }

        // Trả về URL đầy đủ
        return Storage::url($avatarPath); // Ví dụ: /storage/avatars/xxx.png
    }
}