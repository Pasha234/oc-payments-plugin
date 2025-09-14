<?php

namespace PalPalych\Payments\Tests\Unit\Application\Usecase\Payment;

use Mockery;
use RuntimeException;
use ReflectionProperty;
use Tests\ComponentTestCase;
use PalPalych\Payments\Classes\Domain\Entity\Payment;
use PalPalych\Payments\Classes\Domain\Enum\PaymentStatus;
use PalPalych\Payments\Classes\Domain\Entity\PaymentMethod;
use PalPalych\Payments\Tests\Unit\Entities\TestPayableEntity;
use PalPalych\Payments\Classes\Domain\Contract\PayableInterface;
use PalPalych\Payments\Classes\Domain\Event\EventDispatcherInterface;
use PalPalych\Payments\Classes\Domain\Gateway\PaymentGatewayInterface;
use PalPalych\Payments\Classes\Domain\Repository\PayableRepositoryInterface;
use PalPalych\Payments\Classes\Domain\Repository\PaymentRepositoryInterface;
use PalPalych\Payments\Classes\Domain\Repository\PaymentMethodRepositoryInterface;
use PalPalych\Payments\Classes\Application\Dto\Request\CreatePaymentWithPaymentMethodRequest;
use PalPalych\Payments\Classes\Application\Usecase\Payment\CreatePaymentWithPaymentMethodUseCase;
use PalPalych\Payments\Classes\Domain\Dto\Gateway\CreatePaymentWithPaymentMethodGatewayRequest;
use PalPalych\Payments\Classes\Domain\Dto\Gateway\CreatePaymentWithPaymentMethodGatewayResponse;

class CreatePaymentWithPaymentMethodUseCaseTest extends ComponentTestCase
{
    private Mockery\MockInterface|PaymentRepositoryInterface $paymentRepository;
    private Mockery\MockInterface|PaymentGatewayInterface $paymentGateway;
    private Mockery\MockInterface|PayableRepositoryInterface $payableRepository;
    private Mockery\MockInterface|PaymentMethodRepositoryInterface $paymentMethodRepository;
    private Mockery\MockInterface|EventDispatcherInterface $eventDispatcher;
    private CreatePaymentWithPaymentMethodUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentRepository = Mockery::mock(PaymentRepositoryInterface::class);
        $this->paymentGateway = Mockery::mock(PaymentGatewayInterface::class);
        $this->payableRepository = Mockery::mock(PayableRepositoryInterface::class);
        $this->paymentMethodRepository = Mockery::mock(PaymentMethodRepositoryInterface::class);
        $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

