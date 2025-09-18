<?php

namespace PalPalych\Payments\Tests\Models\Factory;

use Illuminate\Database\Eloquent\Factories\Factory;
use PalPalych\Payments\Tests\Models\TestPayable;
use PalPalych\Payments\Tests\Models\Factory\UserFactory;

/**
 * @template-extends Factory<TestPayable>
 */
class TestPayableFactory extends Factory
{
    protected $model = TestPayable::class;

    public function definition(): array
    {
        return [
            'user_id' => app(UserFactory::class),
            'paid' => false,
        ];
    }

    public function paid(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'paid' => true,
            ];
        });
    }
}
