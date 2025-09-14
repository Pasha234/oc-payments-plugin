<?php

namespace PalPalych\Payments\Classes\Application\Usecase\Payment;

use PalPalych\Payments\Classes\Domain\Dto\Gateway\CreatePaymentGatewayRequest;
use PalPalych\Payments\Classes\Domain\Entity\Payment;
use PalPalych\Payments\Classes\Domain\Contract\PayableInterface;
use PalPalych\Payments\Classes\Domain\Gateway\PaymentGatewayInterface;
use PalPalych\Payments\Classes\Application\Dto\Request\CreatePaymentRequest;
use PalPalych\Payments\Classes\Domain\Repository\PayableRepositoryInterface;
use PalPalych\Payments\Classes\Domain\Repository\PaymentRepositoryInterface;
use PalPalych\Payments\Classes\Application\Dto\Response\CreatePaymentResponse;

class CreatePaymentUseCase
{
    public function __construct(
        private PaymentRepositoryInterface $paymentRepository,
        private PaymentGatewayInterface $paymentGateway,
        private PayableRepositoryInterface $payableRepository,
    ) {
    }

    /**
     * @throws \RuntimeException
     */
    public function __invoke(CreatePaymentRequest $request): CreatePaymentResponse
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

        $this->paymentRepository->save($payment);

        $paymentGatewayResponse = $this->paymentGateway->createPayment(new CreatePaymentGatewayRequest(
            $payment,
            $payable,
            $request->success_url,
            $request->client_email,
            $payable->getPayableDescription(),
        ));

        $payment->setGatewayId($paymentGatewayResponse->gateway_id);
        $payment->setIdempotenceKey($paymentGatewayResponse->idempotence_key);
        $payment->setGatewayRequest($paymentGatewayResponse->gateway_request);
        $payment->setGatewayResponse($paymentGatewayResponse->gateway_response);

        $this->paymentRepository->save($payment);

        return new CreatePaymentResponse($paymentGatewayResponse->confirmation_url);
    }
}
