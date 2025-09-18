<?php namespace PalPalych\Payments\Console;

use Exception;
use Illuminate\Console\Command;
use PalPalych\Payments\Models\Payment;
use PalPalych\Payments\Classes\Domain\Enum\PaymentStatus;
use PalPalych\Payments\Classes\Application\Dto\Request\CheckPaymentRequest;
use PalPalych\Payments\Classes\Application\Usecase\Payment\CheckPaymentUseCase;

/**
 * CheckPayments Command
 *
 * @link https://docs.octobercms.com/3.x/extend/console-commands.html
 */
class CheckPendingPayments extends Command
{
    /**
     * @var string signature for the console command.
     */
    protected $signature = 'payments:checkpendingpayments';

    /**
     * @var string description is the console command description
     */
    protected $description = 'Check status of pending payments through the configured payment gateway.';

    /**
     * handle executes the console command.
     */
    public function handle()
    {
        $payments = Payment::where('status', PaymentStatus::pending->value)
            ->get();

        if ($payments->isEmpty()) {
            $this->info('No pending payments to check.');
            return;
        }

        $paymentSuccess = 0;
        $paymentCanceled = 0;
        $paymentPending = 0;
        $paymentError = 0;

        /** @var CheckPaymentUseCase $useCase */
        $useCase = app(CheckPaymentUseCase::class);

        foreach ($payments as $payment) {
            try {
                $response = $useCase(new CheckPaymentRequest($payment->id));
                $payment->reload(); // Reload to get the updated status from the database

                if ($payment->status === PaymentStatus::success) $paymentSuccess++;
                elseif ($payment->status === PaymentStatus::canceled) $paymentCanceled++;
                else $paymentPending++;
            } catch (Exception $e) {
                report($e);
                $paymentError++;
            }
        }

        $this->info("Payments checked: {$payments->count()}");
        $this->info(" - Success: {$paymentSuccess}");
        $this->info(" - Canceled: {$paymentCanceled}");
        $this->info(" - Still Pending: {$paymentPending}");
        $this->warn(" - Errors: {$paymentError}");
    }
}
