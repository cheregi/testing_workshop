<?php

namespace App\Document\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;

class MapPointRepository extends DocumentRepository
{
    public function dropAll()
    {
        $queryBuilder = $this->createQueryBuilder();
        return $queryBuilder->remove()->getQuery()->execute();
    }
}
