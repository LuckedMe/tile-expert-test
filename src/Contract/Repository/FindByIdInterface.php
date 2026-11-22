<?php

declare(strict_types=1);

namespace App\Contract\Repository;

use App\Contract\Entity\EntityInterface;

interface FindByIdInterface
{
    public function findOneById(int $id): ?EntityInterface;
}
