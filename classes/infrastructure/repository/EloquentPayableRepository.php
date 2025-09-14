<?php

namespace PalPalych\Payments\Classes\Infrastructure\Repository;

use Illuminate\Database\Eloquent\Model;
use PalPalych\Payments\Classes\Domain\Contract\PayableInterface;
use PalPalych\Payments\Classes\Domain\Repository\PayableRepositoryInterface;

class EloquentPayableRepository implements PayableRepositoryInterface
{
    /**
     * @param class-string<Model> $type
     */
    public function findById(int $id, string $type): ?PayableInterface
    {
        return $type::query()
            ->find($id);
    }
}
