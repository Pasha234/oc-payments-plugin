<?php

namespace PalPalych\Payments\Classes\Application\Usecase\Webhook;

use Log;
use PalPalych\Payments\Classes\Domain\Enum\PaymentStatus;
use PalPalych\Payments\Classes\Domain\Event\EventDispatcherInterface;
use PalPalych\Payments\Classes\Domain\Event\PaymentMethodActivated;
use PalPalych\Payments\Classes\Domain\Event\PaymentSucceeded;
use PalPalych\Payments\Classes\Domain\Gateway\PaymentGatewayInterface;
use PalPalych\Payments\Classes\Domain\Enum\PaymentMethodStatus;
use PalPalych\Payments\Classes\Application\Dto\Request\HandleWebhookRequest;
use PalPalych\Payments\Classes\Domain\Gateway\PaymentGatewayWebhookStatus;
use PalPalych\Payments\Classes\Domain\Repository\PayableRepositoryInterface;
use PalPalych\Payments\Classes\Domain\Repository\PaymentRepositoryInterface;
use PalPalych\Payments\Classes\Domain\Repository\PaymentMethodRepositoryInterface;
use PalPalych\Payments\Models\PaymentMethod as PaymentMethodModel;
use PalPalych\Payments\Models\Payment as PaymentModel;
use RuntimeException;

class HandleWebhookUseCase
{
    public function __construct(
        private PaymentMethodRepositoryInterface $paymentMethodRepository,
        private PaymentRepositoryInterface $paymentRepository,
        private PaymentGatewayInterface $paymentGateway,
        private PayableRepositoryInterface $payableRepository,
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * @throws \PalPalych\Payments\Classes\Domain\Exception\PaymentGatewayException
     */
    public function __invoke(HandleWebhookRequest $request): void
    {
        $webhookResponse = $this->paymentGateway->handleWebhook(
            $request->payload,
            $request->ip,
            $request->more_logs
        );

        $gatewayId = $webhookResponse->gateway_id;

        switch ($webhookResponse->status) {
            case PaymentGatewayWebhookStatus::payment_success:
                $payment = $this->paymentRepository->findByGatewayId($gatewayId);
                if (!$payment) {
                    throw new RuntimeException("Webhook handler: Payment with gateway ID {$gatewayId} not found.");
                }
                if ($payment->getStatus() !== PaymentStatus::success) {
                    $payment->setStatus(PaymentStatus::success);
                    $payment->setPaidAt(new \DateTimeImmutable());
                    $payment->setGatewayResponse($webhookResponse->gateway_response);
                    $this->paymentRepository->save($payment);

                    $payable = $payment->getPayableId() ?
                        $this->payableRepository->findById($payment->getPayableId(), $payment->getPayableType())
                        : null;

                    if ($payable) {
                        $payable->markAsPaid();
                    }

                    $this->eventDispatcher->dispatch(new PaymentSucceeded($payment));
                }
                break;

            case PaymentGatewayWebhookStatus::payment_canceled:
                $payment = $this->paymentRepository->findByGatewayId($gatewayId);
                if (!$payment) {
                    throw new RuntimeException("Webhook handler: Payment with gateway ID {$gatewayId} not found.");
                }
                if ($payment->getStatus() !== PaymentStatus::canceled) {
                    $payment->setStatus(PaymentStatus::canceled);
                    $payment->setGatewayResponse($webhookResponse->gateway_response);
                    $this->paymentRepository->save($payment);
                }
                break;

            case PaymentGatewayWebhookStatus::payment_waiting_for_capture:
                // This status is treated as failed in this system.
                $payment = $this->paymentRepository->findByGatewayId($gatewayId);
                if (!$payment) {
                    throw new RuntimeException("Webhook handler: Payment with gateway ID {$gatewayId} not found.");
                }
                if ($payment->getStatus() !== PaymentStatus::failed) {
                    $payment->setStatus(PaymentStatus::failed);
                    $payment->setGatewayResponse($webhookResponse->gateway_response);
                    $this->paymentRepository->save($payment);
                }
                break;

            case PaymentGatewayWebhookStatus::payment_method_active:
                $paymentMethod = $this->paymentMethodRepository->findByGatewayId($gatewayId);
                if (!$paymentMethod) {
                    throw new RuntimeException("Webhook handler: PaymentMethod with gateway ID {$gatewayId} not found.");
                }
                if ($paymentMethod->getStatus() !== PaymentMethodStatus::success) {
                    $paymentMethod->setStatus(PaymentMethodStatus::success);
                    $paymentMethod->setAcceptedAt(new \DateTimeImmutable());
                    $paymentMethod->setGatewayResponse($webhookResponse->gateway_response);
                    $this->paymentMethodRepository->save($paymentMethod);

                    $this->eventDispatcher->dispatch(new PaymentMethodActivated($paymentMethod));
                }
                break;
        }
    }
}
