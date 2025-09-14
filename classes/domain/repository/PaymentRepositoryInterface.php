<?php

namespace PalPalych\Payments\Classes\Domain\Repository;

use PalPalych\Payments\Classes\Domain\Entity\Payment;

interface PaymentRepositoryInterface
{
    /**
     * @param int $id
     * @return Payment|null
     */
    public function findById(int $id): ?Payment;

    /**
     * @param string $gatewayId
     * @return Payment|null
     */
    public function findByGatewayId(string $gatewayId): ?Payment;

    /**
     * Persists a payment entity.
     * @param Payment $payment
     * @return void
     */
    public function save(Payment $payment): void;

    /**
     * @param Payment $payment
     */
    public function delete(Payment $payment): void;
}
