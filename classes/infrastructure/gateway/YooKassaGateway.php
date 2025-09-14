<?php

namespace PalPalych\Payments\Classes\Infrastructure\Gateway;

use Log;
use PalPalych\Payments\Classes\Domain\Entity\Payment;
use PalPalych\Payments\Classes\Infrastructure\Gateway\Yookassa\ReceiptBuilder;
use YooKassa\Model\Notification\NotificationEventType;
use PalPalych\Payments\Classes\Domain\Enum\PaymentStatus;
use PalPalych\Payments\Classes\Domain\Entity\PaymentMethod;
use PalPalych\Payments\Classes\Domain\Enum\PaymentMethodStatus;
use PalPalych\Payments\Classes\Domain\Gateway\PaymentGatewayInterface;
use PalPalych\Payments\Classes\Domain\Exception\PaymentGatewayException;
use PalPalych\Payments\Classes\Domain\Dto\Gateway\WebhookGatewayResponse;
use PalPalych\Payments\Classes\Domain\Gateway\PaymentGatewayWebhookStatus;
use PalPalych\Payments\Classes\Domain\Dto\Gateway\CheckPaymentGatewayResponse;
use PalPalych\Payments\Classes\Domain\Dto\Gateway\CreatePaymentGatewayResponse;
use PalPalych\Payments\Classes\Domain\Exception\PaymentGatewayForbiddenException;
use PalPalych\Payments\Classes\Domain\Dto\Gateway\CheckPaymentMethodGatewayResponse;
use PalPalych\Payments\Classes\Domain\Dto\Gateway\CreatePaymentGatewayRequest;
use PalPalych\Payments\Classes\Domain\Dto\Gateway\CreatePaymentMethodGatewayResponse;
use PalPalych\Payments\Classes\Domain\Dto\Gateway\CreatePaymentWithPaymentMethodGatewayRequest;
use PalPalych\Payments\Classes\Domain\Dto\Gateway\CreatePaymentWithPaymentMethodGatewayResponse;
use YooKassa\Request\Payments\CreatePaymentRequest;
use YooKassa\Request\Payments\CreatePaymentRequestBuilder;

class YooKassaGateway implements PaymentGatewayInterface
{
    public function __construct(
        protected \YooKassa\Client $client
    )
    {

    }

    public function getClient(): \YooKassa\Client
    {
        return $this->client;
    }

    public function createPayment(CreatePaymentGatewayRequest $request): CreatePaymentGatewayResponse
    {
        $idempotenceKey = uniqid('', true);

        $amountInKopecks = $request->payment->getTotal();

        $receipt = (new ReceiptBuilder())->build(
            $request->payable->getReceiptItems(),
            $request->client_email
        )->toArray();

        $requestData = [
            'amount' => array(
                'value' => number_format($amountInKopecks / 100, 2, '.', ''),
                'currency' => 'RUB',
            ),
            'capture' => true,
            'description' => $request->description,
            'receipt' => $receipt,
        ];

        $returnUrl = $request->success_url;
        if (strpos($returnUrl, '?') === false) {
            $returnUrl .= '?payment_id=' . $request->payment->getId();
        } else {
            $returnUrl .= '&payment_id=' . $request->payment->getId();
        }
        $requestData['confirmation'] = [
            'type' => 'redirect',
            'return_url' => $returnUrl,
        ];

        $gateway_request = new CreatePaymentRequest($requestData);

        $response = $this->client->createPayment(
            $gateway_request,
            $idempotenceKey
        );

        $confirmation = $response->getConfirmation();
        $confirmationUrl = $confirmation->getConfirmationUrl();

        return new CreatePaymentGatewayResponse(
            json_encode($requestData),
            json_encode($response->toArray()),
            $response->getId(),
            $idempotenceKey,
            $confirmationUrl,
        );
    }

