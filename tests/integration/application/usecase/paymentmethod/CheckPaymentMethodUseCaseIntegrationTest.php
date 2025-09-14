<?php

namespace PalPalych\Payments\Tests\Integration\Application\Usecase\PaymentMethod;

use Mockery;
use PalPalych\Payments\Classes\Application\Dto\Request\CheckPaymentMethodRequest;
use PalPalych\Payments\Classes\Application\Usecase\PaymentMethod\CheckPaymentMethodUseCase;
use PalPalych\Payments\Classes\Domain\Enum\PaymentMethodStatus;
use PalPalych\Payments\Models\PaymentMethod;
use Palpalych\Stories\Models\Tests\Factories\UserFactory;
use RainLab\User\Models\User;
use Tests\ComponentTestCase;
use YooKassa\Client\ApiClientInterface;
use YooKassa\Common\ApiClient as YooKassaApiClient;
use YooKassa\Common\Http\Response as YooKassaResponse;
use YooKassa\Common\ResponseObject;

class CheckPaymentMethodUseCaseIntegrationTest extends ComponentTestCase
{
    protected $refreshPluins = [
        'RainLab.User',
        'PalPalych.Payments',
    ];

    public function test_it_updates_payment_method_to_success_from_pending()
    {
        // 1. Arrange
        /** @var User $user */
        $user = UserFactory::new()->create();
        /** @var PaymentMethod $paymentMethod */
        $paymentMethod = PaymentMethod::factory()->create([
            'user_id' => $user->id,
            'status' => PaymentMethodStatus::pending->value,
            'gateway_id' => 'pm_pending_123',
            'accepted_at' => null,
        ]);

        $gatewayId = $paymentMethod->gateway_id;

        // Mock YooKassa Client
        $yooKassaClientMock = Mockery::mock(\YooKassa\Client::class);
        $apiClientMock = Mockery::mock(ApiClientInterface::class);
        $responseObjectMock = Mockery::mock(ResponseObject::class);

        $yooKassaClientMock->shouldReceive('getApiClient')->andReturn($apiClientMock);

        $responseBody = json_encode(['status' => 'active', 'id' => $gatewayId]);
        $responseObjectMock->shouldReceive('getCode')->andReturn(200);
        $responseObjectMock->shouldReceive('getBody')->andReturn($responseBody);

        $apiClientMock
            ->shouldReceive('call')
            ->once()
            ->with(
                '/payment_methods/' . $gatewayId,
                'GET',
                [],
                null,
                []
            )
            ->andReturn($responseObjectMock);

        $this->app->instance(\YooKassa\Client::class, $yooKassaClientMock);

        // 2. Act
        $request = new CheckPaymentMethodRequest(payment_method_id: $paymentMethod->id);
        /** @var CheckPaymentMethodUseCase $useCase */
        $useCase = $this->app->make(CheckPaymentMethodUseCase::class);
        $useCase($request);

        // 3. Assert
        $this->assertDatabaseHas('palpalych_payments_payment_methods', [
            'id' => $paymentMethod->id,
            'status' => PaymentMethodStatus::success->value,
        ]);

        $updatedPaymentMethod = PaymentMethod::find($paymentMethod->id);
        $this->assertNotNull($updatedPaymentMethod->accepted_at);
    }

