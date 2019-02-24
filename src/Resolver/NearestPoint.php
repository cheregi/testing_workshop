<?php
declare(strict_types=1);

namespace App\Resolver;

use App\Document\MapPoint;

class NearestPoint
{
    /**
     * @var MapPoint|null
     */
    private $topRightPoint;

    /**
     * @var MapPoint|null
     */
    private $topLeftPoint;

    /**
     * @var MapPoint|null
     */
    private $bottomRightPoint;

    /**
     * @var MapPoint|null
     */
    private $bottomLeftPoint;

    /**
     * @var MapPoint|null
     */
    private $exactPoint;

    /**
     * NearestPoint constructor.
     *
     * @param MapPoint|null $topRightPoint
     * @param MapPoint|null $topLeftPoint
     * @param MapPoint|null $bottomRightPoint
     * @param MapPoint|null $bottomLeftPoint
     * @param MapPoint|null $exactPoint
     */
    public function __construct(
        MapPoint $topRightPoint = null,
        MapPoint $topLeftPoint = null,
        MapPoint $bottomRightPoint = null,
        MapPoint $bottomLeftPoint = null,
        MapPoint $exactPoint = null
    ) {
        $this->topRightPoint = $topRightPoint;
        $this->topLeftPoint = $topLeftPoint;
        $this->bottomRightPoint = $bottomRightPoint;
        $this->bottomLeftPoint = $bottomLeftPoint;
        $this->exactPoint = $exactPoint;
    }

    /**
     * @return MapPoint|null
     */
    public function getTopRightPoint(): ?MapPoint
    {
        return $this->topRightPoint;
    }

    /**
     * @param MapPoint|null $topRightPoint
     *
     * @return $this
     */
    public function setTopRightPoint(MapPoint $topRightPoint): NearestPoint
    {
        $this->topRightPoint = $topRightPoint;
        return $this;
    }

    /**
     * @return MapPoint|null
     */
    public function getTopLeftPoint(): ?MapPoint
    {
        return $this->topLeftPoint;
    }

    /**
     * @param MapPoint|null $topLeftPoint
     *
     * @return $this
     */
    public function setTopLeftPoint(MapPoint $topLeftPoint): NearestPoint
    {
        $this->topLeftPoint = $topLeftPoint;
        return $this;
    }

    /**
     * @return MapPoint|null
     */
    public function getBottomRightPoint(): ?MapPoint
    {
        return $this->bottomRightPoint;
    }

    /**
     * @param MapPoint|null $bottomRightPoint
     *
     * @return $this
     */
    public function setBottomRightPoint(MapPoint $bottomRightPoint): NearestPoint
    {
        $this->bottomRightPoint = $bottomRightPoint;
        return $this;
    }

    /**
     * @return MapPoint|null
     */
    public function getBottomLeftPoint(): ?MapPoint
    {
        return $this->bottomLeftPoint;
    }

    /**
     * @param MapPoint|null $bottomLeftPoint
     *
     * @return $this
     */
    public function setBottomLeftPoint(MapPoint $bottomLeftPoint): NearestPoint
    {
        $this->bottomLeftPoint = $bottomLeftPoint;
        return $this;
    }

    /**
     * @return MapPoint|null
     */
    public function getExactPoint(): ?MapPoint
    {
        return $this->exactPoint;
    }

    /**
     * @param MapPoint|null $exactPoint
     *
     * @return NearestPoint
     */
    public function setExactPoint(?MapPoint $exactPoint): NearestPoint
    {
        $this->exactPoint = $exactPoint;
        return $this;
    }
}
