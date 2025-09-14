<?php namespace PalPalych\Payments\Models;

use Model;
use Carbon\CarbonInterface;
use RainLab\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use October\Rain\Database\Traits\Encryptable;
use PalPalych\Payments\Classes\Domain\Enum\PaymentMethodStatus;
use PalPalych\Payments\Models\Tests\Factories\PaymentMethodFactory;

/**
 * @mixin Builder
 *
 * @property-read int $id
 *
 * @property int $user_id
 * @property ?string $idempotence_key
 * @property ?object $gateway_request
 * @property ?object $gateway_response
 * @property ?string $gateway_id

 * @property PaymentMethodStatus $status
 * @property ?string $card_type
 * @property ?string $last4
 * @property ?string $expiry_year
 * @property ?string $expiry_month
 *
 * @property ?CarbonInterface $accepted_at
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 *
 * @property User $user
 */
class PaymentMethod extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use Encryptable;

    /**
     * @var string table name
     */
    public $table = 'palpalych_payments_payment_methods';

    /**
     * @var array rules for validation
     */
    public $rules = [];

    public $dates = [
        'accepted_at',
    ];

    public $casts = [
        'status' => PaymentMethodStatus::class,
    ];

    public $fillable = [
        'user_id',
        'idempotence_key',
        'gateway_request',
        'gateway_response',
        'gateway_id',
        'card_type',
        'last4',
        'expiry_year',
        'expiry_month',
    ];

    public $encryptable = ['gateway_request', 'gateway_response'];

    public $jsonable = ['gateway_response', 'gateway_request'];

    public $belongsTo = [
        'user' => User::class,
    ];

    public function getStatusOptions(): array
    {
        return PaymentMethodStatus::getOptions();
    }

    public static function factory(): PaymentMethodFactory
    {
        return PaymentMethodFactory::new();
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->getStatusOptions()[$this->status->value] ?? $this->status->name;
    }
}
