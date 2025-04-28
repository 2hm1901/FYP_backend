<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\BankAccount\GetUserBankAccountRequest;
use App\Http\Resources\BankAccount\BankAccountResource;
use App\Services\BankAccount\BankAccountServiceInterface;

/**
 * @OA\Tag(
 *     name="BankAccount",
 *     description="Quản lý thông tin ngân hàng của người dùng"
 * )
 */
class BankAccountController extends Controller
{
    protected $bankAccountService;

    public function __construct(BankAccountServiceInterface $bankAccountService)
    {
        $this->bankAccountService = $bankAccountService;
    }

    /**
     * Lấy thông tin ngân hàng của người dùng
     * 
     * @OA\Get(
     *     path="/api/bank-account/{userId}",
     *     summary="Lấy thông tin ngân hàng của người dùng",
     *     description="Lấy thông tin ngân hàng dựa vào ID người dùng",
     *     operationId="getUserBankAccount",
     *     tags={"BankAccount"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         description="ID của người dùng",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lấy thông tin thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Lấy thông tin ngân hàng thành công"),
     *             @OA\Property(property="bankAccount", type="object", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy người dùng",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Người dùng không tồn tại")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi server"
     *     )
     * )
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