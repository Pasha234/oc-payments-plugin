<?php

namespace PalPalych\Payments\Tests\Unit\Application\Usecase\PaymentMethod;

use Mockery;
use RuntimeException;
use Tests\ComponentTestCase;
use PalPalych\Payments\Classes\Domain\Entity\PaymentMethod;
use PalPalych\Payments\Classes\Application\Dto\Request\DeletePaymentMethodRequest;
use PalPalych\Payments\Classes\Domain\Repository\PaymentMethodRepositoryInterface;
use PalPalych\Payments\Classes\Application\Usecase\PaymentMethod\DeletePaymentMethodUseCase;

class DeletePaymentMethodUseCaseTest extends ComponentTestCase
{
    private Mockery\MockInterface|PaymentMethodRepositoryInterface $paymentMethodRepository;
    private DeletePaymentMethodUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentMethodRepository = Mockery::mock(PaymentMethodRepositoryInterface::class);

        $this->useCase = new DeletePaymentMethodUseCase(
            $this->paymentMethodRepository
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

        $request = new DeletePaymentMethodRequest(payment_method_id: '999');

        ($this->useCase)($request);
    }

    public function test_it_successfully_deletes_a_payment_method()
    {
        $paymentMethodId = 123;
        $request = new DeletePaymentMethodRequest((string)$paymentMethodId);

        $paymentMethod = new PaymentMethod();

        $this->paymentMethodRepository
            ->shouldReceive('findById')
            ->with($paymentMethodId)
            ->once()
            ->andReturn($paymentMethod);

        $this->paymentMethodRepository
            ->shouldReceive('delete')
            ->once()
            ->with($paymentMethod);

        ($this->useCase)($request);
    }
}

