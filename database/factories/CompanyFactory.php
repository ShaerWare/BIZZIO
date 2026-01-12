<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . fake()->unique()->randomNumber(4),
            'inn' => fake()->numerify('##########'),
            'legal_form' => fake()->randomElement(['ООО', 'ИП', 'АО', 'ПАО']),
            'short_description' => fake()->sentence(10),
            'full_description' => fake()->paragraphs(3, true),
            'industry_id' => null,
            'is_verified' => false,
            'created_by' => User::factory(),
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
        ]);
    }

    public function forOwner(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->id,
        ]);
    }
}
