<?php
declare(strict_types=1);
namespace App\Document\Repository;

use Doctrine\ODM\MongoDB\Cursor;
use Doctrine\ODM\MongoDB\DocumentRepository;

class MapPointRepository extends DocumentRepository
{
    /**
     * @return mixed
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function dropAll()
    {
        $queryBuilder = $this->createQueryBuilder();
        return $queryBuilder->remove()->getQuery()->execute();
    }

    /**
     * @param float $centerX
     * @param float $centerY
     * @param float $radius
     *
     * @return Cursor
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function findCircularPoints(float $centerX, float $centerY, float $radius)
    {
        $queryBuilder = $this->createQueryBuilder();
        return $queryBuilder->field('coordinates')
            ->geoWithinCenter($centerX, $centerY, $radius)
            ->getQuery()
            ->execute();
    }

    /**
     * @param float $fromX
     * @param float $fromY
     * @param float $toX
     * @param float $toY
     *
     * @return mixed
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function findBoxedPoints(float $fromX, float $fromY, float $toX, float $toY)
    {
        $queryBuilder = $this->createQueryBuilder();
        return $queryBuilder->field('coordinates')
            ->geoWithinBox($fromX, $fromY, $toX, $toY)
            ->getQuery()
            ->execute();
    }
}
