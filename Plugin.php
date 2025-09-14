<?php

namespace PalPalych\Payments;

use Backend;
use PalPalych\Payments\Classes\Domain\Event\EventDispatcherInterface;
use System\Classes\PluginBase;
use PalPalych\Payments\Models\Settings;
use PalPalych\Payments\Console\CheckPendingPayments;
use PalPalych\Payments\Console\CheckPendingPaymentMethods;
use PalPalych\Payments\Classes\Infrastructure\Subscriber\UserExtend;
use PalPalych\Payments\Classes\Domain\Gateway\PaymentGatewayInterface;
use PalPalych\Payments\Classes\Infrastructure\Gateway\YooKassaGateway;
use PalPalych\Payments\Classes\Domain\Repository\PayableRepositoryInterface;
use PalPalych\Payments\Classes\Domain\Repository\PaymentRepositoryInterface;
use PalPalych\Payments\Classes\Domain\Repository\PaymentMethodRepositoryInterface;
use PalPalych\Payments\Classes\Infrastructure\Event\LaravelEventDispatcher;
use PalPalych\Payments\Classes\Infrastructure\Repository\EloquentPayableRepository;
use PalPalych\Payments\Classes\Infrastructure\Repository\EloquentPaymentRepository;
use PalPalych\Payments\Classes\Infrastructure\Repository\EloquentPaymentMethodRepository;

/**
 * Plugin Information File
 *
 * @link https://docs.octobercms.com/3.x/extend/system/plugins.html
 */
class Plugin extends PluginBase
{
    public $require = ['RainLab.User'];

    /**
     * pluginDetails about this plugin.
     */
    public function pluginDetails()
    {
        return [
            'name' => 'Payments',
            'description' => 'No description provided yet...',
            'author' => 'PalPalych',
            'icon' => 'icon-leaf'
        ];
    }

    /**
     * register method, called when the plugin is first registered.
     */
    public function register()
    {
        $this->app->bind(PaymentGatewayInterface::class, YooKassaGateway::class);
        $this->app->bind(PayableRepositoryInterface::class, EloquentPayableRepository::class);
        $this->app->bind(PaymentMethodRepositoryInterface::class, EloquentPaymentMethodRepository::class);
        $this->app->bind(PaymentRepositoryInterface::class, EloquentPaymentRepository::class);
        $this->app->bind(EventDispatcherInterface::class, LaravelEventDispatcher::class);
        $this->app->singleton(\YooKassa\Client::class, function() {
            $yookassa = new \YooKassa\Client();
            $yookassa->setAuth(
                Settings::get('yookassa_shop_id'),
                Settings::get('yookassa_secret_key'),
            );
            return $yookassa;
        });
        $this->registerConsoleCommand('palpalych.payments.check.payments', CheckPendingPayments::class);
        $this->registerConsoleCommand('palpalych.payments.check.payment_methods', CheckPendingPaymentMethods::class);
    }

    /**
     * boot method, called right before the request route.
     */
    public function boot()
    {
        \Event::subscribe(UserExtend::class);

        $this->publishes([
            __DIR__.'/config/config.php' => config_path('palpalych/payments.php'),
        ], 'config');
    }

    /**
     * registerComponents used by the frontend.
     */
    public function registerComponents()
    {
        return [
            'PalPalych\Payments\Components\PaymentCheck' => 'PaymentCheck',
            'PalPalych\Payments\Components\PaymentMethods' => 'paymentMethods',
        ];
    }

    /**
     * registerPermissions used by the backend.
     */
    public function registerPermissions()
    {
        return []; // Remove this line to activate

        return [
            'palpalych.payments.some_permission' => [
                'tab' => 'Payments',
                'label' => 'Some permission'
            ],
        ];
    }

    /**
     * registerNavigation used by the backend.
     */
    public function registerNavigation()
    {
        return [
            'payments' => [
                'label' => 'Платежи',
                'url' => Backend::url('palpalych/payments/payments'),
                'icon' => 'icon-rub',
                'permissions' => ['palpalych.payments.*'],
                'order' => 500,
                'sideMenu' => [
                    'payments' => [
                        'label' => 'Платежи',
                        'url' => Backend::url('palpalych/payments/payments'),
                        'icon' => 'icon-rub',
                        'permissions' => ['palpalych.payments.*'],
                        'order' => 500,
                    ],
                    'payment_methods' => [
                        'label' => 'Способы оплаты',
                        'url' => Backend::url('palpalych/payments/paymentmethods'),
                        'icon' => 'icon-rub',
                        'permissions' => ['palpalych.payments.*'],
                        'order' => 500,
                    ],
                ]
            ],
        ];
    }

    /**
     * registerSettings
     */
    public function registerSettings()
    {
        return [
            'settings' => [
                'label' => "Платежи",
                'description' => "Платежи и подписки",
                'category' => 'Платежи',
                'icon' => 'icon-rub',
                'class' => Settings::class,
                'order' => 500,
            ]
        ];
    }
}
