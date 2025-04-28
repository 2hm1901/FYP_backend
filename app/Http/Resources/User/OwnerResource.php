<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Resources\Json\JsonResource;

class OwnerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'user_type' => $this->user_type,
            'avatar' => $this->avatar,
            'bankAccount' => $this->bankAccount ? [
                'id' => $this->bankAccount->id,
                'account_number' => $this->bankAccount->account_number,
                'bank_name' => $this->bankAccount->bank_name,
                'qr_code' => $this->bankAccount->qr_code,
            ] : null,
        ];
    }
} 