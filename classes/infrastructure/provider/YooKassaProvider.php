<?php

namespace PalPalych\Payments\Classes\Infrastructure\Provider;

use Illuminate\Support\ServiceProvider;
use PalPalych\Payments\Models\Settings;
use Illuminate\Contracts\Foundation\Application;

class YooKassaProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\YooKassa\Client::class, function (Application $app) {
            $shop_id = Settings::get('yookassa_shop_id');
            $secret_key = Settings::get('yookassa_secret_key');
            $client = new \YooKassa\Client();

            return $client->setAuth(
                $shop_id,
                $secret_key
            );
        });
    }
}
