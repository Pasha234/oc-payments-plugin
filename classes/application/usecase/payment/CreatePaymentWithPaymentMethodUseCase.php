<?php

namespace PalPalych\Payments\Classes\Application\Usecase\Payment;

use PalPalych\Payments\Classes\Domain\Dto\Gateway\CreatePaymentWithPaymentMethodGatewayRequest;
use PalPalych\Payments\Classes\Domain\Entity\Payment;
use PalPalych\Payments\Classes\Domain\Enum\PaymentStatus;
use PalPalych\Payments\Classes\Domain\Event\PaymentSucceeded;
use PalPalych\Payments\Classes\Domain\Contract\PayableInterface;
use PalPalych\Payments\Classes\Domain\Event\EventDispatcherInterface;
use PalPalych\Payments\Classes\Domain\Gateway\PaymentGatewayInterface;
use PalPalych\Payments\Classes\Domain\Repository\PayableRepositoryInterface;
use PalPalych\Payments\Classes\Domain\Repository\PaymentRepositoryInterface;
use PalPalych\Payments\Classes\Domain\Repository\PaymentMethodRepositoryInterface;
use PalPalych\Payments\Classes\Application\Dto\Request\CreatePaymentWithPaymentMethodRequest;
use PalPalych\Payments\Classes\Application\Dto\Response\CreatePaymentWithPaymentMethodResponse;

class CreatePaymentWithPaymentMethodUseCase
{
    public function __construct(
        private PaymentRepositoryInterface $paymentRepository,
        private PaymentGatewayInterface $paymentGateway,
        private PayableRepositoryInterface $payableRepository,
        private PaymentMethodRepositoryInterface $paymentMethodRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * @throws \RuntimeException
     */
    public function __invoke(CreatePaymentWithPaymentMethodRequest $request): CreatePaymentWithPaymentMethodResponse
    {
        if (!class_exists($request->payableType) || !is_subclass_of($request->payableType, PayableInterface::class)) {
            throw new \RuntimeException("Invalid payable type: {$request->payableType}");
        }

        $payable = $this->payableRepository->findById(
            $request->payableId,
            $request->payableType,
        );

        if (!$payable) {
            throw new \RuntimeException("Payable entity not found with Type: {$request->payableType} and ID: {$request->payableId}");
        }

        $payment = new Payment();
        $payment->setUserId($request->userId);
        $payment->setPayableId($request->payableId);
        $payment->setPayableType($request->payableType);
        $payment->setTotal($payable->getPayableAmount());

        $paymentMethod = $this->paymentMethodRepository->findById($request->paymentMethodId);
        if (!$paymentMethod) {
            throw new \RuntimeException("Payment method not found with ID: {$request->paymentMethodId}");
        }
        if ($paymentMethod->getUserId() !== $request->userId) {
            // A more secure application might throw a generic "not found" to avoid leaking information.
            throw new \RuntimeException("Payment method does not belong to the user.");
        }
        $payment->setPaymentMethodId($paymentMethod->getId());
        $paymentMethodGatewayId = $paymentMethod->getGatewayId();

        $this->paymentRepository->save($payment);

        $gatewayRequest = new CreatePaymentWithPaymentMethodGatewayRequest(
            $payment,
            $payable,
            $paymentMethodGatewayId,
            $request->client_email,
            $payable->getPayableDescription(),
        );

        $paymentGatewayResponse = $this->paymentGateway->createPaymentWithPaymentMethod($gatewayRequest);

        $payment->setGatewayId($paymentGatewayResponse->gateway_id);
        $payment->setIdempotenceKey($paymentGatewayResponse->idempotence_key);
        $payment->setGatewayRequest($paymentGatewayResponse->gateway_request);
        $payment->setGatewayResponse($paymentGatewayResponse->gateway_response);

        if ($paymentGatewayResponse->status === PaymentStatus::success && $payment->getStatus() === PaymentStatus::pending) {
            $payment->markAsPaid();

            $payable = $this->payableRepository->findById($payment->getPayableId(), $payment->getPayableType());
            if ($payable) {
                $payable->markAsPaid();
            }

            $this->eventDispatcher->dispatch(new PaymentSucceeded($payment));
        } elseif ($paymentGatewayResponse->status === PaymentStatus::canceled && $payment->getStatus() === PaymentStatus::pending) {
            $payment->markAsCanceled();
        } elseif ($paymentGatewayResponse->status === PaymentStatus::failed && $payment->getStatus() === PaymentStatus::pending) {
            $payment->markAsFailed();
        }

        $this->paymentRepository->save($payment);

        return new CreatePaymentWithPaymentMethodResponse(
            $payment->getId(),
        );
    }
}
