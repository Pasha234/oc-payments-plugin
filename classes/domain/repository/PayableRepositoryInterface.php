<?php

namespace PalPalych\Payments\Classes\Domain\Repository;

use PalPalych\Payments\Classes\Domain\Contract\PayableInterface;

interface PayableRepositoryInterface
{
    /**
     * @param int $id
     * @param class-string $type
     *
     * @return ?PayableInterface
     */
    public function findById(int $id, string $type): ?PayableInterface;
}
