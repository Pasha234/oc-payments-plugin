<?php

namespace PalPalych\Payments\Tests\Models\Factory;

use Carbon\Carbon;
use RainLab\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @template-extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'first_name' => $this->faker->lexify('?????'),
            'last_name' => $this->faker->lexify('?????'),
            'username' => $this->faker->lexify('?????'),
            'email' => $this->faker->email(),
            'password' => "test(123)",
            'password_confirmation' => "test(123)",
            'gender' => $this->faker->numberBetween(1, 2),
            'activated_at' => Carbon::now(),
        ];
    }

    public function notActivated(): static
    {
        return $this->state([
            'activated_at' => null,
        ]);
    }
}
