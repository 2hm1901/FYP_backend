<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ForgotPasswordController extends Controller
{
    public function forgot(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['errors' => ['email' => ['Email không tồn tại.']]], 422);
        }

        if (!$user->hasVerifiedEmail()) {
            return response()->json(['errors' => ['email' => ['Email chưa được xác thực.']]], 422);
        }

        try {
            $status = Password::sendResetLink(['email' => $request->email]);

            if ($status === Password::RESET_LINK_SENT) {
                return response()->json(['message' => 'Liên kết đặt lại mật khẩu đã được gửi đến email của bạn.']);
            }
            return response()->json(['errors' => ['email' => ['Không thể gửi liên kết đặt lại mật khẩu. Status: ' . $status]]], 422);
        } catch (\Exception $e) {
            return response()->json(['errors' => ['email' => ['Lỗi hệ thống khi gửi liên kết đặt lại mật khẩu.']]], 500);
        }
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email|exists:users',
            'password' => 'required|confirmed|min:8',
        ]);

        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => bcrypt($password),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Mật khẩu đã được đặt lại thành công.']);
        }

        return response()->json(['errors' => ['token' => ['Token không hợp lệ hoặc đã hết hạn.']]], 422);
    }
}