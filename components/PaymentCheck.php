<?php

namespace PalPalych\Payments\Components;

use Auth;
use Exception;
use RainLab\User\Models\User;
use Cms\Classes\ComponentBase;
use PalPalych\Payments\Models\Payment;
use PalPalych\Payments\Models\PaymentMethod;
use PalPalych\Payments\Classes\Domain\Enum\PaymentStatus;
use PalPalych\Payments\Classes\Domain\Enum\PaymentMethodStatus;
use PalPalych\Payments\Classes\Application\Dto\Request\CheckPaymentRequest;
use PalPalych\Payments\Classes\Application\Usecase\Payment\CheckPaymentUseCase;
use PalPalych\Payments\Classes\Application\Dto\Request\CheckPaymentMethodRequest;
use PalPalych\Payments\Classes\Application\Usecase\PaymentMethod\CheckPaymentMethodUseCase;

class PaymentCheck extends ComponentBase
{
    protected ?Payment $payment = null;
    protected ?PaymentMethod $paymentMethod = null;
    protected ?User $user = null;

    public function componentDetails()
    {
        return [
            'name' => 'PaymentCheck',
            'description' => 'Checks payment or payment method status upon redirect from gateway.'
        ];
    }

    /**
     * @link https://docs.octobercms.com/3.x/element/inspector-types.html
     */
    public function defineProperties()
    {
        return [];
    }

    public function onRun()
    {
        if (!input('payment_method_id') && !input('payment_id')) {
            return;
        }

        $this->user = Auth::user();

        if (!$this->user) {
            return;
        }

        if (input('payment_method_id')) {
            $this->checkPaymentMethod();
        } elseif (input('payment_id')) {
            $this->checkPayment();
        }
    }

    public function checkPaymentMethod()
    {
        $this->paymentMethod = PaymentMethod::find(input('payment_method_id'));

        if (!$this->paymentMethod || $this->paymentMethod->user_id != $this->user->id) {
            return $this->controller->run(404);
        }

        if ($this->paymentMethod->status == PaymentMethodStatus::pending) {
            try {
                /** @var CheckPaymentMethodUseCase $useCase */
                $useCase = app(CheckPaymentMethodUseCase::class);
                $request = new CheckPaymentMethodRequest($this->paymentMethod->id);
                $useCase($request);
                $this->paymentMethod->reload();
            } catch (Exception $e) {
                report($e);
            }
        }
    }

    public function checkPayment()
    {
        $this->payment = Payment::find(input('payment_id'));

        if (!$this->payment || $this->payment->user_id != $this->user->id) {
            return $this->controller->run(404);
        }

        if ($this->payment->status == PaymentStatus::pending) {
            try {
                /** @var CheckPaymentUseCase $useCase */
                $useCase = app(CheckPaymentUseCase::class);
                $request = new CheckPaymentRequest($this->payment->id);
                $useCase($request);
                $this->payment->reload();
            } catch (Exception $e) {
                report($e);
            }
        }
    }

    public function getStatus(): ?string
    {
        if ($this->paymentMethod) {
            return $this->paymentMethod->status->name;
        }
        if ($this->payment) {
            return $this->payment->status->name;
        }
        return null;
    }
}
