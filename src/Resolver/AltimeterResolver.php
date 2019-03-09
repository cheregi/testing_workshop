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

        $topLeftPoint = $nearestPoint->getTopLeftPoint();
        $topRightPoint = $nearestPoint->getTopRightPoint();
        $bottomRightPoint = $nearestPoint->getBottomRightPoint();
        $bottomLeftPoint = $nearestPoint->getBottomLeftPoint();
        $elevations = [
            [$topLeftPoint, $this->getDistance($topLeftPoint, $nearestPoint)],
            [$topRightPoint, $this->getDistance($topRightPoint, $nearestPoint)],
            [$bottomRightPoint, $this->getDistance($bottomRightPoint, $nearestPoint)],
            [$bottomLeftPoint, $this->getDistance($bottomLeftPoint, $nearestPoint)]
        ];

        $totalDistance = array_sum(array_map(function($row){
            return $row[1];
        }, $elevations));
        $elevation = array_sum(array_map(function($row){
            if ($row[0]) {
                return $row[0]->getElevation() * $row[1];
            }
            return 0;
        }, $elevations)) / $totalDistance;

        return $elevation;
    }

    private function getDistance(?MapPoint $point, NearestPoint $origin)
    {
        if ($point) {
            return sqrt(
                pow($point->getCoordinates()->getPositionX() - $origin->getPositionX(), 2) +
                pow($point->getCoordinates()->getPositionY() - $origin->getPositionY(), 2)
            );
        }

        return 0;
    }
}
