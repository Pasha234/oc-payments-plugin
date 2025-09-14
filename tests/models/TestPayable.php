<?php namespace PalPalych\Payments\Tests\Models;

use Model;
use Carbon\CarbonInterface;
use RainLab\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use PalPalych\Payments\Tests\Models\Factory\TestPayableFactory;
use PalPalych\Payments\Classes\Domain\Contract\PayableInterface;
use PalPalych\Payments\Classes\Domain\Gateway\Receipt\ReceiptItem;
use PalPalych\Payments\Classes\Domain\Gateway\Receipt\ReceiptItems;
use PalPalych\Payments\Classes\Domain\Gateway\Receipt\ReceiptItemAmount;
use PalPalych\Payments\Classes\Domain\Gateway\Receipt\ReceiptItemCurrency;
use PalPalych\Payments\Classes\Domain\Gateway\Receipt\VatCode;

/**
 * @mixin Builder
 *
 * @property-read int $id
 *
 * @property int $user_id
 * @property bool $paid
 *
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 *
 * @property User $user
 */
class TestPayable extends Model implements PayableInterface
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string table name
     */
    public $table = 'palpalych_payments_test_payable';

    /**
     * @var array rules for validation
     */
    public $rules = [];

    public $fillable = [
        'user_id',
    ];

    public $belongsTo = [
        'user' => User::class,
    ];

    public static function factory(): TestPayableFactory
    {
        return TestPayableFactory::new();
    }

    /**
     * @inheritDoc
     */
    public function getPayableAmount(): int
    {
        return 100;
    }

    /**
     * @inheritDoc
     */
    public function getPayableDescription(): string
    {
        return "Оплата №{$this->id}";
    }

    public function getPayableId(): string
    {
        return (string) $this->id;
    }

    public function getPayableType(): string
    {
        return self::class;
    }

    public function markAsPaid(): void
    {
        $this->paid = true;
        $this->save();
    }

    public function getReceiptItems(): ReceiptItems
    {
        $receiptItems = new ReceiptItems();
        $receiptItems->addItem(new ReceiptItem(
            'test item',
            new ReceiptItemAmount(
                $this->getPayableAmount(),
                ReceiptItemCurrency::rub,
            ),
            VatCode::without_vat,
            1
        ));

        return $receiptItems;
    }
}
