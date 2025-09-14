<?php

namespace PalPalych\Payments\Classes\Infrastructure\Repository;

use Error;
use ReflectionProperty;
use PalPalych\Payments\Classes\Infrastructure\Mapper\PaymentMethodMapper;
use PalPalych\Payments\Models\PaymentMethod as PaymentMethodModel;
use PalPalych\Payments\Classes\Domain\Entity\PaymentMethod as PaymentMethodEntity;
use PalPalych\Payments\Classes\Domain\Repository\PaymentMethodRepositoryInterface;

class EloquentPaymentMethodRepository implements PaymentMethodRepositoryInterface
{
    /**
     * @inheritDoc
     */
    public function findById(int $id): ?PaymentMethodEntity
    {
        $paymentMethodModel = PaymentMethodModel::find($id);

        if (!$paymentMethodModel) {
            return null;
        }

        return PaymentMethodMapper::toEntity($paymentMethodModel);
    }

    /**
     * @inheritDoc
     */
    public function findByGatewayId(string $gatewayId): ?PaymentMethodEntity
    {
        $paymentMethodModel = PaymentMethodModel::where('gateway_id', $gatewayId)->first();

        if (!$paymentMethodModel) {
            return null;
        }

        return PaymentMethodMapper::toEntity($paymentMethodModel);
    }

    /**
     * @inheritDoc
     */
    public function save(PaymentMethodEntity $paymentMethod): void
    {
        $isNew = false;
        $id = $paymentMethod->getId();
        if (!$id) {
            $isNew = true;
        }

        $paymentMethodModel = $isNew ? new PaymentMethodModel() : PaymentMethodModel::findOrFail($id);

        PaymentMethodMapper::toModel($paymentMethod, $paymentMethodModel);

        $paymentMethodModel->save();

        if ($isNew) {
            $reflection = new ReflectionProperty($paymentMethod, 'id');
            $reflection->setAccessible(true);
            $reflection->setValue($paymentMethod, $paymentMethodModel->id);
        }
    }

    /**
     * @inheritDoc
     */
    public function delete(PaymentMethodEntity $paymentMethod): void
    {
        $id = $paymentMethod->getId();
        $paymentMethodModel = PaymentMethodModel::find($id);
        if ($paymentMethodModel) {
            $paymentMethodModel->delete();
        }
    }
}

