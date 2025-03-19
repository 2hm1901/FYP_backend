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
            'phone_number' => [
                'nullable',
                'string',
                'max:15',
                'regex:/^(03|05|07|08|09)[0-9]{8}$/' // Validate số điện thoại VN
            ],
            'skill_level' => 'nullable|string|max:255',
            'avatar' => 'nullable|string|regex:/^data:image\/[a-z]+;base64,/',
        ]);

        // Chỉ xử lý avatar nếu có dữ liệu mới được gửi lên
        if ($request->filled('avatar')) {
            try {
                $avatarUrl = $this->saveAvatar($request->avatar);
                $validatedData['avatar'] = $avatarUrl;
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to save avatar: ' . $e->getMessage(),
                ], 400);
            }
        } else {
            unset($validatedData['avatar']);
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

        $avatarFilename = uniqid() . '.' . $extension;
        $avatarPath = 'avatars/' . $avatarFilename;
        Storage::disk('public')->put($avatarPath, $avatar);

        // Xóa ảnh cũ nếu có
        $user = Auth::user();
        if ($user->avatar && Storage::disk('public')->exists('avatars/' . basename($user->avatar))) {
            Storage::disk('public')->delete('avatars/' . basename($user->avatar));
        }

        return $avatarFilename;
    }
}