<?php
declare(strict_types=1);

namespace App\Resolver;

use App\Document\MapPoint;
use App\Document\Repository\MapPointRepository;
use App\Modifier\VerticesMultiplier;
use Psr\Log\LoggerInterface;

class LaserSensorResolver
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var MapPointRepository
     */
    private $repository;

    /**
     * @var float
     */
    private $mapMeterWidth;

    /**
     * @var float
     */
    private $meterRange;

    /**
     * @var float
     */
    private $aperture;

    /**
     * @var VerticesMultiplier
     */
    private $verticesMultiplier;

    /**
     * @var bool
     */
    private $relativePosition = false;

    /**
     * LaserSensorResolver constructor.
     *
     * @param LoggerInterface    $logger
     * @param VerticesMultiplier $verticesMultiplier
     * @param MapPointRepository $repository
     * @param float              $mapMeterWidth
     * @param array              $laserConfiguration
     */
    public function __construct(
        LoggerInterface $logger,
        VerticesMultiplier $verticesMultiplier,
        MapPointRepository $repository,
        float $mapMeterWidth,
        array $laserConfiguration
    ) {
        $this->logger = $logger;
        $this->verticesMultiplier = $verticesMultiplier;
        $this->repository = $repository;
        $this->mapMeterWidth = $mapMeterWidth;
        $this->meterRange = $laserConfiguration['meter_range'];
        $this->aperture = $laserConfiguration['aperture_angle'];
        $this->relativePosition = $laserConfiguration['relative_position'];
    }

    /**
     * @param float $positionX Metric position x on the map
     * @param float $positionY Metric position y on the map
     * @param float $angle     Angular direction of the sensor
     *
     * @param float $altitude
     *
     * @return array[x, y, z]
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function resolveDetectedPoints(float $positionX, float $positionY, float $angle, float $altitude)
    {
        $radPerMeter = (360 / $this->mapMeterWidth);
        $this->logger->debug('Rad per meter calculated', [$radPerMeter]);
        $rangeRadius = ceil($radPerMeter * $this->meterRange);

        $centerX = $radPerMeter * $positionX;
        $centerY = $radPerMeter * $positionY;

        $rangeRadius = $rangeRadius < 1 ? 1 : $rangeRadius;
        $this->logger->debug(
            'Resolving circular points',
            [
                'x1' => $centerX - $rangeRadius,
                'y1' => $centerY - $rangeRadius,
                'x2' => $centerX + $rangeRadius,
                'y2' => $centerY + $rangeRadius,
                'radius' => $rangeRadius
            ]
        );

        $rangedPoints = $this->repository
            ->findBoxedPoints(
                $centerX - $rangeRadius,
                $centerY - $rangeRadius,
                $centerX + $rangeRadius,
                $centerY + $rangeRadius
            );
        $this->logger->debug(sprintf('%d vertex resolved', $rangedPoints->count()));
        $rangedPoints = $this->verticesMultiplier->multiplyVertices($rangedPoints->toArray());
        $this->logger->debug(sprintf('%d vertex after multiplication', count($rangedPoints)));

        $meterPerRad = $this->mapMeterWidth / 360;
        $this->logger->debug('Meter per rad calculated', [$meterPerRad]);
        return array_filter(
            array_map(function(MapPoint $point) use ($meterPerRad, $rangeRadius, $positionX, $positionY, $angle, $altitude) {
                $coordinates = [
                    'x' => $point->getCoordinates()->getPositionX() * $meterPerRad,
                    'y' => $point->getCoordinates()->getPositionY() * $meterPerRad,
                    'z' => $point->getElevation()
                ];

                $dist = [
                    'x' => $coordinates['x'] - $positionX,
                    'y' => $coordinates['y'] - $positionY
                ];
                $distance = abs(sqrt(pow($dist['x'], 2) + pow($dist['y'], 2)));

                if ($distance > $this->meterRange) {
                    return false;
                }

                $position = [
                    'x' => ($coordinates['x'] - $positionX) / $this->meterRange,
                    'y' => ($coordinates['y'] - $positionY) / $this->meterRange
                ];
                $minAngle = $angle - ($this->aperture / 2);
                $maxAngle = $angle + ($this->aperture / 2);

                if (abs($position['y']) > abs($position['x'])) {
                    $pointAngle = asin($position['y']) * (180 / pi());
                } else {
                    $pointAngle = acos($position['x']) * (180 / pi());
                }

                if ($pointAngle < $minAngle || $pointAngle > $maxAngle) {
                    return false;
                }

                if ($this->relativePosition) {
                    $coordinates['x'] = $coordinates['x'] - $positionX;
                    $coordinates['y'] = $coordinates['y'] - $positionY;
                    $coordinates['z'] = $coordinates['z'] - $altitude;
                }

                $coordinates['d'] = $distance;
                $coordinates['a'] = $pointAngle;
                return $coordinates;
            }, $rangedPoints)
        );
    }
}
