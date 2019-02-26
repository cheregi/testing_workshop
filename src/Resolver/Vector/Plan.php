<?php
declare(strict_types=1);

namespace App\Resolver\Vector;

use App\Document\MapPoint;

class Plan
{
    /**
     * @var MapPoint[]
     */
    private $points = [];

    /**
     * @var Vector
     */
    private $normalVector;

    /**
     * Plan constructor.
     *
     * @param MapPoint[] $points
     * @param Vector     $normalVector
     */
    public function __construct(array $points = [], Vector $normalVector = null)
    {
        $this->points = $points;
        $this->normalVector = $normalVector;
    }

    public function calculateStraightIntersection(Straight $straight)
    {
        $straightParametric = function($t) use ($straight) {
            $point = $straight->getPoint();
            $vector = $straight->getVector();
            return [
                'x' => $point->getCoordinates()->getPositionX() + ($vector->getTransX() * $t),
                'y' => $point->getCoordinates()->getPositionY() + ($vector->getTransY() * $t),
                'z' => $point->getElevation() + ($vector->getTransZ() * $t)
            ];
        };

        $getT = function() use ($straight) {
            $point = $straight->getPoint();
            $vector = $straight->getVector();

            $nx = $this->normalVector->getTransX();
            $ny = $this->normalVector->getTransY();
            $nz = $this->normalVector->getTransZ();
            $d = $this->getCartesianDConst();

            $ax = $point->getCoordinates()->getPositionX();
            $ay = $point->getCoordinates()->getPositionY();
            $az = $point->getElevation();
            $dx = $vector->getTransX();
            $dy = $vector->getTransY();
            $dz = $vector->getTransZ();

            return ((-1 * $d) - ($nx * $ax) - ($ny * $ay) - ($nz * $az)) / (($nx * $dx) + ($ny * $dy) + ($nz * $dz));
        };

        return $straightParametric(
            $getT()
        );
    }

    private function getCartesianDConst()
    {
        $point = $this->points[0];

        return -1 * (
                $this->normalVector->getTransX() * $point->getCoordinates()->getPositionX() +
                $this->normalVector->getTransY() * $point->getCoordinates()->getPositionY() +
                $this->normalVector->getTransZ() * $point->getElevation()
            );
    }
}
