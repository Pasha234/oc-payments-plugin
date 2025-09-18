<?php

namespace PalPalych\Payments\Tests\Integration\Application\Usecase\Payment;

use Mockery;
use PalPalych\Payments\Classes\Application\Dto\Request\CheckPaymentRequest;
use PalPalych\Payments\Classes\Application\Usecase\Payment\CheckPaymentUseCase;
use PalPalych\Payments\Classes\Domain\Enum\PaymentStatus;
use PalPalych\Payments\Models\Payment;
use PalPalych\Payments\Tests\Models\Factory\UserFactory;
use RainLab\User\Models\User;
use Tests\ComponentTestCase;
use YooKassa\Model\Payment\PaymentInterface;

class CheckPaymentUseCaseIntegrationTest extends ComponentTestCase
{
    protected $refreshPlugins = [
        'RainLab.User',
        'PalPalych.Payments',
    ];

    public function test_it_updates_payment_to_success_from_pending()
    {
        // 1. Arrange
        /** @var User $user */
        $user = app(UserFactory::class)->create();
        /** @var Payment $payment */
        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'status' => PaymentStatus::pending->value,
            'gateway_id' => 'gw_pending_123',
            'paid_at' => null,
        ]);

        $gatewayId = $payment->gateway_id;

        // Mock YooKassa Client
        $yooKassaClientMock = Mockery::mock(\YooKassa\Client::class);
        $yooKassaPaymentResponseMock = Mockery::mock(PaymentInterface::class);

        $yooKassaPaymentResponseMock->shouldReceive('getStatus')->andReturn('succeeded');
        $yooKassaPaymentResponseMock->shouldReceive('jsonSerialize')->andReturn(['status' => 'succeeded']);

        $yooKassaClientMock
            ->shouldReceive('getPaymentInfo')
            ->once()
            ->with($gatewayId)
            ->andReturn($yooKassaPaymentResponseMock);

        $this->app->instance(\YooKassa\Client::class, $yooKassaClientMock);

        // 2. Act
        $request = new CheckPaymentRequest(payment_id: $payment->id);
        /** @var CheckPaymentUseCase $useCase */
        $useCase = $this->app->make(CheckPaymentUseCase::class);
        $useCase($request);

        // 3. Assert
        $this->assertDatabaseHas('palpalych_payments_payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::success->value,
        ]);

        $updatedPayment = Payment::find($payment->id);
        $this->assertNotNull($updatedPayment->paid_at);
    }

    public function test_it_updates_payment_to_canceled_from_pending()
    {
        // 1. Arrange
        /** @var User $user */
        $user = app(UserFactory::class)->create();
        /** @var Payment $payment */
        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'status' => PaymentStatus::pending->value,
            'gateway_id' => 'gw_pending_456',
            'paid_at' => null,
        ]);

        $gatewayId = $payment->gateway_id;

        // Mock YooKassa Client
        $yooKassaClientMock = Mockery::mock(\YooKassa\Client::class);
        $yooKassaPaymentResponseMock = Mockery::mock(PaymentInterface::class);

        $yooKassaPaymentResponseMock->shouldReceive('getStatus')->andReturn('canceled');
        $yooKassaPaymentResponseMock->shouldReceive('jsonSerialize')->andReturn(['status' => 'canceled']);

        $yooKassaClientMock
            ->shouldReceive('getPaymentInfo')
            ->once()
            ->with($gatewayId)
            ->andReturn($yooKassaPaymentResponseMock);

        $this->app->instance(\YooKassa\Client::class, $yooKassaClientMock);

        // 2. Act
        $request = new CheckPaymentRequest(payment_id: $payment->id);
        /** @var CheckPaymentUseCase $useCase */
        $useCase = $this->app->make(CheckPaymentUseCase::class);
        $useCase($request);

        // 3. Assert
        $this->assertDatabaseHas('palpalych_payments_payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::canceled->value,
        ]);

        $updatedPayment = Payment::find($payment->id);
        $this->assertNull($updatedPayment->paid_at);
    }

    public function test_it_updates_payment_to_failed_from_pending()
    {
        // 1. Arrange
        /** @var User $user */
        $user = app(UserFactory::class)->create();
        /** @var Payment $payment */
        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'status' => PaymentStatus::pending->value,
            'gateway_id' => 'gw_pending_789',
            'paid_at' => null,
        ]);

        $gatewayId = $payment->gateway_id;

        // Mock YooKassa Client
        $yooKassaClientMock = Mockery::mock(\YooKassa\Client::class);
        $yooKassaPaymentResponseMock = Mockery::mock(PaymentInterface::class);

        // 'waiting_for_capture' will be mapped to 'failed' by the gateway
        $yooKassaPaymentResponseMock->shouldReceive('getStatus')->andReturn('waiting_for_capture');
        $yooKassaPaymentResponseMock->shouldReceive('jsonSerialize')->andReturn(['status' => 'waiting_for_capture']);

        $yooKassaClientMock
            ->shouldReceive('getPaymentInfo')
            ->once()
            ->with($gatewayId)
            ->andReturn($yooKassaPaymentResponseMock);

        $this->app->instance(\YooKassa\Client::class, $yooKassaClientMock);

        // 2. Act
        $request = new CheckPaymentRequest(payment_id: $payment->id);
        /** @var CheckPaymentUseCase $useCase */
        $useCase = $this->app->make(CheckPaymentUseCase::class);
        $useCase($request);

        // 3. Assert
        $this->assertDatabaseHas('palpalych_payments_payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::failed->value,
        ]);

        $updatedPayment = Payment::find($payment->id);
        $this->assertNull($updatedPayment->paid_at);
    }

    public function test_it_does_not_change_status_if_payment_is_already_succeeded()
    {
        // 1. Arrange
        /** @var User $user */
        $user = app(UserFactory::class)->create();
        /** @var Payment $payment */
        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'status' => PaymentStatus::success->value,
            'gateway_id' => 'gw_success_123',
            'paid_at' => now(),
        ]);

        $gatewayId = $payment->gateway_id;
        $originalPaidAt = $payment->paid_at;

        // Mock YooKassa Client
        $yooKassaClientMock = Mockery::mock(\YooKassa\Client::class);
        $yooKassaPaymentResponseMock = Mockery::mock(PaymentInterface::class);

        $yooKassaPaymentResponseMock->shouldReceive('getStatus')->andReturn('succeeded');
        $yooKassaPaymentResponseMock->shouldReceive('jsonSerialize')->andReturn(['status' => 'succeeded']);

        $yooKassaClientMock
            ->shouldReceive('getPaymentInfo')
            ->once()
            ->with($gatewayId)
            ->andReturn($yooKassaPaymentResponseMock);

        $this->app->instance(\YooKassa\Client::class, $yooKassaClientMock);

        // 2. Act
        $request = new CheckPaymentRequest(payment_id: $payment->id);
        /** @var CheckPaymentUseCase $useCase */
        $useCase = $this->app->make(CheckPaymentUseCase::class);
        $useCase($request);

        // 3. Assert
        $this->assertDatabaseHas('palpalych_payments_payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::success->value,
        ]);

        $updatedPayment = Payment::find($payment->id);
        $this->assertEquals($originalPaidAt->toDateTimeString(), $updatedPayment->paid_at->toDateTimeString());
    }
}

