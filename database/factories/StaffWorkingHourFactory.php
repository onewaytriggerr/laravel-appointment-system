<?php

namespace Database\Factories;

use App\Models\StaffWorkingHour;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Database\Factories\UserFactory;
use App\Enums\DayOfWeek;

/**
 * @extends Factory<StaffWorkingHour>
 */
class StaffWorkingHourFactory extends Factory
{

    protected $model = StaffWorkingHour::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id'     => UserFactory::staff(),
            'day_of_week' => fake()->randomElement(DayOfWeek::cases())->value,
            'start_time'  => '09:00:00',
            'end_time'    => '12:00:00'
        ];
    }
}
