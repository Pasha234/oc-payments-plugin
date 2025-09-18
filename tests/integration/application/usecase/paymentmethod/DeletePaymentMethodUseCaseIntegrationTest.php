<?php

namespace PalPalych\Payments\Tests\Integration\Application\Usecase\PaymentMethod;

use PalPalych\Payments\Classes\Application\Dto\Request\DeletePaymentMethodRequest;
use PalPalych\Payments\Classes\Application\Usecase\PaymentMethod\DeletePaymentMethodUseCase;
use PalPalych\Payments\Models\PaymentMethod;
use PalPalych\Payments\Tests\Models\Factory\UserFactory;
use RainLab\User\Models\User;
use RuntimeException;
use Tests\ComponentTestCase;

class DeletePaymentMethodUseCaseIntegrationTest extends ComponentTestCase
{
    protected $refreshPlugins = [
        'RainLab.User',
        'PalPalych.Payments',
    ];

    public function test_it_successfully_deletes_a_payment_method()
    {
        // 1. Arrange
        /** @var User $user */
        $user = app(UserFactory::class)->create();
        /** @var PaymentMethod $paymentMethod */
        $paymentMethod = PaymentMethod::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('palpalych_payments_payment_methods', [
            'id' => $paymentMethod->id,
        ]);

        // 2. Act
        $request = new DeletePaymentMethodRequest(payment_method_id: (string)$paymentMethod->id);
        /** @var DeletePaymentMethodUseCase $useCase */
        $useCase = $this->app->make(DeletePaymentMethodUseCase::class);
        $useCase($request);

        // 3. Assert
        $this->assertDatabaseMissing('palpalych_payments_payment_methods', [
            'id' => $paymentMethod->id,
        ]);
    }

    public function test_it_throws_exception_if_payment_method_not_found()
    {
        // 1. Arrange
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Payment method entity not found with ID: 999');

        // 2. Act
        $request = new DeletePaymentMethodRequest(payment_method_id: '999');
        /** @var DeletePaymentMethodUseCase $useCase */
        $useCase = $this->app->make(DeletePaymentMethodUseCase::class);
        $useCase($request);
    }
}

