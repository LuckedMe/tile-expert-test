<?php

declare(strict_types=1);

namespace App\Repository\Traits;

use App\Contract\Entity\EntityInterface;

trait findOneByIdTrait
{
    public function findOneById(int $id): ?EntityInterface
    {
        return $this->find($id);
    }
}
