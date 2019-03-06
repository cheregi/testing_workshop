<?php
declare(strict_types=1);

namespace App\Resolver;

use App\Resolver\Gyroscope\GyroscopicInfoFactory;

class GyroscopicResolver
{
    /**
     * @var GyroscopicInfoFactory
     */
    private $gyroscopicFactory;

    /**
     * GyroscopicResolver constructor.
     *
     * @param GyroscopicInfoFactory $gyroscopicFactory
     */
    public function __construct(GyroscopicInfoFactory $gyroscopicFactory)
    {
        $this->gyroscopicFactory = $gyroscopicFactory;
    }

    /**
     * @param NearestPoint $nearestPoint
     * @param float        $xyAngle
     *
     * @return Gyroscope\GyroscopicInfo
     */
    public function getGyroscopicInfo(NearestPoint $nearestPoint, float $xyAngle)
    {
        return $this->gyroscopicFactory->getGyroscopicInfo($nearestPoint, $xyAngle);
    }
}
