<?php

namespace PalPalych\Payments\Tests\Integration\Application\Usecase\Webhook;

use PalPalych\Payments\Classes\Application\Dto\Request\HandleWebhookRequest;
use PalPalych\Payments\Classes\Application\Usecase\Webhook\HandleWebhookUseCase;
use PalPalych\Payments\Classes\Domain\Enum\PaymentMethodStatus;
use PalPalych\Payments\Classes\Domain\Enum\PaymentStatus;
use PalPalych\Payments\Models\Payment;
use PalPalych\Payments\Models\PaymentMethod;
use PalPalych\Payments\Tests\Models\Factory\UserFactory;
use RainLab\User\Models\User;
use Tests\ComponentTestCase;
use YooKassa\Model\Notification\NotificationEventType;

class HandleWebhookUseCaseIntegrationTest extends ComponentTestCase
{
    protected $refreshPlugins = [
        'RainLab.User',
        'PalPalych.Payments',
    ];

    public function test_it_handles_payment_succeeded_webhook()
    {
        // 1. Arrange
        /** @var User $user */
        $user = app(UserFactory::class)->create();
        /** @var Payment $payment */
        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'status' => PaymentStatus::pending->value,
            'gateway_id' => '2d2d10f0-000f-5000-8000-1f2b3c4d5e6f',
            'paid_at' => null,
        ]);

        $payload = [
            'type' => 'notification',
            'event' => NotificationEventType::PAYMENT_SUCCEEDED,
            'object' => [
                'id' => $payment->gateway_id,
                'status' => 'succeeded',
                'amount' => ['value' => '100.00', 'currency' => 'RUB'],
                'paid' => true,
                'created_at' => '2023-01-01T00:00:00.000Z',
                'recipient' => [
                    'account_id' => '57a5667a-d943-4084-8589-c9da2ee819e8',
                    'gateway_id' => '652b4eff-7526-4402-82f3-0dd460298fa4',
                ],
                'refundable' => true,
                'test' => false,
            ],
        ];

        // Mock YooKassa Client IP check
        $yooKassaClientMock = $this->mock(\YooKassa\Client::class);
        $yooKassaClientMock->shouldReceive('isNotificationIPTrusted')->andReturn(true);

        // 2. Act
        $request = new HandleWebhookRequest(
            payload: $payload,
            ip: '127.0.0.1',
            more_logs: false
        );
        /** @var HandleWebhookUseCase $useCase */
        $useCase = $this->app->make(HandleWebhookUseCase::class);
        $useCase($request);

        // 3. Assert
        $this->assertDatabaseHas('palpalych_payments_payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::success->value,
        ]);

        $updatedPayment = Payment::find($payment->id);
        $this->assertNotNull($updatedPayment->paid_at);
        $this->assertJsonStringEqualsJsonString(json_encode($payload['object']), json_encode($updatedPayment->gateway_response));
    }

    public function test_it_handles_payment_method_saved_webhook()
    {
        // 1. Arrange
        /** @var User $user */
        $user = app(UserFactory::class)->create();
        /** @var PaymentMethod $paymentMethod */
        $paymentMethod = PaymentMethod::factory()->create([
            'user_id' => $user->id,
            'status' => PaymentMethodStatus::pending->value,
            'gateway_id' => '2d2d10f0-000f-5000-8000-1f2b3c4d5e6f',
            'accepted_at' => null,
        ]);

        $payload = [
            'type' => 'notification',
            'event' => 'payment_method.active',
            'object' => [
                'id' => $paymentMethod->gateway_id,
                'type' => 'bank_card',
                'saved' => true,
                'title' => 'Card **** 4444',
                'card' => [
                    'first6' => '555555',
                    'last4' => '4444',
                    'expiry_year' => '2025',
                    'expiry_month' => '12',
                    'card_type' => 'MasterCard',
                ],
            ],
        ];

        // Mock YooKassa Client IP check
        $yooKassaClientMock = $this->mock(\YooKassa\Client::class);
        $yooKassaClientMock->shouldReceive('isNotificationIPTrusted')->andReturn(true);

        // 2. Act
        $request = new HandleWebhookRequest(
            payload: $payload,
            ip: '127.0.0.1',
            more_logs: false
        );
        /** @var HandleWebhookUseCase $useCase */
        $useCase = $this->app->make(HandleWebhookUseCase::class);
        $useCase($request);

        // 3. Assert
        $this->assertDatabaseHas('palpalych_payments_payment_methods', [
            'id' => $paymentMethod->id,
            'status' => PaymentMethodStatus::success->value,
        ]);

        $updatedPaymentMethod = PaymentMethod::find($paymentMethod->id);
        $this->assertNotNull($updatedPaymentMethod->accepted_at);
        $this->assertJsonStringEqualsJsonString(json_encode($payload['object']), json_encode($updatedPaymentMethod->gateway_response));
    }
}
