<?php

namespace PalPalych\Payments\Models;

use System\Models\SettingModel;

/**
 * @property bool $more_logs
 * @property string $yookassa_shop_id
 * @property string $yookassa_secret_key
 */
class Settings extends SettingModel
{
    /**
     * @var string settingsCode is a unique code for this object
     */
    public $settingsCode = 'payment_settings';

    /**
     * @var mixed settingsFields definition file
     */
    public $settingsFields = 'fields.yaml';

    /**
     * initSettingsData
     */
    public function initSettingsData()
    {
        $this->more_logs = config('palpalych.payments::more_logs');
        $this->yookassa_shop_id = config('palpalych.payments::yookassa.shop_id');
        $this->yookassa_secret_key = config('palpalych.payments::yookassa.secret_key');
    }
}
