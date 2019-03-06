<?php
declare(strict_types=1);
namespace App\Resolver\RoverInformation;

class RoverInformation
{
    private $wheelRpm = 0.0;

    private $wheelAngle = 0.0;

    private $angle = 0.0;

    private $elevation = 0.0;

    private $positionX = 0.0;

    private $positionY = 0.0;

    /**
     * @return float
     */
    public function getAngle(): float
    {
        return $this->angle;
    }

    /**
     * @param float $angle
     *
     * @return RoverInformation
     */
    public function setAngle(float $angle): RoverInformation
    {
        $this->angle = $angle;
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
     * @return RoverInformation
     */
    public function setElevation(float $elevation): RoverInformation
    {
        $this->elevation = $elevation;
        return $this;
    }

    /**
     * @return float
     */
    public function getPositionX(): float
    {
        return $this->positionX;
    }

    /**
     * @param float $positionX
     *
     * @return RoverInformation
     */
    public function setPositionX(float $positionX): RoverInformation
    {
        $this->positionX = $positionX;
        return $this;
    }

    /**
     * @return float
     */
    public function getPositionY(): float
    {
        return $this->positionY;
    }

    /**
     * @param float $positionY
     *
     * @return RoverInformation
     */
    public function setPositionY(float $positionY): RoverInformation
    {
        $this->positionY = $positionY;
        return $this;
    }

    /**
     * @return float
     */
    public function getWheelAngle(): float
    {
        return $this->wheelAngle;
    }

    /**
     * @param float $wheelAngle
     *
     * @return RoverInformation
     */
    public function setWheelAngle(float $wheelAngle): RoverInformation
    {
        $this->wheelAngle = $wheelAngle;
        return $this;
    }

    /**
     * @return float
     */
    public function getWheelRpm(): float
    {
        return $this->wheelRpm;
    }

    /**
     * @param float $wheelRpm
     *
     * @return RoverInformation
     */
    public function setWheelRpm(float $wheelRpm): RoverInformation
    {
        $this->wheelRpm = $wheelRpm;
        return $this;
    }
}