    public function createPaymentWithPaymentMethod(CreatePaymentWithPaymentMethodGatewayRequest $request): CreatePaymentWithPaymentMethodGatewayResponse
    {
        $idempotenceKey = uniqid('', true);

        $amountInKopecks = $request->payment->getTotal();

        $receipt = (new ReceiptBuilder())->build(
            $request->payable->getReceiptItems(),
            $request->client_email
        );

        $requestData = [
            'amount' => array(
                'value' => number_format($amountInKopecks / 100, 2, '.', ''),
                'currency' => 'RUB',
            ),
            'capture' => true,
            'description' => $request->description,
            'payment_method_id' => $request->paymentMethodGatewayId,
            'receipt' => $receipt,
        ];

        // 3ds карты требуют подтверждения в виде трехзначного кода в тестовом окружении Юкассы,
        // но на проде этого подтверждения не должно быть
        // if ($success_url) {
        //     $returnUrl = $success_url;
        //     if (strpos($returnUrl, '?') === false) {
        //         $returnUrl .= '?payment_id=' . $payment->getId();
        //     } else {
        //         $returnUrl .= '&payment_id=' . $payment->getId();
        //     }
        //     $requestData['confirmation'] = [
        //         'type' => 'redirect',
        //         'return_url' => $returnUrl,
        //     ];
        // }

        $gateway_request = new CreatePaymentRequest($requestData);

        $response = $this->client->createPayment(
            $gateway_request,
            $idempotenceKey
        );

        $status = match ($response->getStatus()) {
            'succeeded' => PaymentStatus::success,
            'canceled' => PaymentStatus::canceled,
            'pending' => PaymentStatus::pending,
            default => PaymentStatus::failed,
        };

        // $confirmation = $response->getConfirmation();
        // $confirmationUrl = $confirmation?->getConfirmationUrl() ?? null;

        return new CreatePaymentWithPaymentMethodGatewayResponse(
            json_encode($requestData),
            json_encode($response->toArray()),
            $response->getId(),
            $idempotenceKey,
            $status
        );
    }

    public function checkPayment(Payment $payment): CheckPaymentGatewayResponse
    {
        if ($payment->getGatewayId() === null) {
            throw new PaymentGatewayException("Payment gateway ID must be a string");
        }
        $paymentInfo = $this->client->getPaymentInfo($payment->getGatewayId());

        $status = match ($paymentInfo->getStatus()) {
            'succeeded' => PaymentStatus::success,
            'canceled' => PaymentStatus::canceled,
            'pending' => PaymentStatus::pending,
            default => PaymentStatus::failed,
        };

        return new CheckPaymentGatewayResponse(
            $status,
            json_encode($paymentInfo)
        );
    }

