<?php

namespace PalPalych\Payments\Models\Tests\Factories;

use PalPalych\Payments\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use PalPalych\Payments\Classes\Domain\Enum\PaymentStatus;
use Palpalych\Stories\Models\Tests\Factories\UserFactory;

/**
 * @template-extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'user_id' => UserFactory::new(),
            'total' => $this->faker->numberBetween(),
            'idempotence_key' => $this->faker->uuid(),
            // 'payment_data' => ,
            // 'payment_response' => ,
            // 'payment_token' => ,
            'status' => PaymentStatus::pending,
        ];
    }
}