        $this->useCase = new CreatePaymentWithPaymentMethodUseCase(
            $this->paymentRepository,
            $this->paymentGateway,
            $this->payableRepository,
            $this->paymentMethodRepository,
            $this->eventDispatcher
        );
    }

    public function test_it_throws_exception_for_invalid_payable_type()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid payable type: InvalidPayable');

        $request = new CreatePaymentWithPaymentMethodRequest(
            userId: 1,
            payableId: 1,
            payableType: 'InvalidPayable',
            paymentMethodId: 1,
            client_email: 'test@mail.com',
        );

        ($this->useCase)($request);
    }

    public function test_it_throws_exception_if_payable_not_found()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Payable entity not found with Type: PalPalych\Payments\Tests\Unit\Entities\TestPayableEntity and ID: 999');

        $this->payableRepository
            ->shouldReceive('findById')
            ->with(999, TestPayableEntity::class)
            ->once()
            ->andReturnNull();

        $request = new CreatePaymentWithPaymentMethodRequest(
            userId: 1,
            payableId: 999,
            payableType: TestPayableEntity::class,
            paymentMethodId: 1,
            client_email: 'test@mail.com',
        );

        ($this->useCase)($request);
    }

    public function test_it_throws_exception_if_payment_method_not_found()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Payment method not found with ID: 888');

        $payable = Mockery::mock(PayableInterface::class);
        $payable->shouldReceive('getPayableAmount')->andReturn(100);
        $this->payableRepository->shouldReceive('findById')->andReturn($payable);

        $this->paymentMethodRepository->shouldReceive('findById')->with(888)->once()->andReturnNull();

        $request = new CreatePaymentWithPaymentMethodRequest(
            userId: 1,
            payableId: 1,
            payableType: TestPayableEntity::class,
            paymentMethodId: 888,
            client_email: 'test@mail.com',
        );

        ($this->useCase)($request);
    }

    public function test_it_throws_exception_if_payment_method_does_not_belong_to_user()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Payment method does not belong to the user.');

        $payable = Mockery::mock(PayableInterface::class);
        $payable->shouldReceive('getPayableAmount')->andReturn(100);
        $this->payableRepository->shouldReceive('findById')->andReturn($payable);

        $paymentMethod = new PaymentMethod();
        $paymentMethod->setUserId(2); // Belongs to user 2
        $this->paymentMethodRepository->shouldReceive('findById')->with(1)->once()->andReturn($paymentMethod);

        $request = new CreatePaymentWithPaymentMethodRequest(
            userId: 1, // Request from user 1
            payableId: 1,
            payableType: TestPayableEntity::class,
            paymentMethodId: 1,
            client_email: 'test@mail.com',
        );

        ($this->useCase)($request);
    }

    public function test_it_successfully_creates_a_payment_with_payment_method()
    {
        $userId = 1;
        $userEmail = 'test@mail.com';
        $payableId = 123;
        $payableType = TestPayableEntity::class;
        $paymentMethodId = 789;
        $paymentId = 456;

        $request = new CreatePaymentWithPaymentMethodRequest(
            $userId,
            $payableId,
            $payableType,
            $paymentMethodId,
            $userEmail,
        );

        $payable = Mockery::mock(PayableInterface::class);
        $payable->shouldReceive('getPayableAmount')->once()->andReturn(1000);
        $payable->shouldReceive('getPayableDescription')->once()->andReturn('Test description');
        $this->payableRepository->shouldReceive('findById')->with($payableId, $payableType)->once()->andReturn($payable);

        $paymentMethod = new PaymentMethod();
        $paymentMethod->setUserId($userId)->setGatewayId('pm_gateway_123');
        $reflectionId = new ReflectionProperty($paymentMethod, 'id');
        $reflectionId->setAccessible(true);
        $reflectionId->setValue($paymentMethod, $paymentMethodId);
        $this->paymentMethodRepository->shouldReceive('findById')->with($paymentMethodId)->once()->andReturn($paymentMethod);

        $this->paymentRepository
            ->shouldReceive('save')
            ->twice()
            ->with(Mockery::on(function (Payment $payment) use ($userId, $payableId, $payableType, $paymentMethodId, $paymentId) {
                $this->assertEquals($userId, $payment->getUserId());
                $this->assertEquals($payableId, $payment->getPayableId());
                $this->assertEquals($payableType, $payment->getPayableType());
                $this->assertEquals($paymentMethodId, $payment->getPaymentMethodId());

                if ($payment->getId() === null) {
                    $reflectionId = new ReflectionProperty($payment, 'id');
                    $reflectionId->setAccessible(true);
                    $reflectionId->setValue($payment, $paymentId);
                }

                return true;
            }));

        $gatewayResponse = new CreatePaymentWithPaymentMethodGatewayResponse(
            '{}',
            '{}',
            'gw_123',
            'idem_123',
            PaymentStatus::pending
        );

        $this->paymentGateway
            ->shouldReceive('createPaymentWithPaymentMethod')
            ->once()
            ->with(
                Mockery::type(CreatePaymentWithPaymentMethodGatewayRequest::class),
            )
            ->andReturn($gatewayResponse);

        $response = ($this->useCase)($request);

        $this->assertEquals($paymentId, $response->payment_id);
    }
}
