<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition(): array
    {
        return [
            'name'         => fake()->company(),
            'address'      => fake()->address(),
            'phone'        => fake()->e164PhoneNumber(),
            'timezone'     => fake()->timezone(),
            'opening_time' => '09:00:00',
            'closing_time' => '18:00:00',
        ];
    }

    public function utcTimezone(): static
    {
        return $this->state(['timezone' => 'UTC']);
    }
}
