<?php

namespace PalPalych\Payments\Classes\Application\Usecase\Payment;

use PalPalych\Payments\Models\Payment as PaymentModel;
use PalPalych\Payments\Classes\Domain\Enum\PaymentStatus;
use PalPalych\Payments\Classes\Domain\Event\PaymentSucceeded;
use PalPalych\Payments\Classes\Domain\Event\EventDispatcherInterface;
use PalPalych\Payments\Classes\Domain\Gateway\PaymentGatewayInterface;
use PalPalych\Payments\Classes\Application\Dto\Request\CheckPaymentRequest;
use PalPalych\Payments\Classes\Domain\Repository\PayableRepositoryInterface;
use PalPalych\Payments\Classes\Domain\Repository\PaymentRepositoryInterface;
use PalPalych\Payments\Classes\Application\Dto\Response\CheckPaymentResponse;

class CheckPaymentUseCase
{
    public function __construct(
        private PaymentRepositoryInterface $paymentRepository,
        private PaymentGatewayInterface $paymentGateway,
        private PayableRepositoryInterface $payableRepository,
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * @throws \RuntimeException
     */
    public function __invoke(CheckPaymentRequest $request): CheckPaymentResponse
    {
        $payment = $this->paymentRepository->findById(
            $request->payment_id,
        );

        if (!$payment) {
            throw new \RuntimeException("Payment entity not found with ID: {$request->payment_id}");
        }

        $checkPaymentResponse = $this->paymentGateway->checkPayment($payment);

        $payment->setGatewayResponse($checkPaymentResponse->gateway_response);

        if ($checkPaymentResponse->status === PaymentStatus::success && $payment->getStatus() === PaymentStatus::pending) {
            $payment->markAsPaid();

            $payable = $payment->getPayableId() ?
                $this->payableRepository->findById($payment->getPayableId(), $payment->getPayableType())
                : null;

            if ($payable) {
                $payable->markAsPaid();
            }

            $this->eventDispatcher->dispatch(new PaymentSucceeded($payment));
        } elseif ($checkPaymentResponse->status === PaymentStatus::canceled && $payment->getStatus() === PaymentStatus::pending) {
            $payment->markAsCanceled();
        } elseif ($checkPaymentResponse->status === PaymentStatus::failed && $payment->getStatus() === PaymentStatus::pending) {
            $payment->markAsFailed();
        }

        $this->paymentRepository->save($payment);

        return new CheckPaymentResponse(
            $checkPaymentResponse->status,
            $checkPaymentResponse->confirmation_url
        );
    }
}
