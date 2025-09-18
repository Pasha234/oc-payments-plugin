<?php

namespace PalPalych\Payments\Models\Tests\Factories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use PalPalych\Payments\Classes\Domain\Enum\PaymentMethodStatus;
use PalPalych\Payments\Models\PaymentMethod;
use PalPalych\Payments\Tests\Models\Factory\UserFactory;

/**
 * @template-extends Factory<PaymentMethod>
 */
class PaymentMethodFactory extends Factory
{
    protected $model = PaymentMethod::class;

    public function definition(): array
    {
        return [
            'user_id' => app(UserFactory::class),
            'idempotence_key' => $this->faker->uuid(),
            'status' => PaymentMethodStatus::pending,
            'card_type' => $this->faker->word(),
            'last4' => $this->faker->word(),
            'expiry_year' => $this->faker->word(),
            'expiry_month' => $this->faker->word(),
        ];
    }

    public function accepted()
    {
        return $this->state([
            'accepted_at' => Carbon::now(),
            'status' => PaymentMethodStatus::success,
        ]);
    }
}
