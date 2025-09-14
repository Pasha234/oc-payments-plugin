<?php

namespace PalPalych\Payments\Classes\Application\Usecase\PaymentMethod;

use PalPalych\Payments\Classes\Application\Dto\Request\DeletePaymentMethodRequest;
use PalPalych\Payments\Classes\Domain\Repository\PaymentMethodRepositoryInterface;

class DeletePaymentMethodUseCase
{
    public function __construct(
        private PaymentMethodRepositoryInterface $paymentMethodRepository,
    ) {
    }

    /**
     * @throws \RuntimeException
     */
    public function __invoke(DeletePaymentMethodRequest $request): void
    {
        $paymentMethod = $this->paymentMethodRepository->findById((int)$request->payment_method_id);

        if (!$paymentMethod) {
            throw new \RuntimeException("Payment method entity not found with ID: {$request->payment_method_id}");
        }

        $this->paymentMethodRepository->delete($paymentMethod);
    }
}
