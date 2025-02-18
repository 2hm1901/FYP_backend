<?php

namespace Database\Factories;

use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

class VenueFactory extends Factory
{
    protected $model = Venue::class;

    public function definition()
    {
        $openTime = $this->getRoundedTime();
        $closeTime = $this->getCloseTimeAfter($openTime);

        return [
            'owner_id' => User::factory()->create(['user_type' => 'owner'])->id,
            'name' => $this->faker->word,
            'phone' => '0' . $this->faker->regexify('[1-9]{9}'), // Tạo số điện thoại luôn bắt đầu bằng số 0 và có độ dài tổng cộng 10 số
            'location' => $this->faker->streetName . ', ' . $this->faker->city . ', ' . $this->faker->state, // Chỉ lấy tên đường, thành phố và tỉnh
            'court_count' => $this->faker->numberBetween(1, 5),
            'open_time' => $openTime,
            'close_time' => $closeTime,
            'created_at' => now(),
        ];
    }

    private function getRoundedTime()
    {
        $hours = $this->faker->numberBetween(0, 23);
        $minutes = $this->faker->randomElement(['00', '30']);
        return sprintf('%02d:%02d', $hours, $minutes);
    }

    private function getCloseTimeAfter($openTime)
    {
        list($openHour, $openMinute) = explode(':', $openTime);
        $minCloseHour = ($openHour + 10) % 24;
        $maxCloseHour = ($openHour + 15) % 24;

        $closeHour = $this->faker->numberBetween($minCloseHour, $maxCloseHour);
        $closeMinute = $openMinute;

        return sprintf('%02d:%02d', $closeHour, $closeMinute);
    }
}
