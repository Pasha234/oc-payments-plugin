<?php

namespace PalPalych\Payments\Classes\Domain\Repository;

use PalPalych\Payments\Classes\Domain\Entity\PaymentMethod;

interface PaymentMethodRepositoryInterface
{
    /**
     * @param int $id
     * @return PaymentMethod|null
     */
    public function findById(int $id): ?PaymentMethod;

    /**
     * @param string $gatewayId
     * @return PaymentMethod|null
     */
    public function findByGatewayId(string $gatewayId): ?PaymentMethod;

    /**
     * Persists a payment entity.
     * @param PaymentMethod $paymentMethod
     * @return void
     */
    public function save(PaymentMethod $paymentMethod): void;

    /**
     * @param PaymentMethod $paymentMethod
     */
    public function delete(PaymentMethod $paymentMethod): void;
}
