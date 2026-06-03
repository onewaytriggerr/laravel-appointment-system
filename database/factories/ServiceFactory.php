<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    protected $model = Service::class;

    private $services = [
        'Haircut & Styling', 'Deep Tissue Massage', 'Oil Change', 
        'Car Wash & Wax', 'Dental Checkup', 'Personal Training Session', 
        'Manicure & Pedicure', 'Consultation', 'AC Repair'
    ];

    public function definition(): array
    {
        return [
            'name'             => fake()->unique()->randomElement($this->services),
            'duration_minutes' => fake()->randomElement([30, 45, 60, 75, 90, 105, 120]),
            'description'      => fake()->text(150),
            'price'            => fake()->randomFloat(2, 20, 200),
            'image'            => null,
        ];
    }

    public function shortDuration(int $minutes = 30): static
    {
        return $this->state(['duration_minutes' => $minutes]);
    }

    public function longDuration(int $minutes = 120): static
    {
        return $this->state(['duration_minutes' => $minutes]);
    }
}
