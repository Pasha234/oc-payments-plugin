<?php

namespace PalPalych\Payments\Classes\Infrastructure\Repository;

use PalPalych\Payments\Classes\Infrastructure\Mapper\PaymentMapper;
use ReflectionProperty;
use PalPalych\Payments\Models\Payment as PaymentModel;
use PalPalych\Payments\Classes\Domain\Entity\Payment as PaymentEntity;
use PalPalych\Payments\Classes\Domain\Repository\PaymentRepositoryInterface;

class EloquentPaymentRepository implements PaymentRepositoryInterface
{
    /**
     * @inheritDoc
     */
    public function findById(int $id): ?PaymentEntity
    {
        $paymentModel = PaymentModel::find($id);

        if (!$paymentModel) {
            return null;
        }

        return PaymentMapper::toEntity($paymentModel);
    }

    /**
     * @inheritDoc
     */
    public function findByGatewayId(string $gatewayId): ?PaymentEntity
    {
        $paymentModel = PaymentModel::where('gateway_id', $gatewayId)->first();

        if (!$paymentModel) {
            return null;
        }

        return PaymentMapper::toEntity($paymentModel);
    }

    /**
     * @inheritDoc
     */
    public function save(PaymentEntity $payment): void
    {
        $isNew = $payment->getId() === null;
        $paymentModel = $isNew ? new PaymentModel() : PaymentModel::findOrFail($payment->getId());

        PaymentMapper::toModel($payment, $paymentModel);

        $paymentModel->save();

        if ($isNew) {
            $reflection = new ReflectionProperty($payment, 'id');
            $reflection->setAccessible(true);
            $reflection->setValue($payment, $paymentModel->id);
        }
    }

    /**
     * @inheritDoc
     */
    public function delete(PaymentEntity $payment): void
    {
        if ($payment->getId()) {
            $paymentModel = PaymentModel::find($payment->getId());
            if ($paymentModel) {
                $paymentModel->delete();
            }
        }
    }
}
