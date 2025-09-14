<?php

namespace PalPalych\Payments\Components;

use RainLab\User\Models\User;
use Cms\Classes\ComponentBase;
use October\Rain\Support\Facades\Auth;
use PalPalych\Payments\Models\PaymentMethod;
use PalPalych\Payments\Classes\Application\Dto\Request\CreatePaymentMethodRequest;
use PalPalych\Payments\Classes\Application\Dto\Request\DeletePaymentMethodRequest;
use PalPalych\Payments\Classes\Domain\Repository\PaymentMethodRepositoryInterface;
use PalPalych\Payments\Classes\Application\Usecase\PaymentMethod\CreatePaymentMethodUseCase;
use PalPalych\Payments\Classes\Application\Usecase\PaymentMethod\DeletePaymentMethodUseCase;
use PalPalych\Payments\Classes\Domain\Enum\PaymentMethodStatus;

class PaymentMethods extends ComponentBase
{
    public ?User $user;
    public $paymentMethods;

    public function componentDetails()
    {
        return [
            'name'        => 'Payment Methods Component',
            'description' => 'Displays and manages user payment methods.'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function init()
    {
        $this->user = Auth::user();
    }

    public function onRun()
    {
        if (!$this->user) {
            return;
        }
        $this->paymentMethods = $this->page['paymentMethods'] = $this->loadPaymentMethods();
    }

    protected function loadPaymentMethods()
    {
        return PaymentMethod::where('user_id', $this->user->id)
            ->where('status', PaymentMethodStatus::success)
            ->get();
    }

    public function onAdd()
    {
        $user = Auth::user();
        if (!$user) {
            throw new \ApplicationException('Not authorized');
        }

        /** @var CreatePaymentMethodUseCase $useCase */
        $useCase = app(CreatePaymentMethodUseCase::class);

        $successUrl = $this->controller->currentPageUrl();

        $request = new CreatePaymentMethodRequest(
            userId: $user->id,
            success_url: $successUrl
        );

        $response = $useCase($request);

        return \Redirect::to($response->confirmation_url);
    }

    public function onDelete()
    {
        $user = Auth::user();
        if (!$user) {
            throw new \ApplicationException('Not authorized');
        }

        $paymentMethodId = post('payment_method_id');
        if (!$paymentMethodId) {
            throw new \ApplicationException('Payment Method ID is required.');
        }

        /** @var PaymentMethodRepositoryInterface $repository */
        $repository = app(PaymentMethodRepositoryInterface::class);
        $paymentMethod = $repository->findById((int)$paymentMethodId);

        if (!$paymentMethod || $paymentMethod->getUserId() !== $user->id) {
            throw new \ApplicationException('Payment method not found or access denied.');
        }

        /** @var DeletePaymentMethodUseCase $useCase */
        $useCase = app(DeletePaymentMethodUseCase::class);
        $request = new DeletePaymentMethodRequest((string)$paymentMethodId);
        $useCase($request);

        $this->paymentMethods = $this->page['paymentMethods'] = $this->loadPaymentMethods();
    }
}
