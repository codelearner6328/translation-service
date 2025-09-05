<?php

namespace Database\Factories;

use App\Models\TranslationKey;
use Illuminate\Database\Eloquent\Factories\Factory;

class TranslationKeyFactory extends Factory
{
    protected $model = TranslationKey::class;
    public function definition()
    {
        return [
            'namespace' => $this->faker->randomElement(['app', 'auth', 'errors', 'emails']),
            'key' => $this->faker->unique()->slug(3),
            'description' => $this->faker->sentence(),
        ];
    }
}
