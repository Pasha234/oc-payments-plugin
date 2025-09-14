<?php namespace PalPalych\Payments\Console;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PalPalych\Payments\Classes\YooKassa;
use PalPalych\Payments\Models\Payment\PaymentStatus;
use PalPalych\Payments\Models\PaymentMethod;

/**
 * CheckPayments Command
 *
 * @link https://docs.octobercms.com/3.x/extend/console-commands.html
 */
class CheckPendingPaymentMethods extends Command
{
    /**
     * @var string signature for the console command.
     */
    protected $signature = 'payments:checkpendingpaymentmethods';

    /**
     * @var string description is the console command description
     */
    protected $description = 'Check status of pending payment methods through yookassa API';

    /**
     * handle executes the console command.
     */
    public function handle()
    {
        $yk = new YooKassa();

        $paymentMethods = PaymentMethod::where('status', PaymentStatus::pending->value)
            ->get();

        $paymentSuccess = 0;
        $paymentCanceled = 0;
        $paymentPending = 0;
        $paymentError = 0;
        foreach ($paymentMethods as $paymentMethod) {
            try {
                $yk->checkPaymentMethod($paymentMethod);
                if ($paymentMethod->status == PaymentStatus::success) {
                    $paymentSuccess++;
                } else if ($paymentMethod->status == PaymentStatus::canceled) {
                    $paymentCanceled++;
                } else {
                    $paymentPending++;
                }
            } catch (Exception $e) {
                report($e);
                $paymentError++;
            }
        }

        $message = "Payment methods success: {$paymentSuccess}". PHP_EOL .
            "Payment methods waiting for response: {$paymentPending}" . PHP_EOL .
            "Payment methods canceled: {$paymentCanceled}" . PHP_EOL .
            "Payment methods with error: {$paymentError}";

        $this->output->writeln($message);
    }
}
