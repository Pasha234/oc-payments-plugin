<?php

namespace PalPalych\Payments\Tests\Unit\Application\Usecase\Payment;

use Mockery;
use RuntimeException;
use Tests\ComponentTestCase;
use PalPalych\Payments\Tests\Models\TestPayable;
use PalPalych\Payments\Classes\Domain\Entity\Payment;
use PalPalych\Payments\Classes\Domain\Enum\PaymentStatus;
use PalPalych\Payments\Classes\Domain\Event\PaymentSucceeded;
use PalPalych\Payments\Classes\Domain\Contract\PayableInterface;
use PalPalych\Payments\Classes\Domain\Event\EventDispatcherInterface;
use PalPalych\Payments\Classes\Domain\Gateway\PaymentGatewayInterface;
use PalPalych\Payments\Classes\Application\Dto\Request\CheckPaymentRequest;
use PalPalych\Payments\Classes\Domain\Repository\PayableRepositoryInterface;
use PalPalych\Payments\Classes\Domain\Repository\PaymentRepositoryInterface;
use PalPalych\Payments\Classes\Domain\Dto\Gateway\CheckPaymentGatewayResponse;
use PalPalych\Payments\Classes\Application\Usecase\Payment\CheckPaymentUseCase;

class CheckPaymentUseCaseTest extends ComponentTestCase
{
    private Mockery\MockInterface|PaymentRepositoryInterface $paymentRepository;
    private Mockery\MockInterface|PaymentGatewayInterface $paymentGateway;
    private Mockery\MockInterface|PayableRepositoryInterface $payableRepository;
    private Mockery\MockInterface|EventDispatcherInterface $eventDispatcher;
    private CheckPaymentUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentRepository = Mockery::mock(PaymentRepositoryInterface::class);
        $this->paymentGateway = Mockery::mock(PaymentGatewayInterface::class);
        $this->payableRepository = Mockery::mock(PayableRepositoryInterface::class);
        $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

        $this->useCase = new CheckPaymentUseCase(
            $this->paymentRepository,
            $this->paymentGateway,
            $this->payableRepository,
            $this->eventDispatcher
        );
    }

    public function test_it_throws_exception_if_payment_not_found()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Payment entity not found with ID: 999');

        $this->paymentRepository
            ->shouldReceive('findById')
            ->with(999)
            ->once()
            ->andReturnNull();

        $request = new CheckPaymentRequest(payment_id: 999);

        ($this->useCase)($request);
    }

    public function test_it_updates_payment_to_success_from_pending()
    {
        $paymentId = 123;
        $request = new CheckPaymentRequest($paymentId);

        $payment = new Payment();
        $payment->setUserId(1)->setStatus(PaymentStatus::pending)
        ->setPayableId(999)->setPayableType(TestPayable::class);

        $payable = Mockery::mock(PayableInterface::class);

        $payable->shouldReceive('markAsPaid')
            ->once();

        $this->payableRepository->shouldReceive('findById')
            ->with(999, TestPayable::class)
            ->once()
            ->andReturn($payable);

        $this->eventDispatcher->shouldReceive('dispatch')
            ->once();

        $this->paymentRepository->shouldReceive('findById')->with($paymentId)->once()->andReturn($payment);

        $gatewayResponse = new CheckPaymentGatewayResponse(PaymentStatus::success, '{"status":"succeeded"}');

        $this->paymentGateway
            ->shouldReceive('checkPayment')
            ->once()
            ->with($payment)
            ->andReturn($gatewayResponse);

        $this->paymentRepository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (Payment $savedPayment) {
                $this->assertEquals(PaymentStatus::success, $savedPayment->getStatus());
                $this->assertNotNull($savedPayment->getPaidAt());
                $this->assertEquals('{"status":"succeeded"}', $savedPayment->getGatewayResponse());
                return true;
            }));

        ($this->useCase)($request);
    }

    public function test_it_updates_payment_to_canceled_from_pending()
    {
        $paymentId = 123;
        $request = new CheckPaymentRequest($paymentId);

        $payment = new Payment();
        $payment->setUserId(1)->setStatus(PaymentStatus::pending);

        $this->paymentRepository->shouldReceive('findById')->with($paymentId)->once()->andReturn($payment);

        $gatewayResponse = new CheckPaymentGatewayResponse(PaymentStatus::canceled, '{"status":"canceled"}');

        $this->paymentGateway
            ->shouldReceive('checkPayment')
            ->once()
            ->with($payment)
            ->andReturn($gatewayResponse);

        $this->paymentRepository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (Payment $savedPayment) {
                $this->assertEquals(PaymentStatus::canceled, $savedPayment->getStatus());
                $this->assertNull($savedPayment->getPaidAt());
                $this->assertEquals('{"status":"canceled"}', $savedPayment->getGatewayResponse());
                return true;
            }));

        ($this->useCase)($request);
    }

    public function test_it_updates_payment_to_failed_from_pending()
    {
        $paymentId = 123;
        $request = new CheckPaymentRequest($paymentId);

        $payment = new Payment();
        $payment->setUserId(1)->setStatus(PaymentStatus::pending);

        $this->paymentRepository->shouldReceive('findById')->with($paymentId)->once()->andReturn($payment);

        $gatewayResponse = new CheckPaymentGatewayResponse(PaymentStatus::failed, '{"status":"failed"}');

        $this->paymentGateway
            ->shouldReceive('checkPayment')
            ->once()
            ->with($payment)
            ->andReturn($gatewayResponse);

        $this->paymentRepository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (Payment $savedPayment) {
                $this->assertEquals(PaymentStatus::failed, $savedPayment->getStatus());
                $this->assertNull($savedPayment->getPaidAt());
                $this->assertEquals('{"status":"failed"}', $savedPayment->getGatewayResponse());
                return true;
            }));

        ($this->useCase)($request);
    }
}
