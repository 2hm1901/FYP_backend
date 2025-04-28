<?php

namespace App\Services\BankAccount;

use App\Repositories\BankAccount\BankAccountRepositoryInterface;
use App\Repositories\User\UserRepositoryInterface;

class BankAccountService implements BankAccountServiceInterface
{
    protected $bankAccountRepository;
    protected $userRepository;

    public function __construct(
        BankAccountRepositoryInterface $bankAccountRepository,
        UserRepositoryInterface $userRepository
    ) {
        $this->bankAccountRepository = $bankAccountRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * Lấy thông tin tài khoản ngân hàng của người dùng
     * 
     * @param int $userId
     * @return array
     */
    public function getUserBankAccount($userId)
    {
        $user = $this->userRepository->getUserById($userId);
        
        if (!$user) {
            throw new \Exception('Người dùng không tồn tại');
        }
        
        $bankAccount = $this->bankAccountRepository->getBankAccountByUserId($userId);
        
        return [
            'user' => $user,
            'bankAccount' => $bankAccount
        ];
    }
} 