<?php

namespace PalPalych\Payments\Tests\Integration\Application\Usecase\Payment;

use Mockery;
use PalPalych\Payments\Classes\Application\Dto\Request\CreatePaymentRequest;
use PalPalych\Payments\Classes\Application\Usecase\Payment\CreatePaymentUseCase;
use PalPalych\Payments\Models\Payment;
use PalPalych\Payments\Models\PaymentMethod;
use PalPalych\Payments\Tests\Models\TestPayable;
use PalPalych\Payments\Tests\Models\Factory\UserFactory;
use RainLab\User\Models\User;
use Tests\ComponentTestCase;
use YooKassa\Model\Payment\Confirmation\AbstractConfirmation;
use YooKassa\Request\Payments\CreatePaymentResponse;

class CreatePaymentUseCaseIntegrationTest extends ComponentTestCase
{
    protected $refreshPlugins = [
        'RainLab.User',
        'PalPalych.Payments',
    ];

    public function test_it_successfully_creates_a_payment_integration()
    {
        // 1. Arrange
        /** @var User $user */
        $user = app(UserFactory::class)->create();
        /** @var TestPayable $payable */
        $payable = TestPayable::factory()->create(['user_id' => $user->id]);

        $confirmationUrl = 'http://yookassa.ru/confirmation';
        $gatewayId = 'gw_123';

        // Mock YooKassa Client
        $yooKassaClientMock = Mockery::mock(\YooKassa\Client::class);
        $yooKassaPaymentResponseMock = Mockery::mock(CreatePaymentResponse::class);
        $confirmationMock = Mockery::mock(AbstractConfirmation::class);

        $confirmationMock->shouldReceive('getConfirmationUrl')->andReturn($confirmationUrl);

        $yooKassaPaymentResponseMock->shouldReceive('getId')->andReturn($gatewayId);
        $yooKassaPaymentResponseMock->shouldReceive('getConfirmation')->andReturn($confirmationMock);
        $yooKassaPaymentResponseMock->shouldReceive('toArray')->andReturn(['id' => $gatewayId, 'status' => 'pending']);

        $yooKassaClientMock
            ->shouldReceive('createPayment')
            ->once()
            ->with(
                Mockery::on(function ($requestData) use ($payable) {
                    $this->assertEquals(number_format($payable->getPayableAmount() / 100, 2, '.', ''), $requestData['amount']['value']);
                    $this->assertTrue($requestData['capture']);
                    $this->assertStringContainsString('payment_id=', $requestData['confirmation']['return_url']);
                    return true;
                }),
                Mockery::any() // Idempotence key is random
            )
            ->andReturn($yooKassaPaymentResponseMock);

        $this->app->instance(\YooKassa\Client::class, $yooKassaClientMock);

        // 2. Act
        $request = new CreatePaymentRequest(
            userId: $user->id,
            payableId: $payable->id,
            payableType: TestPayable::class,
            success_url: 'http://example.com/success',
            client_email: $user->email,
        );

        /** @var CreatePaymentUseCase $useCase */
        $useCase = $this->app->make(CreatePaymentUseCase::class);
        $response = $useCase($request);

        // 3. Assert
        $this->assertEquals($confirmationUrl, $response->confirmation_url);

        // Assert payment is in the database
        $this->assertDatabaseHas('palpalych_payments_payments', [
            'user_id' => $user->id,
            'payable_id' => $payable->id,
            'payable_type' => TestPayable::class,
            'total' => $payable->getPayableAmount(),
            'gateway_id' => $gatewayId,
            'status' => 0, // Pending
        ]);

        $payment = Payment::first();
        $this->assertNotNull($payment);
        $this->assertNotNull($payment->idempotence_key);
        $this->assertNotNull($payment->gateway_request);
        $this->assertNotNull($payment->gateway_response);
    }
}
