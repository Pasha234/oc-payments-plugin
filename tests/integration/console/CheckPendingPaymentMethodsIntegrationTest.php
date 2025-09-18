<?php

namespace PalPalych\Payments\Tests\Integration\Console;

use Mockery;
use PalPalych\Payments\Classes\Domain\Enum\PaymentMethodStatus;
use PalPalych\Payments\Models\PaymentMethod;
use Palpalych\Stories\Models\Tests\Factories\UserFactory;
use Tests\ComponentTestCase;
use YooKassa\Client\ApiClientInterface;
use YooKassa\Common\ResponseObject;

class CheckPendingPaymentMethodsIntegrationTest extends ComponentTestCase
{
    protected $refreshPlugins = [
        'RainLab.User',
        'PalPalych.Payments',
    ];

    public function test_it_checks_pending_payment_methods_and_updates_statuses()
    {
        // 1. Arrange
        $user = UserFactory::new()->create();

        // Create payment methods with different outcomes
        $pmToSucceed = PaymentMethod::factory()->create([
            'user_id' => $user->id,
            'status' => PaymentMethodStatus::pending->value,
            'gateway_id' => 'pm_succeed',
        ]);

        $pmToCancel = PaymentMethod::factory()->create([
            'user_id' => $user->id,
            'status' => PaymentMethodStatus::pending->value,
            'gateway_id' => 'pm_cancel',
        ]);

        $pmToRemainPending = PaymentMethod::factory()->create([
            'user_id' => $user->id,
            'status' => PaymentMethodStatus::pending->value,
            'gateway_id' => 'pm_pending',
        ]);

        $pmWithError = PaymentMethod::factory()->create([
            'user_id' => $user->id,
            'status' => PaymentMethodStatus::pending->value,
            'gateway_id' => 'pm_error',
        ]);

        // Mock YooKassa Client
        $yooKassaClientMock = Mockery::mock(\YooKassa\Client::class);
        $apiClientMock = Mockery::mock(ApiClientInterface::class);
        $yooKassaClientMock->shouldReceive('getApiClient')->andReturn($apiClientMock);

        // Mock responses from gateway
        $succeededResponse = Mockery::mock(ResponseObject::class);
        $succeededResponse->shouldReceive('getCode')->andReturn(200);
        $succeededResponse->shouldReceive('getBody')->andReturn(json_encode(['status' => 'active']));

        $canceledResponse = Mockery::mock(ResponseObject::class);
        $canceledResponse->shouldReceive('getCode')->andReturn(200);
        $canceledResponse->shouldReceive('getBody')->andReturn(json_encode(['status' => 'inactive']));

        $pendingResponse = Mockery::mock(ResponseObject::class);
        $pendingResponse->shouldReceive('getCode')->andReturn(200);
        $pendingResponse->shouldReceive('getBody')->andReturn(json_encode(['status' => 'pending']));

        $apiClientMock
            ->shouldReceive('call')
            ->with('/payment_methods/' . $pmToSucceed->gateway_id, 'GET', Mockery::any(), Mockery::any(), Mockery::any())
            ->andReturn($succeededResponse);

        $apiClientMock
            ->shouldReceive('call')
            ->with('/payment_methods/' . $pmToCancel->gateway_id, 'GET', Mockery::any(), Mockery::any(), Mockery::any())
            ->andReturn($canceledResponse);

        $apiClientMock
            ->shouldReceive('call')
            ->with('/payment_methods/' . $pmToRemainPending->gateway_id, 'GET', Mockery::any(), Mockery::any(), Mockery::any())
            ->andReturn($pendingResponse);

        $apiClientMock
            ->shouldReceive('call')
            ->with('/payment_methods/' . $pmWithError->gateway_id, 'GET', Mockery::any(), Mockery::any(), Mockery::any())
            ->andThrow(new \Exception('Gateway communication error'));

        $this->app->instance(\YooKassa\Client::class, $yooKassaClientMock);

        // 2. Act
        $this->artisan('payments:checkpendingpaymentmethods');

        // 3. Assert
        $this->assertDatabaseHas('palpalych_payments_payment_methods', [
            'id' => $pmToSucceed->id,
            'status' => PaymentMethodStatus::success->value,
        ]);
        $this->assertDatabaseHas('palpalych_payments_payment_methods', [
            'id' => $pmToCancel->id,
            'status' => PaymentMethodStatus::canceled->value,
        ]);
        $this->assertDatabaseHas('palpalych_payments_payment_methods', [
            'id' => $pmToRemainPending->id,
            'status' => PaymentMethodStatus::pending->value,
        ]);
        $this->assertDatabaseHas('palpalych_payments_payment_methods', [
            'id' => $pmWithError->id,
            'status' => PaymentMethodStatus::pending->value, // Status should not change on error
        ]);
    }
}
