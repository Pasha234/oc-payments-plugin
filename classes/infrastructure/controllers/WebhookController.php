<?php

namespace PalPalych\Payments\Classes\Controllers;

use Exception;
use Log;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use PalPalych\Payments\Models\Settings;
use PalPalych\Payments\Classes\Application\Dto\Request\HandleWebhookRequest;
use PalPalych\Payments\Classes\Application\Usecase\Webhook\HandleWebhookUseCase;
use PalPalych\Payments\Classes\Domain\Exception\PaymentGatewayException;
use PalPalych\Payments\Classes\Domain\Exception\PaymentGatewayForbiddenException;
use RuntimeException;

class WebhookController extends Controller
{
    public function handle(Request $request, HandleWebhookUseCase $useCase): JsonResponse
    {
        $moreLogs = Settings::get('more_logs', false);

        try {
            $payload = $request->all();
            if (empty($payload)) {
                throw new PaymentGatewayException('Webhook payload is empty.');
            }

            $useCase(new HandleWebhookRequest($payload, $request->ip(), $moreLogs));

            return response()->json(['status' => 'ok']);
        } catch (PaymentGatewayForbiddenException $e) {
            Log::warning('Webhook Forbidden: ' . $e->getMessage(), ['ip' => $request->ip()]);
            return response()->json(['error' => 'Forbidden'], 403);
        } catch (RuntimeException $e) {
            Log::warning('Webhook Inalid Request: ' . $e->getMessage() . " \nFull trace: " . $e->getTraceAsString());
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (Exception $e) {
            Log::error('Webhook Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Webhook processing error'], 500);
        }
    }
}