    public function handleWebhook(array $payload, string $ip, bool $moreLogs = false): WebhookGatewayResponse
    {
        if (!$this->getClient()->isNotificationIPTrusted($ip)) {
            throw new PaymentGatewayForbiddenException('Ip not trusted');
        }

        if ($moreLogs) Log::debug('Webhook data: ' . json_encode($payload, JSON_PRETTY_PRINT));

        try {
            $event = $payload['event'] ?? null;

            if ($event === 'payment_method.active') {
                $responseObject = $payload['object'];
                if (empty($responseObject['id'])) {
                    throw new PaymentGatewayException("YooKassa Webhook Error: 'object.id' is missing for 'payment_method.active' event.");
                }

                if ($moreLogs) Log::info("Processing webhook for entity ID: {$responseObject['id']}, event: $event");

                return new WebhookGatewayResponse(
                    PaymentGatewayWebhookStatus::payment_method_active,
                    $responseObject['id'],
                    json_encode($responseObject)
                );
            }

            $factory = new \YooKassa\Model\Notification\NotificationFactory();
            $notificationObject = $factory->factory($payload);
            $responseObject = $notificationObject->getObject();

            if ($moreLogs) Log::info("Processing webhook for entity ID: {$responseObject->getId()}, status: {$responseObject->getStatus()}, event: {$notificationObject->getEvent()}");

            $status = match ($notificationObject->getEvent()) {
                NotificationEventType::PAYMENT_SUCCEEDED => PaymentGatewayWebhookStatus::payment_success,
                NotificationEventType::PAYMENT_WAITING_FOR_CAPTURE => PaymentGatewayWebhookStatus::payment_waiting_for_capture,
                NotificationEventType::PAYMENT_CANCELED => PaymentGatewayWebhookStatus::payment_canceled,
                default => null,
            };

            if ($status === null) {
                throw new PaymentGatewayException("YooKassa Webhook Error: Unhandled event type '{$notificationObject->getEvent()}'");
            }

            return new WebhookGatewayResponse(
                $status,
                $responseObject->getId(),
                json_encode($responseObject)
            );
        } catch (\Exception $e) {
            throw new PaymentGatewayException('YooKassa Webhook Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
        }
    }

    public function createPaymentMethod(string $success_url): CreatePaymentMethodGatewayResponse
    {
        $idempotenceKey = uniqid('', true);

        $requestData = [
            'type' => 'bank_card',
            'confirmation' => [
                'type' => 'redirect',
                'return_url' => $success_url,
            ],
        ];
        $requestJson = json_encode($requestData);

        try {
            $responseObject = $this->client->getApiClient()->call(
                '/payment_methods',
                'POST',
                [],
                $requestJson,
                ['Idempotence-Key' => $idempotenceKey, 'Content-Type' => 'application/json']
            );

            if ($responseObject->getCode() >= 400) {
                $body = $responseObject->getBody();
                $data = json_decode($body, true) ?: [];
                $description = $data['description'] ?? 'Unknown API error';
                $code = $data['code'] ?? 'unknown';
                throw new PaymentGatewayException("YooKassa API error on creating payment method: {$description} (code: {$code})");
            }

            $responseBody = $responseObject->getBody();
            $responseData = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);

            if (empty($responseData['confirmation']['confirmation_url'])) {
                throw new PaymentGatewayException("YooKassa response did not contain a confirmation URL. Response: " . $responseBody);
            }

            $cardInfo = $responseData['card'] ?? null;

            return new CreatePaymentMethodGatewayResponse(
                $requestJson,
                $responseBody,
                $responseData['id'],
                $idempotenceKey,
                $responseData['confirmation']['confirmation_url'],
                $cardInfo['card_type'] ?? null,
                $cardInfo['last4'] ?? null,
                $cardInfo['expiry_year'] ?? null,
                $cardInfo['expiry_month'] ?? null
            );
        } catch (\YooKassa\Common\Exceptions\ApiConnectionException $e) {
            Log::error("YooKassa API connection error while creating payment method: " . $e->getMessage());
            throw new PaymentGatewayException("Failed to connect to YooKassa.", 0, $e);
        } catch (\JsonException $e) {
            Log::error("YooKassa API response JSON error: " . $e->getMessage());
            throw new PaymentGatewayException("Invalid JSON response from YooKassa.", 0, $e);
        }
    }

    public function checkPaymentMethod(PaymentMethod $paymentMethod): CheckPaymentMethodGatewayResponse
    {
        if ($paymentMethod->getGatewayId() === null) {
            throw new PaymentGatewayException("Gateway ID must be a string");
        }

        try {
            $responseObject = $this->client->getApiClient()->call(
                '/payment_methods/' . $paymentMethod->getGatewayId(),
                'GET',
                [],
                null,
                []
            );

            if ($responseObject->getCode() >= 400) {
                $body = $responseObject->getBody();
                $data = json_decode($body, true) ?: [];
                $description = $data['description'] ?? 'Unknown API error';
                $code = $data['code'] ?? 'unknown';
                throw new PaymentGatewayException("YooKassa API error on getting payment method: {$description} (code: {$code})");
            }

            $responseBody = $responseObject->getBody();
            $responseData = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);

            $status = match ($responseData['status'] ?? null) {
                'active' => PaymentMethodStatus::success,
                'inactive' => PaymentMethodStatus::canceled,
                'pending' => PaymentMethodStatus::pending,
                default => PaymentMethodStatus::failed,
            };

            $cardInfo = $responseData['card'] ?? null;

            return new CheckPaymentMethodGatewayResponse(
                $status,
                $responseBody,
                $cardInfo['card_type'] ?? null,
                $cardInfo['last4'] ?? null,
                $cardInfo['expiry_year'] ?? null,
                $cardInfo['expiry_month'] ?? null,
            );
        } catch (\YooKassa\Common\Exceptions\ApiConnectionException $e) {
            Log::error("YooKassa API connection error while checking payment method: " . $e->getMessage());
            throw new PaymentGatewayException("Failed to connect to YooKassa.", 0, $e);
        } catch (\JsonException $e) {
            Log::error("YooKassa API response JSON error: " . $e->getMessage());
            throw new PaymentGatewayException("Invalid JSON response from YooKassa.", 0, $e);
        }
    }
}
