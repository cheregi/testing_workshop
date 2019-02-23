<?php
declare(strict_types=1);
namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\Document(repositoryClass="App\Document\Repository\MapPointRepository")
 * @MongoDB\Index(keys={"coordinates"="2d"})
 */
class MapPoint
{
    /**
     * @var string
     * @MongoDB\Id()
     */
    private $id;

    /**
     * @var Coordinates
     * @MongoDB\EmbedOne(targetDocument="Coordinates")
     */
    private $coordinates;

    /**
     * @var float
     * @MongoDB\Field(type="float")
     */
    private $elevation;

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     *
     * @return MapPoint
     */
    public function setId(string $id): MapPoint
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return Coordinates
     */
    public function getCoordinates(): Coordinates
    {
        return $this->coordinates;
    }

    /**
     * @param Coordinates $coordinates
     *
     * @return MapPoint
     */
    public function setCoordinates(Coordinates $coordinates): MapPoint
    {
        $this->coordinates = $coordinates;
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
     * @return MapPoint
     */
    public function setElevation(float $elevation): MapPoint
    {
        $this->elevation = $elevation;
        return $this;
    }
}
