<?php

namespace PalPalych\Payments\Tests\Integration\Application\Usecase\PaymentMethod;

use Mockery;
use PalPalych\Payments\Classes\Application\Dto\Request\CreatePaymentMethodRequest;
use PalPalych\Payments\Classes\Application\Usecase\PaymentMethod\CreatePaymentMethodUseCase;
use PalPalych\Payments\Models\PaymentMethod;
use Palpalych\Stories\Models\Tests\Factories\UserFactory;
use RainLab\User\Models\User;
use Tests\ComponentTestCase;
use YooKassa\Client\ApiClientInterface;
use YooKassa\Common\ResponseObject;

class CreatePaymentMethodUseCaseIntegrationTest extends ComponentTestCase
{
    protected $refreshPlugins = [
        'RainLab.User',
        'PalPalych.Payments',
    ];

    public function test_it_successfully_creates_a_payment_method()
    {
        // 1. Arrange
        /** @var User $user */
        $user = UserFactory::new()->create();

        $confirmationUrl = 'http://yookassa.ru/confirmation/pm';
        $gatewayId = 'pm_12345';
        $cardType = 'MasterCard';
        $last4 = '5678';
        $expiryYear = '2030';
        $expiryMonth = '10';

        // Mock YooKassa Client
        $yooKassaClientMock = Mockery::mock(\YooKassa\Client::class);
        $apiClientMock = Mockery::mock(ApiClientInterface::class);
        $responseObjectMock = Mockery::mock(ResponseObject::class);

        $yooKassaClientMock->shouldReceive('getApiClient')->andReturn($apiClientMock);

        $responseBody = json_encode([
            'id' => $gatewayId,
            'status' => 'pending',
            'type' => 'bank_card',
            'card' => [
                'card_type' => $cardType,
                'last4' => $last4,
                'expiry_year' => $expiryYear,
                'expiry_month' => $expiryMonth,
            ],
            'confirmation' => [
                'type' => 'redirect',
                'confirmation_url' => $confirmationUrl,
            ],
        ]);
        $responseObjectMock->shouldReceive('getCode')->andReturn(200);
        $responseObjectMock->shouldReceive('getBody')->andReturn($responseBody);

        $apiClientMock
            ->shouldReceive('call')
            ->once()
            ->with(
                '/payment_methods',
                'POST',
                [],
                Mockery::on(function ($requestBody) {
                    $data = json_decode($requestBody, true);
                    $this->assertStringStartsWith('http://example.com/success?payment_method_id=', $data['confirmation']['return_url']);
                    return true;
                }),
                Mockery::on(function ($headers) {
                    $this->assertArrayHasKey('Idempotence-Key', $headers);
                    $this->assertEquals('application/json', $headers['Content-Type']);
                    return true;
                })
            )
            ->andReturn($responseObjectMock);

        $this->app->instance(\YooKassa\Client::class, $yooKassaClientMock);

        // 2. Act
        $request = new CreatePaymentMethodRequest(
            userId: $user->id,
            success_url: 'http://example.com/success'
        );

        /** @var CreatePaymentMethodUseCase $useCase */
        $useCase = $this->app->make(CreatePaymentMethodUseCase::class);
        $response = $useCase($request);

        // 3. Assert
        $this->assertEquals($confirmationUrl, $response->confirmation_url);

        $this->assertDatabaseHas('palpalych_payments_payment_methods', [
            'user_id' => $user->id,
            'gateway_id' => $gatewayId,
            'status' => 0, // Pending
            'card_type' => $cardType,
            'last4' => $last4,
            'expiry_year' => $expiryYear,
            'expiry_month' => $expiryMonth,
        ]);

        $paymentMethod = PaymentMethod::first();
        $this->assertNotNull($paymentMethod);
        $this->assertNotNull($paymentMethod->idempotence_key);
        $this->assertNotNull($paymentMethod->gateway_request);
        $this->assertNotNull($paymentMethod->gateway_response);
    }
}

