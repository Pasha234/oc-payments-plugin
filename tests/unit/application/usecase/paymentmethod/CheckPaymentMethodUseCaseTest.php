<?php

namespace PalPalych\Payments\Tests\Unit\Application\Usecase\PaymentMethod;

use Mockery;
use RuntimeException;
use Tests\ComponentTestCase;
use PalPalych\Payments\Classes\Domain\Entity\PaymentMethod;
use PalPalych\Payments\Classes\Domain\Enum\PaymentMethodStatus;
use PalPalych\Payments\Classes\Domain\Event\EventDispatcherInterface;
use PalPalych\Payments\Classes\Domain\Gateway\PaymentGatewayInterface;
use PalPalych\Payments\Classes\Application\Dto\Request\CheckPaymentMethodRequest;
use PalPalych\Payments\Classes\Domain\Repository\PaymentMethodRepositoryInterface;
use PalPalych\Payments\Classes\Domain\Dto\Gateway\CheckPaymentMethodGatewayResponse;
use PalPalych\Payments\Classes\Application\Usecase\PaymentMethod\CheckPaymentMethodUseCase;

class CheckPaymentMethodUseCaseTest extends ComponentTestCase
{
    private Mockery\MockInterface|PaymentMethodRepositoryInterface $paymentMethodRepository;
    private Mockery\MockInterface|PaymentGatewayInterface $paymentGateway;
    private Mockery\MockInterface|EventDispatcherInterface $eventDispatcher;
    private CheckPaymentMethodUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentMethodRepository = Mockery::mock(PaymentMethodRepositoryInterface::class);
        $this->paymentGateway = Mockery::mock(PaymentGatewayInterface::class);
        $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

        $this->useCase = new CheckPaymentMethodUseCase(
            $this->paymentMethodRepository,
            $this->paymentGateway,
            $this->eventDispatcher,
        );
    }

    public function test_it_throws_exception_if_payment_method_not_found()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Payment method entity not found with ID: 999');

        $this->paymentMethodRepository
            ->shouldReceive('findById')
            ->with(999)
            ->once()
            ->andReturnNull();

        $request = new CheckPaymentMethodRequest(payment_method_id: 999);

        ($this->useCase)($request);
    }

    public function test_it_updates_payment_method_to_success_from_pending()
    {
        $paymentMethodId = 123;
        $request = new CheckPaymentMethodRequest($paymentMethodId);

        $paymentMethod = new PaymentMethod();
        $paymentMethod->setUserId(1)->setStatus(PaymentMethodStatus::pending);

        $this->paymentMethodRepository->shouldReceive('findById')->with($paymentMethodId)->once()->andReturn($paymentMethod);

        $this->eventDispatcher->shouldReceive('dispatch')
            ->once();

        $gatewayResponse = new CheckPaymentMethodGatewayResponse(PaymentMethodStatus::success, '{"status":"succeeded"}');

        $this->paymentGateway
            ->shouldReceive('checkPaymentMethod')
            ->once()
            ->with($paymentMethod)
            ->andReturn($gatewayResponse);

        $this->paymentMethodRepository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (PaymentMethod $savedPaymentMethod) {
                $this->assertEquals(PaymentMethodStatus::success, $savedPaymentMethod->getStatus());
                $this->assertNotNull($savedPaymentMethod->getAcceptedAt());
                $this->assertEquals('{"status":"succeeded"}', $savedPaymentMethod->getGatewayResponse());
                return true;
            }));

        ($this->useCase)($request);
    }

    public function test_it_updates_payment_method_to_canceled_from_pending()
    {
        $paymentMethodId = 123;
        $request = new CheckPaymentMethodRequest($paymentMethodId);

        $paymentMethod = new PaymentMethod();
        $paymentMethod->setUserId(1)->setStatus(PaymentMethodStatus::pending);

        $this->paymentMethodRepository->shouldReceive('findById')->with($paymentMethodId)->once()->andReturn($paymentMethod);

        $gatewayResponse = new CheckPaymentMethodGatewayResponse(PaymentMethodStatus::canceled, '{"status":"canceled"}');

        $this->paymentGateway
            ->shouldReceive('checkPaymentMethod')
            ->once()
            ->with($paymentMethod)
            ->andReturn($gatewayResponse);

        $this->paymentMethodRepository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (PaymentMethod $savedPaymentMethod) {
                $this->assertEquals(PaymentMethodStatus::canceled, $savedPaymentMethod->getStatus());
                $this->assertNull($savedPaymentMethod->getAcceptedAt());
                $this->assertEquals('{"status":"canceled"}', $savedPaymentMethod->getGatewayResponse());
                return true;
            }));

        ($this->useCase)($request);
    }

    public function test_it_updates_payment_method_to_failed_from_pending()
    {
        $paymentMethodId = 123;
        $request = new CheckPaymentMethodRequest($paymentMethodId);

        $paymentMethod = new PaymentMethod();
        $paymentMethod->setUserId(1)->setStatus(PaymentMethodStatus::pending);

        $this->paymentMethodRepository->shouldReceive('findById')->with($paymentMethodId)->once()->andReturn($paymentMethod);

        $gatewayResponse = new CheckPaymentMethodGatewayResponse(PaymentMethodStatus::failed, '{"status":"failed"}');

        $this->paymentGateway
            ->shouldReceive('checkPaymentMethod')
            ->once()
            ->with($paymentMethod)
            ->andReturn($gatewayResponse);

        $this->paymentMethodRepository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (PaymentMethod $savedPaymentMethod) {
                $this->assertEquals(PaymentMethodStatus::failed, $savedPaymentMethod->getStatus());
                $this->assertNull($savedPaymentMethod->getAcceptedAt());
                $this->assertEquals('{"status":"failed"}', $savedPaymentMethod->getGatewayResponse());
                return true;
            }));

        ($this->useCase)($request);
    }
}

