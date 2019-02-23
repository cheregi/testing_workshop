<?php
declare(strict_types=1);

namespace App\Modifier\Heap;

use App\Document\MapPoint;

class MapPointHeap extends \SplMinHeap
{
    /**
     * Compare elements in order to place them correctly in the heap while sifting up.
     *
     * @link  https://php.net/manual/en/splminheap.compare.php
     *
     * @param MapPoint $value1 <p>
     *                      The value of the first node being compared.
     *                      </p>
     * @param MapPoint $value2 <p>
     *                      The value of the second node being compared.
     *                      </p>
     *
     * @return int Result of the comparison, positive integer if <i>value1</i> is lower than <i>value2</i>, 0 if they
     *              are equal, negative integer otherwise.
     * </p>
     * <p>
     * Having multiple elements with the same value in a Heap is not recommended. They will end up in an arbitrary
     * relative position.
     * @since 5.3.0
     */
    protected function compare($value1, $value2)
    {
        $val = $value1->getCoordinates()->getPositionX() - $value2->getCoordinates()->getPositionX();
        if ($val < 0) {
            return intval(floor($val));
        }
        return intval(ceil($val));
    }

}
