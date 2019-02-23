<?php
declare(strict_types=1);

namespace App\Modifier;

use App\Document\Coordinates;
use App\Document\MapPoint;
use App\Modifier\Heap\MapPointHeap;

class VerticesMultiplier
{
    private $multiplier = 0;

    /**
     * VerticesMultiplier constructor.
     *
     * @param int $multiplier
     */
    public function __construct(int $multiplier)
    {
        $this->multiplier = $multiplier;
    }

    /**
     * @param MapPoint[] $vertices
     *
     * @return array
     */
    public function multiplyVertices(array $vertices)
    {
        for ($iteration = 0;$iteration < $this->multiplier;$iteration++) {
            $vertices = $this->doubleVertex($vertices);
        }

        return $vertices;
    }

    /**
     * @param MapPoint[] $vertices
     *
     * @return array
     */
    private function doubleVertex(array $vertices)
    {
        $yMap = [];
        foreach ($vertices as $vertex) {
            $positionY = $vertex->getCoordinates()->getPositionY();
            if (!isset($yMap[$positionY])) {
                $yMap[$positionY] = new MapPointHeap();
            }

            $yMap[$positionY]->insert($vertex);
        }

        $vertexMap = array_map('iterator_to_array', $yMap);
        $vertexMap = array_map('array_values', $vertexMap);
        $vertexMap = array_values($vertexMap);

        $vertices = [];
        foreach ($vertexMap as $yIteration => $rowVertex) {
            foreach ($rowVertex as $xIteration => $cellVertex) {
                $vertices[] = $cellVertex;
                if (isset($rowVertex[$xIteration + 1])) {
                    $vertices[] = $this->getMedium($cellVertex, $rowVertex[$xIteration + 1]);

                    if (isset($vertexMap[$yIteration + 1]) && isset($vertexMap[$yIteration + 1][$xIteration + 1])) {
                        $vertices[] = $this->getMedium(
                            $cellVertex,
                            $vertexMap[$yIteration + 1][$xIteration + 1]
                        );
                    }
                }

                if (isset($vertexMap[$yIteration + 1]) && isset($vertexMap[$yIteration + 1][$xIteration])) {
                    $vertices[] = $this->getMedium(
                        $cellVertex,
                        $vertexMap[$yIteration + 1][$xIteration]
                    );
                }
            }
        }

        return $vertices;
    }

    private function getMedium(MapPoint $vertex, MapPoint $nextVertex)
    {
        $positionY = (
                $vertex->getCoordinates()->getPositionY() +
                $nextVertex->getCoordinates()->getPositionY()
            ) / 2;
        $positionX = (
                $vertex->getCoordinates()->getPositionX() +
                $nextVertex->getCoordinates()->getPositionX()
            ) / 2;

        $newVertex = new MapPoint();
        $newVertex->setElevation(($vertex->getElevation() + $nextVertex->getElevation()) / 2)
            ->setCoordinates(new Coordinates($positionX, $positionY));

        return $newVertex;
    }
}
