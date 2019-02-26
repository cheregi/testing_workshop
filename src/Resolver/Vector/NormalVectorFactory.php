<?php
declare(strict_types=1);

namespace App\Resolver\Vector;

use App\Document\MapPoint;

class NormalVectorFactory
{
    public function getVector(MapPoint $pointA, MapPoint $pointB, MapPoint $pointC)
    {
        $vectorAB = new Vector(
            $pointB->getCoordinates()->getPositionX() - $pointA->getCoordinates()->getPositionX(),
            $pointB->getCoordinates()->getPositionY() - $pointA->getCoordinates()->getPositionY(),
            $pointB->getElevation() - $pointA->getElevation()
        );

        $vectorAC = new Vector(
            $pointC->getCoordinates()->getPositionX() - $pointA->getCoordinates()->getPositionX(),
            $pointC->getCoordinates()->getPositionY() - $pointA->getCoordinates()->getPositionY(),
            $pointC->getElevation() - $pointA->getElevation()
        );

        return new Vector(
            ($vectorAB->getTransY() * $vectorAC->getTransZ()) - ($vectorAB->getTransZ() * $vectorAC->getTransY()),
            ($vectorAB->getTransZ() * $vectorAC->getTransX()) - ($vectorAB->getTransX() * $vectorAC->getTransZ()),
            ($vectorAB->getTransX() * $vectorAC->getTransY()) - ($vectorAB->getTransY() * $vectorAC->getTransX())
        );
    }
}
