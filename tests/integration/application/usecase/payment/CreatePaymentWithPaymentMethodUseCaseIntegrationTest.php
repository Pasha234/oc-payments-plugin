<?php

namespace PalPalych\Payments\Tests\Integration\Application\Usecase\Payment;

use Mockery;
use PalPalych\Payments\Classes\Application\Dto\Request\CreatePaymentWithPaymentMethodRequest;
use PalPalych\Payments\Classes\Application\Usecase\Payment\CreatePaymentWithPaymentMethodUseCase;
use PalPalych\Payments\Classes\Domain\Enum\PaymentStatus;
use PalPalych\Payments\Models\Payment;
use PalPalych\Payments\Models\PaymentMethod;
use PalPalych\Payments\Tests\Models\TestPayable;
use Palpalych\Stories\Models\Tests\Factories\UserFactory;
use RainLab\User\Models\User;
use Tests\ComponentTestCase;
use YooKassa\Request\Payments\CreatePaymentResponse;

class CreatePaymentWithPaymentMethodUseCaseIntegrationTest extends ComponentTestCase
{
    protected $refreshPlugins = [
        'RainLab.User',
        'PalPalych.Payments',
    ];

    public function test_it_successfully_creates_a_payment_with_payment_method()
    {
        // 1. Arrange
        /** @var User $user */
        $user = UserFactory::new()->create();
        /** @var TestPayable $payable */
        $payable = TestPayable::factory()->create(['user_id' => $user->id]);
        /** @var PaymentMethod $paymentMethod */
        $paymentMethod = PaymentMethod::factory()->create([
            'user_id' => $user->id,
            'gateway_id' => 'pm_gateway_123',
        ]);

        $gatewayId = 'gw_123';

        // Mock YooKassa Client
        $yooKassaClientMock = Mockery::mock(\YooKassa\Client::class);
        $yooKassaPaymentResponseMock = Mockery::mock(CreatePaymentResponse::class);

        $yooKassaPaymentResponseMock->shouldReceive('getId')->andReturn($gatewayId);
        $yooKassaPaymentResponseMock->shouldReceive('getStatus')->andReturn('succeeded');
        $yooKassaPaymentResponseMock->shouldReceive('toArray')->andReturn(['id' => $gatewayId, 'status' => 'succeeded']);

        $yooKassaClientMock
            ->shouldReceive('createPayment')
            ->once()
            ->with(
                Mockery::on(function ($requestData) use ($payable, $paymentMethod) {
                    $this->assertEquals(number_format($payable->getPayableAmount() / 100, 2, '.', ''), $requestData['amount']['value']);
                    $this->assertTrue($requestData['capture']);
                    $this->assertEquals($paymentMethod->gateway_id, $requestData['payment_method_id']);
                    return true;
                }),
                Mockery::any() // Idempotence key is random
            )
            ->andReturn($yooKassaPaymentResponseMock);

        $this->app->instance(\YooKassa\Client::class, $yooKassaClientMock);

        // 2. Act
        $request = new CreatePaymentWithPaymentMethodRequest(
            userId: $user->id,
            payableId: $payable->id,
            payableType: TestPayable::class,
            paymentMethodId: $paymentMethod->id,
            client_email: $user->email,
        );

        /** @var CreatePaymentWithPaymentMethodUseCase $useCase */
        $useCase = $this->app->make(CreatePaymentWithPaymentMethodUseCase::class);
        $response = $useCase($request);

        // 3. Assert
        $payment = Payment::find($response->payment_id);
        $this->assertNotNull($payment);

        $this->assertDatabaseHas('palpalych_payments_payments', [
            'id' => $payment->id,
            'user_id' => $user->id,
            'payable_id' => $payable->id,
            'payable_type' => TestPayable::class,
            'payment_method_id' => $paymentMethod->id,
            'total' => $payable->getPayableAmount(),
            'gateway_id' => $gatewayId,
            'status' => PaymentStatus::success->value,
        ]);

        $this->assertNotNull($payment->idempotence_key);
        $this->assertNotNull($payment->gateway_request);
        $this->assertNotNull($payment->gateway_response);
        $this->assertNotNull($payment->paid_at);
    }
}
