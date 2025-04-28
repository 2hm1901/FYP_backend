<?php

namespace App\Repositories\BankAccount;

interface BankAccountRepositoryInterface
{
    public function getBankAccountByUserId($userId);
} 