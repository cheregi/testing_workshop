<?php
declare(strict_types=1);

namespace App\Resolver\Movement;

class MovementInfo
{
    /**
     * @var float
     */
    private $wheelRotation;

    /**
     * @var float
     */
    private $positionX;

    /**
     * @var float
     */
    private $positionY;

    /**
     * @var float
     */
    private $angle;

    /**
     * @return float
     */
    public function getWheelAngle(): float
    {
        return $this->wheelRotation;
    }

    /**
     * @param float $wheelRotation
     *
     * @return MovementInfo
     */
    public function setWheelAngle(float $wheelRotation): MovementInfo
    {
        $this->wheelRotation = $wheelRotation;
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
     * @return MovementInfo
     */
    public function setPositionX(float $positionX): MovementInfo
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
     * @return MovementInfo
     */
    public function setPositionY(float $positionY): MovementInfo
    {
        $this->positionY = $positionY;
        return $this;
    }

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
     * @return MovementInfo
     */
    public function setAngle(float $angle): MovementInfo
    {
        $this->angle = $angle;
        return $this;
    }
}
