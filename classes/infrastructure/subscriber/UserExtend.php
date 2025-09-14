<?php

namespace PalPalych\Payments\Classes\Infrastructure\Subscriber;

use October\Rain\Database\Collection;
use PalPalych\Payments\Models\Payment;
use RainLab\User\Models\User as UserModel;
use PalPalych\Payments\Models\PaymentMethod;
use PalPalych\Payments\Classes\Domain\Enum\PaymentStatus;

class UserExtend
{
    public function subscribe()
    {
        UserModel::extend(function (UserModel $model) {
            $model->hasMany = array_merge($model->hasMany, [
                'payments' => Payment::class,
                'payment_methods' => PaymentMethod::class,
            ]);

            $model->addDynamicMethod('getActivatedPaymentMethod', function() use ($model) {
                /** @var Collection */
                $paymentMethods = $model->payment_methods;

                return $paymentMethods->where('status', PaymentStatus::success)->whereNotNull('accepted_at')->first();
            });
        });
    }
}
