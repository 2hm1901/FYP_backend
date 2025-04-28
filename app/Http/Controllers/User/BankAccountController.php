<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\BankAccount\GetUserBankAccountRequest;
use App\Http\Resources\BankAccount\BankAccountResource;
use App\Services\BankAccount\BankAccountServiceInterface;

class BankAccountController extends Controller
{
    protected $bankAccountService;

    public function __construct(BankAccountServiceInterface $bankAccountService)
    {
        $this->bankAccountService = $bankAccountService;
    }

    /**
     * Lấy thông tin ngân hàng của người dùng
     */
    public function getUserBankAccount($userId, GetUserBankAccountRequest $request)
    {
        try {
            $data = $this->bankAccountService->getUserBankAccount($userId);
            
            if (!$data['bankAccount']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Chưa có thông tin ngân hàng',
                    'bankAccount' => null
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Lấy thông tin ngân hàng thành công',
                'bankAccount' => new BankAccountResource($data['bankAccount'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }
} 