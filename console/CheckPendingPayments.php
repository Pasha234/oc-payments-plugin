<?php namespace PalPalych\Payments\Console;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PalPalych\Payments\Models\Payment;
use PalPalych\Payments\Classes\YooKassa;
use PalPalych\Payments\Models\Payment\PaymentStatus;

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
    protected $description = 'Check status of pending payments through yookassa API';

    /**
     * handle executes the console command.
     */
    public function handle()
    {
        $yk = new YooKassa();

        $payments = Payment::where('status', PaymentStatus::pending->value)
            ->get();

        $paymentSuccess = 0;
        $paymentCanceled = 0;
        $paymentPending = 0;
        $paymentError = 0;
        foreach ($payments as $payment) {
            try {
                $yk->checkPayment($payment);
                if ($payment->status == PaymentStatus::success) {
                    $paymentSuccess++;
                } else if ($payment->status == PaymentStatus::canceled) {
                    $paymentCanceled++;
                } else {
                    $paymentPending++;
                }
            } catch (Exception $e) {
                report($e);
                $paymentError++;
            }
        }

        $message = "Payments success: {$paymentSuccess}". PHP_EOL .
            "Payments waiting for response: {$paymentPending}" . PHP_EOL .
            "Payments canceled: {$paymentCanceled}" . PHP_EOL .
            "Payments with error: {$paymentError}";

        $this->output->writeln($message);
    }
}
