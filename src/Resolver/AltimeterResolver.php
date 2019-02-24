<?php
declare(strict_types=1);

namespace App\Resolver;

use App\Document\MapPoint;

class AltimeterResolver
{
    private $mapMeterWidth;

    /**
     * AltimeterResolver constructor.
     *
     * @param $mapMeterWidth
     */
    public function __construct(float $mapMeterWidth)
    {
        $this->mapMeterWidth = $mapMeterWidth;
    }

    public function getAltitude(NearestPoint $nearestPoint)
    {
        $meterPerRad = $this->mapMeterWidth / 360;
        if ($nearestPoint->getExactPoint()) {
            return $nearestPoint->getExactPoint()->getElevation() * $meterPerRad;
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
        return (array_sum($elevations) / count($elevations)) * $meterPerRad;
    }

    private function getAverageElevation(?MapPoint $firstPoint, ?MapPoint $secondPoint)
    {
        if ($firstPoint && $secondPoint) {
            return ($firstPoint->getElevation() + $secondPoint->getElevation()) / 2;
        }

        return false;
    }
}
