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
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;

/**
 * @OA\Tag(
 *     name="Auth",
 *     description="API xử lý xác thực người dùng"
 * )
 */
class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/register",
     *     summary="Đăng ký tài khoản mới",
     *     description="API đăng ký tài khoản người dùng mới",
     *     operationId="register",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"username", "email", "password", "password_confirmation", "user_type"},
     *             @OA\Property(property="username", type="string", example="johndoe"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="password123"),
     *             @OA\Property(property="user_type", type="string", enum={"player", "owner", "admin"}, example="player"),
     *             @OA\Property(property="phone_number", type="string", example="0912345678")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Đăng ký thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User registered successfully"),
     *             @OA\Property(property="token", type="string", example="1|laravel_sanctum_token")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Dữ liệu không hợp lệ"
     *     )
     * )
     */
    public function register(Request $request)
    {
        $rules = [
            'username' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'user_type' => 'required|in:renter,owner',
        ];

        // Thêm rules cho thông tin ngân hàng nếu là chủ sân
        if ($request->user_type === 'owner') {
            $rules['account_number'] = 'required|string';
            $rules['bank_name'] = 'required|string';
            $rules['qr_code'] = 'required|string';
        }

        $request->validate($rules);

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

    /**
     * @OA\Post(
     *     path="/api/login",
     *     summary="Đăng nhập hệ thống",
     *     description="API đăng nhập vào hệ thống",
     *     operationId="login",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Đăng nhập thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User logged in successfully"),
     *             @OA\Property(property="token", type="string", example="1|laravel_sanctum_token"),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Đăng nhập thất bại",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid credentials")
     *         )
     *     )
     * )
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Sai tài khoản hoặc mật khẩu!'
            ], 401);
        }

        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng xác nhận email trước khi đăng nhập.'
            ], 401);
        }

        $token = $user->createToken($user->username);

        return response()->json([
            'success' => true,
            'user' => $user,
            'token' => $token->plainTextToken
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/logout",
     *     summary="Đăng xuất khỏi hệ thống",
     *     description="API đăng xuất và hủy token hiện tại",
     *     operationId="logout",
     *     tags={"Auth"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Đăng xuất thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User logged out successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Chưa xác thực"
     *     )
     * )
     */
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return [
            'message' => 'You are logged out.' 
        ];
    }

    /**
     * @OA\Get(
     *     path="/api/email/verify/{id}/{hash}",
     *     summary="Xác minh email",
     *     description="API xác minh email của người dùng",
     *     operationId="verifyEmail",
     *     tags={"Auth"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID của người dùng",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="hash",
     *         in="path",
     *         description="Hash xác minh",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Xác minh thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Email verified successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Xác minh thất bại"
     *     )
     * )
     */
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