    public function test_it_updates_payment_method_to_canceled_from_pending()
    {
        // 1. Arrange
        /** @var User $user */
        $user = UserFactory::new()->create();
        /** @var PaymentMethod $paymentMethod */
        $paymentMethod = PaymentMethod::factory()->create([
            'user_id' => $user->id,
            'status' => PaymentMethodStatus::pending->value,
            'gateway_id' => 'pm_pending_456',
            'accepted_at' => null,
        ]);

        $gatewayId = $paymentMethod->gateway_id;

        // Mock YooKassa Client
        $yooKassaClientMock = Mockery::mock(\YooKassa\Client::class);
        $apiClientMock = Mockery::mock(ApiClientInterface::class);
        $responseObjectMock = Mockery::mock(ResponseObject::class);

        $yooKassaClientMock->shouldReceive('getApiClient')->andReturn($apiClientMock);

        $responseBody = json_encode(['status' => 'inactive', 'id' => $gatewayId]);
        $responseObjectMock->shouldReceive('getCode')->andReturn(200);
        $responseObjectMock->shouldReceive('getBody')->andReturn($responseBody);

        $apiClientMock
            ->shouldReceive('call')
            ->once()
            ->with(
                '/payment_methods/' . $gatewayId,
                'GET',
                [],
                null,
                []
            )
            ->andReturn($responseObjectMock);

        $this->app->instance(\YooKassa\Client::class, $yooKassaClientMock);

        // 2. Act
        $request = new CheckPaymentMethodRequest(payment_method_id: $paymentMethod->id);
        /** @var CheckPaymentMethodUseCase $useCase */
        $useCase = $this->app->make(CheckPaymentMethodUseCase::class);
        $useCase($request);

        // 3. Assert
        $this->assertDatabaseHas('palpalych_payments_payment_methods', [
            'id' => $paymentMethod->id,
            'status' => PaymentMethodStatus::canceled->value,
        ]);

        $updatedPaymentMethod = PaymentMethod::find($paymentMethod->id);
        $this->assertNull($updatedPaymentMethod->accepted_at);
    }

    public function test_it_updates_payment_method_to_failed_from_pending()
    {
        // 1. Arrange
        /** @var User $user */
        $user = UserFactory::new()->create();
        /** @var PaymentMethod $paymentMethod */
        $paymentMethod = PaymentMethod::factory()->create([
            'user_id' => $user->id,
            'status' => PaymentMethodStatus::pending->value,
            'gateway_id' => 'pm_pending_789',
            'accepted_at' => null,
        ]);

        $gatewayId = $paymentMethod->gateway_id;

        // Mock YooKassa Client
        $yooKassaClientMock = Mockery::mock(\YooKassa\Client::class);
        $apiClientMock = Mockery::mock(ApiClientInterface::class);
        $responseObjectMock = Mockery::mock(ResponseObject::class);

        $yooKassaClientMock->shouldReceive('getApiClient')->andReturn($apiClientMock);

        $responseBody = json_encode(['status' => 'deactivated', 'id' => $gatewayId]);
        $responseObjectMock->shouldReceive('getCode')->andReturn(200);
        $responseObjectMock->shouldReceive('getBody')->andReturn($responseBody);

        $apiClientMock
            ->shouldReceive('call')
            ->once()
            ->with(
                '/payment_methods/' . $gatewayId,
                'GET',
                [],
                null,
                []
            )
            ->andReturn($responseObjectMock);

        $this->app->instance(\YooKassa\Client::class, $yooKassaClientMock);

        // 2. Act
        $request = new CheckPaymentMethodRequest(payment_method_id: $paymentMethod->id);
        /** @var CheckPaymentMethodUseCase $useCase */
        $useCase = $this->app->make(CheckPaymentMethodUseCase::class);
        $useCase($request);

        // 3. Assert
        $this->assertDatabaseHas('palpalych_payments_payment_methods', [
            'id' => $paymentMethod->id,
            'status' => PaymentMethodStatus::failed->value,
        ]);

        $updatedPaymentMethod = PaymentMethod::find($paymentMethod->id);
        $this->assertNull($updatedPaymentMethod->accepted_at);
    }

    public function test_it_throws_exception_if_payment_method_not_found()
    {
        // 1. Arrange
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Payment method entity not found with ID: 999');

        // 2. Act
        $request = new CheckPaymentMethodRequest(payment_method_id: 999);
        /** @var CheckPaymentMethodUseCase $useCase */
        $useCase = $this->app->make(CheckPaymentMethodUseCase::class);
        $useCase($request);

        // 3. Assert
        // Exception is thrown
    }
}
