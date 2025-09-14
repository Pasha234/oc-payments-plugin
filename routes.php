<?php

use PalPalych\Payments\Classes\Controllers\WebhookController;

Route::post('/yookassa-webhook', [WebhookController::class, 'handle'])
    ->middleware('web');