<?php
declare(strict_types=1);

namespace App\Resolver\Vector;

class Vector
{
    /**
     * @var float
     */
    private $transX;

    /**
     * @var float
     */
    private $transY;

    /**
     * @var float
     */
    private $transZ;

    /**
     * Vector constructor.
     *
     * @param $transX
     * @param $transY
     * @param $transZ
     */
    public function __construct(float $transX = 0, float $transY = 0, float $transZ = 0)
    {
        $this->transX = $transX;
        $this->transY = $transY;
        $this->transZ = $transZ;
    }

    /**
     * @return float
     */
    public function getTransX(): float
    {
        return $this->transX;
    }

    /**
     * @param float $transX
     */
    public function setTransX(float $transX): void
    {
        $this->transX = $transX;
    }

    /**
     * @return float
     */
    public function getTransY(): float
    {
        return $this->transY;
    }

    /**
     * @param float $transY
     */
    public function setTransY(float $transY): void
    {
        $this->transY = $transY;
    }

    /**
     * @return float
     */
    public function getTransZ(): float
    {
        return $this->transZ;
    }

    /**
     * @param float $transZ
     */
    public function setTransZ(float $transZ): void
    {
        $this->transZ = $transZ;
    }
}
