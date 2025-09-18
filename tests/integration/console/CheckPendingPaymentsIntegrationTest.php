<?php

namespace PalPalych\Payments\Tests\Integration\Console;

use Mockery;
use PalPalych\Payments\Classes\Domain\Enum\PaymentStatus;
use PalPalych\Payments\Models\Payment;
use Palpalych\Stories\Models\Tests\Factories\UserFactory;
use Tests\ComponentTestCase;
use YooKassa\Model\Payment\PaymentInterface as YooKassaPaymentInterface;

class CheckPendingPaymentsIntegrationTest extends ComponentTestCase
{
    protected $refreshPlugins = [
        'RainLab.User',
        'PalPalych.Payments',
    ];

    public function test_it_checks_pending_payments_and_updates_statuses()
    {
        // 1. Arrange
        $user = UserFactory::new()->create();

        // Create payments with different outcomes
        $paymentToSucceed = Payment::factory()->create([
            'user_id' => $user->id,
            'status' => PaymentStatus::pending->value,
            'gateway_id' => 'gw_succeed',
        ]);

        $paymentToCancel = Payment::factory()->create([
            'user_id' => $user->id,
            'status' => PaymentStatus::pending->value,
            'gateway_id' => 'gw_cancel',
        ]);

        $paymentToRemainPending = Payment::factory()->create([
            'user_id' => $user->id,
            'status' => PaymentStatus::pending->value,
            'gateway_id' => 'gw_pending',
        ]);

        $paymentWithError = Payment::factory()->create([
            'user_id' => $user->id,
            'status' => PaymentStatus::pending->value,
            'gateway_id' => 'gw_error',
        ]);

        // Mock YooKassa Client
        $yooKassaClientMock = Mockery::mock(\YooKassa\Client::class);

        // Mock responses from gateway
        $succeededResponse = Mockery::mock(YooKassaPaymentInterface::class);
        $succeededResponse->shouldReceive('getStatus')->andReturn('succeeded');
        $succeededResponse->shouldReceive('jsonSerialize')->andReturn(['status' => 'succeeded']);

        $canceledResponse = Mockery::mock(YooKassaPaymentInterface::class);
        $canceledResponse->shouldReceive('getStatus')->andReturn('canceled');
        $canceledResponse->shouldReceive('jsonSerialize')->andReturn(['status' => 'canceled']);

        $pendingResponse = Mockery::mock(YooKassaPaymentInterface::class);
        $pendingResponse->shouldReceive('getStatus')->andReturn('pending');
        $pendingResponse->shouldReceive('jsonSerialize')->andReturn(['status' => 'pending']);

        $yooKassaClientMock
            ->shouldReceive('getPaymentInfo')
            ->with($paymentToSucceed->gateway_id)
            ->andReturn($succeededResponse);

        $yooKassaClientMock
            ->shouldReceive('getPaymentInfo')
            ->with($paymentToCancel->gateway_id)
            ->andReturn($canceledResponse);

        $yooKassaClientMock
            ->shouldReceive('getPaymentInfo')
            ->with($paymentToRemainPending->gateway_id)
            ->andReturn($pendingResponse);

        $yooKassaClientMock
            ->shouldReceive('getPaymentInfo')
            ->with($paymentWithError->gateway_id)
            ->andThrow(new \Exception('Gateway communication error'));

        $this->app->instance(\YooKassa\Client::class, $yooKassaClientMock);

        // 2. Act
        $this->artisan('payments:checkpendingpayments');

        // 3. Assert
        $this->assertDatabaseHas('palpalych_payments_payments', [
            'id' => $paymentToSucceed->id,
            'status' => PaymentStatus::success->value,
        ]);
        $this->assertDatabaseHas('palpalych_payments_payments', [
            'id' => $paymentToCancel->id,
            'status' => PaymentStatus::canceled->value,
        ]);
        $this->assertDatabaseHas('palpalych_payments_payments', [
            'id' => $paymentToRemainPending->id,
            'status' => PaymentStatus::pending->value,
        ]);
        $this->assertDatabaseHas('palpalych_payments_payments', [
            'id' => $paymentWithError->id,
            'status' => PaymentStatus::pending->value, // Status should not change on error
        ]);
    }
}
