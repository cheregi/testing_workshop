<?php
declare(strict_types=1);

namespace App\Resolver\Gyroscope;

class GyroscopicInfo
{
    /**
     * @var float
     */
    private $xyAngle;

    /**
     * @var float
     */
    private $xzAngle;

    /**
     * @var float
     */
    private $yzAngle;

    /**
     * GyroscopicInfo constructor.
     *
     * @param float $xyAngle
     * @param float $xzAngle
     * @param float $yzAngle
     */
    public function __construct(float $xyAngle, float $xzAngle, float $yzAngle)
    {
        $this->xyAngle = $xyAngle;
        $this->xzAngle = $xzAngle;
        $this->yzAngle = $yzAngle;
    }

    /**
     * @return float
     */
    public function getXyAngle(): float
    {
        return $this->xyAngle;
    }

    /**
     * @param float $xyAngle
     */
    public function setXyAngle(float $xyAngle): void
    {
        $this->xyAngle = $xyAngle;
    }

    /**
     * @return float
     */
    public function getXzAngle(): float
    {
        return $this->xzAngle;
    }

    /**
     * @param float $xzAngle
     */
    public function setXzAngle(float $xzAngle): void
    {
        $this->xzAngle = $xzAngle;
    }

    /**
     * @return float
     */
    public function getYzAngle(): float
    {
        return $this->yzAngle;
    }

    /**
     * @param float $yzAngle
     */
    public function setYzAngle(float $yzAngle): void
    {
        $this->yzAngle = $yzAngle;
    }
}
