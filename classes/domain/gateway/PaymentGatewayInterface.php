<?php

namespace PalPalych\Payments\Classes\Domain\Gateway;

use PalPalych\Payments\Classes\Domain\Entity\Payment;
use PalPalych\Payments\Classes\Domain\Dto\Gateway\CheckPaymentGatewayResponse;
use PalPalych\Payments\Classes\Domain\Dto\Gateway\CheckPaymentMethodGatewayResponse;
use PalPalych\Payments\Classes\Domain\Dto\Gateway\CreatePaymentGatewayRequest;
use PalPalych\Payments\Classes\Domain\Dto\Gateway\CreatePaymentGatewayResponse;
use PalPalych\Payments\Classes\Domain\Dto\Gateway\CreatePaymentMethodGatewayResponse;
use PalPalych\Payments\Classes\Domain\Dto\Gateway\CreatePaymentWithPaymentMethodGatewayRequest;
use PalPalych\Payments\Classes\Domain\Dto\Gateway\CreatePaymentWithPaymentMethodGatewayResponse;
use PalPalych\Payments\Classes\Domain\Dto\Gateway\WebhookGatewayResponse;
use PalPalych\Payments\Classes\Domain\Entity\PaymentMethod;

interface PaymentGatewayInterface
{
    /**
     * Create a new payment request for a payable item.
     */
    public function createPayment(CreatePaymentGatewayRequest $request): CreatePaymentGatewayResponse;

    /**
     * Create a new payment request for a payable item with saved payment method.
     */
    public function createPaymentWithPaymentMethod(CreatePaymentWithPaymentMethodGatewayRequest $request): CreatePaymentWithPaymentMethodGatewayResponse;

    /**
     * Check the payment status from the payment provider.
     */
    public function checkPayment(Payment $payment): CheckPaymentGatewayResponse;

    /**
     * Handle an incoming webhook from the payment provider.
     */
    public function handleWebhook(array $payload, string $ip, bool $moreLogs = false): WebhookGatewayResponse;

    /**
     * Create a new payment method request.
     */
    public function createPaymentMethod(string $success_url): CreatePaymentMethodGatewayResponse;

    /**
     * Check the payment method status from the payment provider.
     */
    public function checkPaymentMethod(PaymentMethod $paymentMethod): CheckPaymentMethodGatewayResponse;
}
