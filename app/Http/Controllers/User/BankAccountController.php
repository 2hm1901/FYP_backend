<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BankAccount;
use App\Models\User;

class BankAccountController extends Controller
{
    /**
     * Lấy thông tin ngân hàng của người dùng
     */
    public function getUserBankAccount($userId)
    {
        try {
            $user = User::find($userId);
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Người dùng không tồn tại'
                ], 404);
            }
            
            $bankAccount = BankAccount::where('user_id', $userId)->first();
            
            if (!$bankAccount) {
                return response()->json([
                    'success' => true,
                    'message' => 'Chưa có thông tin ngân hàng',
                    'bankAccount' => null
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Lấy thông tin ngân hàng thành công',
                'bankAccount' => $bankAccount
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }
} 