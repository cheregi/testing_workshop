<?php
declare(strict_types=1);

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\EmbeddedDocument
 */
class Coordinates
{
    /**
     * @MongoDB\Field(type="float")
     */
    private $positionX = 0;

    /**
     * @MongoDB\Field(type="float")
     */
    private $positionY = 0;

    /**
     * Coordinates constructor.
     *
     * @param int $positionX
     * @param int $positionY
     */
    public function __construct(float $positionX = 0, float $positionY = 0)
    {
        $this->positionX = $positionX;
        $this->positionY = $positionY;
    }

    /**
     * @return mixed
     */
    public function getPositionX()
    {
        return $this->positionX;
    }

    /**
     * @param mixed $positionX
     *
     * @return Coordinates
     */
    public function setPositionX($positionX)
    {
        $this->positionX = $positionX;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPositionY()
    {
        return $this->positionY;
    }

    /**
     * @param mixed $positionY
     *
     * @return Coordinates
     */
    public function setPositionY($positionY)
    {
        $this->positionY = $positionY;
        return $this;
    }
}
