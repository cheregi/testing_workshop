<?php
declare(strict_types=1);

namespace App\Resolver\Gyroscope;

use App\Document\Coordinates;
use App\Document\MapPoint;
use App\Resolver\NearestPoint;
use App\Resolver\Vector\NormalVectorFactory;
use App\Resolver\Vector\Plan;
use App\Resolver\Vector\StraightFactory;
use DeepCopy\DeepCopy;

class GyroscopicInfoFactory
{
    /**
     * @var DeepCopy
     */
    private $copier;

    /**
     * @var NormalVectorFactory
     */
    private $normalFactory;

    /**
     * @var StraightFactory
     */
    private $straightFactory;

    /**
     * GyroscopicInfoFactory constructor.
     *
     * @param DeepCopy            $copier
     * @param NormalVectorFactory $normalFactory
     * @param StraightFactory     $straightFactory
     */
    public function __construct(DeepCopy $copier, NormalVectorFactory $normalFactory, StraightFactory $straightFactory)
    {
        $this->copier = $copier;
        $this->normalFactory = $normalFactory;
        $this->straightFactory = $straightFactory;
    }

    /**
     * @param NearestPoint $nearestPoint
     * @param float        $angle
     *
     * @return GyroscopicInfo
     */
    public function getGyroscopicInfo(NearestPoint $nearestPoint, float $angle)
    {
        $nearest = $this->copier->copy($nearestPoint);
        $nearest->rotate($angle);

        $center = new MapPoint();
        $center->setCoordinates(new Coordinates(0, 0))
            ->setElevation(0);

        $calculation = function($p1, $p2, $pointA, $pointB) use ($nearest, $center) {
            $po1 = new MapPoint();
            $po1->setCoordinates(new Coordinates($p1[0], $p1[1]))
                ->setElevation($p1[2]);

            $po2 = new MapPoint();
            $po2->setCoordinates(new Coordinates($p2[0], $p2[1]))
                ->setElevation($p2[2]);

            $plan = new Plan(
                [$center, $po1, $po2],
                $this->normalFactory->getVector($center, $po1, $po2)
            );

            $straight = $this->straightFactory->createFromPoints($pointA, $pointB);

            $intersectionPoint = $plan->calculateStraightIntersection($straight);
            $distance = sqrt(
                pow($intersectionPoint['x'], 2) +
                pow($intersectionPoint['y'], 2) +
                pow($intersectionPoint['z'], 2)
            );
            $radialY = $intersectionPoint['z'] / $distance;

            return sin($radialY) * (180 / pi());
        };

        $left = $calculation([-1, 0, 0], [0, 0, 1], $nearest->getTopLeftPoint(), $nearest->getBottomLeftPoint());
        $right = $calculation([+1, 0, 0], [0, 0, 1], $nearest->getTopRightPoint(), $nearest->getBottomRightPoint());
        $xz = ($right - $left) / 2;

        $front = $calculation([0, 1, 0], [0, 0, 1], $nearest->getTopLeftPoint(), $nearest->getTopRightPoint());
        $back = $calculation([0, -1, 0], [0, 0, 1], $nearest->getBottomRightPoint(), $nearest->getBottomLeftPoint());
        $yz = ($front - $back) / 2;

        return new GyroscopicInfo($angle, $xz, $yz);
    }
}
