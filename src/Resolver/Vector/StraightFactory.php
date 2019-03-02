<?php
declare(strict_types=1);

namespace App\Resolver\Vector;

use App\Document\MapPoint;

class StraightFactory
{
    public function createFromPoints(MapPoint $pointA, MapPoint $pointB)
    {
        $vector = new Vector(
            $pointB->getCoordinates()->getPositionX() - $pointA->getCoordinates()->getPositionX(),
            $pointB->getCoordinates()->getPositionY() - $pointA->getCoordinates()->getPositionY(),
            $pointB->getElevation() - $pointA->getElevation()
        );
        return new Straight($pointA, $vector);
    }
}
