<?php
declare(strict_types=1);
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
