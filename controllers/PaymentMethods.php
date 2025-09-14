<?php namespace PalPalych\Payments\Controllers;

use BackendMenu;
use Backend\Classes\Controller;

/**
 * Payment Methods Backend Controller
 *
 * @link https://docs.octobercms.com/3.x/extend/system/controllers.html
 */
class PaymentMethods extends Controller
{
    public $implement = [
        \Backend\Behaviors\FormController::class,
        \Backend\Behaviors\ListController::class,
    ];

    /**
     * @var string formConfig file
     */
    public $formConfig = 'config_form.yaml';

    /**
     * @var string listConfig file
     */
    public $listConfig = 'config_list.yaml';

    /**
     * @var array required permissions
     */
    public $requiredPermissions = ['palpalych.payments.paymentmethods'];

    /**
     * __construct the controller
     */
    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('PalPalych.Payments', 'payments', 'payment_methods');

        $this->addCss('/plugins/palpalych/payments/assets/css/jjsonviewer.css');
        $this->addJs('/plugins/palpalych/payments/assets/js/jjsonviewer.js');
    }
}
