<?php

namespace PalPalych\Payments\Models;

use Model;
use Carbon\CarbonInterface;
use October\Rain\Database\Traits\SoftDelete;
use RainLab\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use October\Rain\Database\Traits\Encryptable;
use PalPalych\Payments\Classes\Domain\Enum\PaymentStatus;
use PalPalych\Payments\Models\Tests\Factories\PaymentFactory;

/**
 * @mixin Builder
 *
 * @property-read int $id
 *
 * @property int $user_id
 * @property ?int $payment_method_id
 * @property ?int $total
 * @property ?string $idempotence_key
 * @property ?object $gateway_request
 * @property ?object $gateway_response
 * @property ?string $gateway_id
 * @property PaymentStatus $status
 *
 * @property ?int $payable_id
 * @property ?string $payable_type
 *
 * @property ?CarbonInterface $paid_at
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 * @property ?CarbonInterface $deleted_at
 *
 * @property ?PaymentMethod $paymentMethod
 * @property ?User $user
 */
class Payment extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use Encryptable;
    use SoftDelete;

    /**
     * @var string table name
     */
    public $table = 'palpalych_payments_payments';

    /**
     * @var array rules for validation
     */
    public $rules = [];

    public $dates = [
        'paid_at',
    ];

    public $fillable = [
        'user_id',
        'total',
        'payable_id',
        'payable_type',
        'idempotence_key',
        'gateway_request',
        'gateway_response',
        'gateway_id',
        'payment_method_id',
    ];

    public $encryptable = ['gateway_request', 'gateway_response'];

    public $jsonable = ['gateway_request', 'gateway_response'];

    public $morphTo = [
        'payable' => []
    ];

    public $casts = [
        'status' => PaymentStatus::class,
    ];

    public $belongsTo = [
        'user' => User::class,
        'paymentMethod' => PaymentMethod::class,
    ];

    public function getStatusOptions(): array
    {
        return PaymentStatus::getOptions();
    }

    public static function factory(): PaymentFactory
    {
        return PaymentFactory::new();
    }

    public function getTotalFormattedAttribute(): string
    {
        return number_format($this->total / 100, 2, thousands_separator: '');
    }
}
