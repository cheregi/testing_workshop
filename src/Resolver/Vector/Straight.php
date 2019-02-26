<?php
declare(strict_types=1);

namespace App\Resolver\Vector;

use App\Document\MapPoint;

class Straight
{
    /**
     * @var MapPoint
     */
    private $point;

    /**
     * @var Vector
     */
    private $vector;

    /**
     * Straight constructor.
     *
     * @param MapPoint $point
     * @param Vector   $vector
     */
    public function __construct(MapPoint $point, Vector $vector)
    {
        $this->point = $point;
        $this->vector = $vector;
    }

    /**
     * @return MapPoint
     */
    public function getPoint(): MapPoint
    {
        return $this->point;
    }

    /**
     * @param MapPoint $point
     */
    public function setPoint(MapPoint $point): void
    {
        $this->point = $point;
    }

    /**
     * @return Vector
     */
    public function getVector(): Vector
    {
        return $this->vector;
    }

    /**
     * @param Vector $vector
     */
    public function setVector(Vector $vector): void
    {
        $this->vector = $vector;
    }
}
