<?php

namespace PalPalych\Payments\Classes\Application\Usecase\PaymentMethod;

use PalPalych\Payments\Classes\Domain\Enum\PaymentMethodStatus;
use PalPalych\Payments\Classes\Domain\Event\EventDispatcherInterface;
use PalPalych\Payments\Classes\Domain\Event\PaymentMethodActivated;
use PalPalych\Payments\Classes\Domain\Gateway\PaymentGatewayInterface;
use PalPalych\Payments\Classes\Application\Dto\Request\CheckPaymentMethodRequest;
use PalPalych\Payments\Classes\Domain\Repository\PaymentMethodRepositoryInterface;

class CheckPaymentMethodUseCase
{
    public function __construct(
        private PaymentMethodRepositoryInterface $paymentMethodRepository,
        private PaymentGatewayInterface $paymentGateway,
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * @throws \RuntimeException
     */
    public function __invoke(CheckPaymentMethodRequest $request): void
    {
        $paymentMethod = $this->paymentMethodRepository->findById(
            $request->payment_method_id,
        );

        if (!$paymentMethod) {
            throw new \RuntimeException("Payment method entity not found with ID: {$request->payment_method_id}");
        }

        $checkPaymentMethodResponse = $this->paymentGateway->checkPaymentMethod($paymentMethod);

        $paymentMethod->setGatewayResponse($checkPaymentMethodResponse->gateway_response);

        if ($checkPaymentMethodResponse->card_type) {
            $paymentMethod->setCardType($checkPaymentMethodResponse->card_type);
        }
        if ($checkPaymentMethodResponse->expiry_month) {
            $paymentMethod->setExpiryMonth($checkPaymentMethodResponse->expiry_month);
        }
        if ($checkPaymentMethodResponse->expiry_year) {
            $paymentMethod->setExpiryYear($checkPaymentMethodResponse->expiry_year);
        }
        if ($checkPaymentMethodResponse->last4) {
            $paymentMethod->setLast4($checkPaymentMethodResponse->last4);
        }

        if ($checkPaymentMethodResponse->status === PaymentMethodStatus::success && $paymentMethod->getStatus() === PaymentMethodStatus::pending) {
            $paymentMethod->markAsAccepted();

            $this->eventDispatcher->dispatch(new PaymentMethodActivated($paymentMethod));
        } elseif ($checkPaymentMethodResponse->status === PaymentMethodStatus::canceled && $paymentMethod->getStatus() === PaymentMethodStatus::pending) {
            $paymentMethod->markAsCanceled();
        } elseif ($checkPaymentMethodResponse->status === PaymentMethodStatus::failed && $paymentMethod->getStatus() === PaymentMethodStatus::pending) {
            $paymentMethod->markAsFailed();
        }

        $this->paymentMethodRepository->save($paymentMethod);
    }
}
