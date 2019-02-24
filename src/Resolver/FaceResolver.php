<?php
declare(strict_types=1);

namespace App\Resolver;

use App\Document\MapPoint;
use App\Document\Repository\MapPointRepository;
use Psr\Log\LoggerInterface;

class FaceResolver
{
    /**
     * @var float
     */
    private $sampleRadius;

    /**
     * @var float
     */
    private $mapMeterWidth;

    /**
     * @var MapPointRepository
     */
    private $mapPointRepository;

    /**
     * @var int
     */
    private $retry;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * FaceResolver constructor.
     *
     * @param float $sampleRadius
     * @param float $mapMeterWidth
     * @param MapPointRepository $mapPointRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        float $sampleRadius,
        float $mapMeterWidth,
        int $retry,
        MapPointRepository $mapPointRepository,
        LoggerInterface $logger
    ) {
        $this->sampleRadius = $sampleRadius;
        $this->mapMeterWidth = $mapMeterWidth;
        $this->mapPointRepository = $mapPointRepository;
        $this->retry = $retry;
        $this->logger = $logger;
    }

    /**
     * @param float $positionX
     * @param float $positionY
     *
     * @return NearestPoint
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function getFaceNear(float $positionX, float $positionY) : NearestPoint
    {
        $radPerMeter = (360 / $this->mapMeterWidth);
        $this->logger->debug('Rad per meter calculated', [$radPerMeter]);

        $centerX = $radPerMeter * $positionX;
        $centerY = $radPerMeter * $positionY;
        $this->logger->debug('Center resolved', ['x' => $centerX, 'y' => $centerY]);

        $result = ['tr'=>null, 'tl' => null, 'bl'=>null, 'br'=>null];
        for ($radius = $this->sampleRadius, $iteration = $this->retry;$iteration > 0;$iteration--, $radius = $radius * 2) {
            $result = array_map(function($row) {
                return $row['origin'];
            }, $this->getNearestPoints($centerX, $centerY, $radius));

            if (empty(array_filter($result, 'is_null'))) {
                return new NearestPoint(
                    $result['tr'],
                    $result['tl'],
                    $result['br'],
                    $result['bl'],
                    $result['eq']
                );
            }
            $this->logger->warning('Nearest point retry invoked', ['radius' => $radius, 'to-radius' => $radius * 2]);
        }
        return new NearestPoint(
            $result['tr'],
            $result['tl'],
            $result['br'],
            $result['bl'],
            $result['eq']
        );
    }

    /**
     * @param float $centerX
     * @param float $centerY
     * @param float $sampleRadius
     *
     * @return array
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    private function getNearestPoints(float $centerX, float $centerY, float $sampleRadius)
    {
        $nearestPoints = $this->mapPointRepository->findCircularPoints($centerX, $centerY, $sampleRadius);
        $this->logger->debug(
            sprintf('%d points resolved', count($nearestPoints)),
            ['x' => $centerX, 'y' => $centerY, 'radius' => $sampleRadius]
        );

        $centeredPoints = array_map(function(MapPoint $point) use ($centerX, $centerY) {
            $coordinate = [
                'x' => $point->getCoordinates()->getPositionX() - $centerX,
                'y' => $point->getCoordinates()->getPositionY() - $centerY,
                'origin' => $point
            ];

            $coordinate['d'] = sqrt(pow($coordinate['x'], 2) + pow($coordinate['y'], 2));
            return $coordinate;
        }, $nearestPoints->toArray());
        $this->logger->debug('Point centered', array_slice($centeredPoints, 0, 15));

        $topRight = [];
        $topLeft = [];
        $bottomRight = [];
        $bottomLeft = [];
        $exactPoint = null;
        foreach ($centeredPoints as $point) {
            if ($point['x'] == 0 && $point['y'] == 0) {
                $exactPoint = $point;
            } else if ($point['x'] > 0 && $point['y'] > 0) {
                $topRight[] = $point;
            } else if ($point['x'] < 0 && $point['y'] > 0) {
                $topLeft[] = $point;
            } else if ($point['x'] > 0 && $point['y'] < 0) {
                $bottomRight[] = $point;
            } else if ($point['x'] < 0 && $point['y'] < 0) {
                $bottomLeft[] = $point;
            }
        }

        usort($topRight, function($pointA, $pointB){return $pointA['d'] - $pointB['d'];});
        usort($topLeft, function($pointA, $pointB){return $pointA['d'] - $pointB['d'];});
        usort($bottomRight, function($pointA, $pointB){return $pointA['d'] - $pointB['d'];});
        usort($bottomLeft, function($pointA, $pointB){return $pointA['d'] - $pointB['d'];});

        $result = [
            'tr' => $topRight[0] ?? null,
            'tl' => $topLeft[0] ?? null,
            'br' => $bottomRight[0] ?? null,
            'bl' => $bottomLeft[0] ?? null,
            'eq' => $exactPoint
        ];
        $this->logger->debug('Point extracted', [$result]);

        return $result;
    }
}
