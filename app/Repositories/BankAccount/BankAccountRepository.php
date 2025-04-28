<?php

namespace App\Repositories\BankAccount;

use App\Models\BankAccount;

class BankAccountRepository implements BankAccountRepositoryInterface
{
    /**
     * Lấy thông tin tài khoản ngân hàng theo user id
     * 
     * @param int $userId
     * @return \App\Models\BankAccount|null
     */
    public function getBankAccountByUserId($userId)
    {
        return BankAccount::where('user_id', $userId)->first();
    }
} 