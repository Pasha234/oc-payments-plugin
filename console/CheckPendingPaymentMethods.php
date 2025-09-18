<?php namespace PalPalych\Payments\Console;

use Exception;
use Illuminate\Console\Command;
use PalPalych\Payments\Models\PaymentMethod;
use PalPalych\Payments\Classes\Domain\Enum\PaymentMethodStatus;
use PalPalych\Payments\Classes\Application\Dto\Request\CheckPaymentMethodRequest;
use PalPalych\Payments\Classes\Application\Usecase\PaymentMethod\CheckPaymentMethodUseCase;

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
    protected $description = 'Check status of pending payment methods through the configured payment gateway.';

    /**
     * handle executes the console command.
     */
    public function handle()
    {
        $paymentMethods = PaymentMethod::where('status', PaymentMethodStatus::pending->value)
            ->get();

        if ($paymentMethods->isEmpty()) {
            $this->info('No pending payment methods to check.');
            return;
        }

        $successCount = 0;
        $canceledCount = 0;
        $pendingCount = 0;
        $errorCount = 0;

        /** @var CheckPaymentMethodUseCase $useCase */
        $useCase = app(CheckPaymentMethodUseCase::class);

        foreach ($paymentMethods as $paymentMethod) {
            try {
                $useCase(new CheckPaymentMethodRequest($paymentMethod->id));
                $paymentMethod->reload(); // Reload to get the updated status from the database

                if ($paymentMethod->status === PaymentMethodStatus::success) $successCount++;
                elseif ($paymentMethod->status === PaymentMethodStatus::canceled) $canceledCount++;
                else $pendingCount++;
            } catch (Exception $e) {
                report($e);
                $errorCount++;
            }
        }

        $this->info("Payment methods checked: {$paymentMethods->count()}");
        $this->info(" - Activated: {$successCount}");
        $this->info(" - Canceled: {$canceledCount}");
        $this->info(" - Still Pending: {$pendingCount}");
        $this->warn(" - Errors: {$errorCount}");
    }
}
