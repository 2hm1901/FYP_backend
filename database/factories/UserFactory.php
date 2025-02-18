<?php
namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition()
    {
        return [
            'username' => $this->faker->unique()->userName,
            'email' => $this->faker->unique()->safeEmail,
            'password' => Hash::make('password'),
            'phone_number' => $this->faker->unique()->numerify('##########'),
            'user_type' => $this->faker->randomElement(['renter', 'owner', 'admin']),
            'skill_level' => $this->faker->randomElement(['Newbie', 'TBY', 'TB', 'TB+', 'Pro']),
            'created_at' => now(),
        ];
    }
}
