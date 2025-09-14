<?php

namespace PalPalych\Payments\Classes\Application\Usecase\PaymentMethod;

use PalPalych\Payments\Classes\Domain\Entity\PaymentMethod;
use PalPalych\Payments\Classes\Domain\Gateway\PaymentGatewayInterface;
use PalPalych\Payments\Classes\Application\Dto\Request\CreatePaymentMethodRequest;
use PalPalych\Payments\Classes\Domain\Repository\PaymentMethodRepositoryInterface;
use PalPalych\Payments\Classes\Application\Dto\Response\CreatePaymentMethodResponse;

class CreatePaymentMethodUseCase
{
    public function __construct(
        private PaymentMethodRepositoryInterface $paymentMethodRepository,
        private PaymentGatewayInterface $paymentGateway,
    ) {
    }

    /**
     * @throws \RuntimeException
     */
    public function __invoke(CreatePaymentMethodRequest $request): CreatePaymentMethodResponse
    {
        $paymentMethod = new PaymentMethod();
        $paymentMethod->setUserId($request->userId);

        $this->paymentMethodRepository->save($paymentMethod);

        $success_url = $request->success_url . "?payment_method_id={$paymentMethod->getId()}";

        $response = $this->paymentGateway->createPaymentMethod($success_url);

        $paymentMethod->setGatewayId($response->gateway_id);
        $paymentMethod->setGatewayRequest($response->gateway_request);
        $paymentMethod->setGatewayResponse($response->gateway_response);
        $paymentMethod->setIdempotenceKey($response->idempotence_key);

        $paymentMethod->setLast4($response->last4);
        $paymentMethod->setCardType($response->card_type);
        $paymentMethod->setExpiryMonth($response->expiry_month);
        $paymentMethod->setExpiryYear($response->expiry_year);

        $this->paymentMethodRepository->save($paymentMethod);

        return new CreatePaymentMethodResponse($response->confirmation_url, $paymentMethod->getId());
    }
}
