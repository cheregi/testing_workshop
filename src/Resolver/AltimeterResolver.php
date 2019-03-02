<?php
declare(strict_types=1);

namespace App\Resolver;

use App\Document\MapPoint;

class AltimeterResolver
{
    public function getAltitude(NearestPoint $nearestPoint)
    {
        if ($nearestPoint->getExactPoint()) {
            return $nearestPoint->getExactPoint()->getElevation();
        }

        $elevations = [
            $this->getAverageElevation($nearestPoint->getTopRightPoint(), $nearestPoint->getBottomLeftPoint()),
            $this->getAverageElevation($nearestPoint->getTopLeftPoint(), $nearestPoint->getBottomRightPoint()),
            $this->getAverageElevation($nearestPoint->getTopLeftPoint(), $nearestPoint->getTopRightPoint()),
            $this->getAverageElevation($nearestPoint->getBottomLeftPoint(), $nearestPoint->getBottomRightPoint()),
            $this->getAverageElevation($nearestPoint->getTopLeftPoint(), $nearestPoint->getBottomLeftPoint()),
            $this->getAverageElevation($nearestPoint->getTopRightPoint(), $nearestPoint->getBottomRightPoint())
        ];

        $elevations = array_filter($elevations);
        return (array_sum($elevations) / count($elevations));
    }

    private function getAverageElevation(?MapPoint $firstPoint, ?MapPoint $secondPoint)
    {
        if ($firstPoint && $secondPoint) {
            return ($firstPoint->getElevation() + $secondPoint->getElevation()) / 2;
        }

        return false;
    }
}
