<?php

namespace PalPalych\Payments\Tests\Unit\Application\Usecase\PaymentMethod;

use Mockery;
use ReflectionProperty;
use Tests\ComponentTestCase;
use PalPalych\Payments\Classes\Domain\Entity\PaymentMethod;
use PalPalych\Payments\Classes\Domain\Gateway\PaymentGatewayInterface;
use PalPalych\Payments\Classes\Application\Dto\Request\CreatePaymentMethodRequest;
use PalPalych\Payments\Classes\Domain\Repository\PaymentMethodRepositoryInterface;
use PalPalych\Payments\Classes\Application\Dto\Response\CreatePaymentMethodResponse;
use PalPalych\Payments\Classes\Domain\Dto\Gateway\CreatePaymentMethodGatewayResponse;
use PalPalych\Payments\Classes\Application\Usecase\PaymentMethod\CreatePaymentMethodUseCase;

class CreatePaymentMethodUseCaseTest extends ComponentTestCase
{
    private Mockery\MockInterface|PaymentMethodRepositoryInterface $paymentMethodRepository;
    private Mockery\MockInterface|PaymentGatewayInterface $paymentGateway;
    private CreatePaymentMethodUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentMethodRepository = Mockery::mock(PaymentMethodRepositoryInterface::class);
        $this->paymentGateway = Mockery::mock(PaymentGatewayInterface::class);

        $this->useCase = new CreatePaymentMethodUseCase(
            $this->paymentMethodRepository,
            $this->paymentGateway
        );
    }

    public function test_it_successfully_creates_a_payment_method()
    {
        // 1. Arrange
        $userId = 1;
        $successUrl = 'http://example.com/success';
        $paymentMethodId = 789;
        $confirmationUrl = 'http://yookassa.ru/confirmation/pm';
        $gatewayId = 'pm_123';
        $idempotenceKey = 'idem_pm_123';
        $gatewayRequest = '{"type":"bank_card"}';
        $gatewayResponseJson = '{"id":"pm_123"}';
        $cardType = 'Visa';
        $last4 = '1234';
        $expiryYear = '2025';
        $expiryMonth = '12';

        $request = new CreatePaymentMethodRequest($userId, $successUrl);

        // 2. Mock expectations
        $this->paymentMethodRepository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (PaymentMethod $paymentMethod) use ($userId, $paymentMethodId) {
                $this->assertEquals($userId, $paymentMethod->getUserId());

                // Simulate setting the ID on save, as the repository would
                $reflectionId = new ReflectionProperty($paymentMethod, 'id');
                $reflectionId->setAccessible(true);
                $reflectionId->setValue($paymentMethod, $paymentMethodId);

                return true;
            }));

        $gatewayResponse = new CreatePaymentMethodGatewayResponse(
            $gatewayRequest,
            $gatewayResponseJson,
            $gatewayId,
            $idempotenceKey,
            $confirmationUrl,
            $cardType,
            $last4,
            $expiryYear,
            $expiryMonth
        );

        $this->paymentGateway
            ->shouldReceive('createPaymentMethod')
            ->once()
            ->with($successUrl . "?payment_method_id={$paymentMethodId}")
            ->andReturn($gatewayResponse);

        $this->paymentMethodRepository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (PaymentMethod $paymentMethod) use ($gatewayId, $gatewayRequest, $gatewayResponseJson, $idempotenceKey, $cardType, $last4, $expiryYear, $expiryMonth) {
                $this->assertEquals($gatewayId, $paymentMethod->getGatewayId());
                $this->assertEquals($gatewayRequest, $paymentMethod->getGatewayRequest());
                $this->assertEquals($gatewayResponseJson, $paymentMethod->getGatewayResponse());
                $this->assertEquals($idempotenceKey, $paymentMethod->getIdempotenceKey());
                $this->assertEquals($cardType, $paymentMethod->getCardType());
                $this->assertEquals($last4, $paymentMethod->getLast4());
                $this->assertEquals($expiryYear, $paymentMethod->getExpiryYear());
                $this->assertEquals($expiryMonth, $paymentMethod->getExpiryMonth());
                return true;
            }));

        // 3. Act
        $response = ($this->useCase)($request);

        // 4. Assert
        $this->assertInstanceOf(CreatePaymentMethodResponse::class, $response);
        $this->assertEquals($confirmationUrl, $response->confirmation_url);
    }
}

