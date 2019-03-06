<?php

namespace App\Resolver\RoverInformation;

use App\Resolver\Gyroscope\GyroscopicInfo;
use App\Resolver\NearestPoint;

class DataContainer
{
    /**
     * @var NearestPoint
     */
    private $nearestPoint;

    /**
     * @var float
     */
    private $elevation;

    /**
     * @var GyroscopicInfo
     */
    private $gyroscope;

    /**
     * @var array
     */
    private $laserInformation;

    /**
     * @return NearestPoint
     */
    public function getNearestPoint(): NearestPoint
    {
        return $this->nearestPoint;
    }

    /**
     * @param NearestPoint $nearestPoint
     *
     * @return DataContainer
     */
    public function setNearestPoint(NearestPoint $nearestPoint): DataContainer
    {
        $this->nearestPoint = $nearestPoint;
        return $this;
    }

    /**
     * @return float
     */
    public function getElevation(): float
    {
        return $this->elevation;
    }

    /**
     * @param float $elevation
     *
     * @return DataContainer
     */
    public function setElevation(float $elevation): DataContainer
    {
        $this->elevation = $elevation;
        return $this;
    }

    /**
     * @return GyroscopicInfo
     */
    public function getGyroscope(): GyroscopicInfo
    {
        return $this->gyroscope;
    }

    /**
     * @param GyroscopicInfo $gyroscope
     *
     * @return DataContainer
     */
    public function setGyroscope(GyroscopicInfo $gyroscope): DataContainer
    {
        $this->gyroscope = $gyroscope;
        return $this;
    }

    /**
     * @return array
     */
    public function getLaserInformation(): array
    {
        return $this->laserInformation;
    }

    /**
     * @param array $laserInformation
     *
     * @return DataContainer
     */
    public function setLaserInformation(array $laserInformation): DataContainer
    {
        $this->laserInformation = $laserInformation;
        return $this;
    }
}
