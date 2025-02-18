<?php

namespace Database\Factories;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition()
    {
        return [
            'booking_id' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => User::factory(),
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'payment_method' => $this->faker->randomElement(['credit_card', 'debit_card', 'paypal', 'momo', 'zalopay']),
            'payment_date' => now(),
            'status' => $this->faker->randomElement(['success', 'failed', 'pending']),
            'created_at' => now(),
        ];
    }
}

