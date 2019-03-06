<?php
declare(strict_types=1);

namespace App\Resolver;

use App\Resolver\Movement\MovementInfo;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class MovementResolver
{
    private $wheelPerimeter;

    /**
     * @var float
     */
    private $wheelAngleRpm;

    /**
     * @var float
     */
    private $wheelDistance;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * MovementResolver constructor.
     *
     * @param array           $wheelConfiguration
     * @param float           $bodyLength
     * @param LoggerInterface $logger
     */
    public function __construct(array $wheelConfiguration, float $bodyLength, LoggerInterface $logger)
    {
        $this->wheelAngleRpm = floatval($wheelConfiguration['rotation_rpm']);
        $this->wheelDistance = ($bodyLength - $wheelConfiguration['diameter']) / 1000;
        $this->logger = $logger;

        $this->setWheelDiameter(floatval($wheelConfiguration['diameter']));
    }

    /**
     * The wheel diameter in mm
     *
     * @param float $wheelDiameter
     *
     * @return MovementResolver
     */
    protected function setWheelDiameter(float $wheelDiameter)
    {
        $this->wheelPerimeter = 2 * pi() * ($wheelDiameter / 2);
        return $this;
    }

    /**
     * @param float $wheelRpm
     * @param float $wheelAngle
     * @param float $wheelAngleDestination
     * @param float $positionX
     * @param float $positionY
     * @param float $angle
     * @param float $tickTime
     *
     * @return MovementInfo
     */
    public function resolveMovement(
        float $wheelRpm,
        float $wheelAngle,
        float $wheelAngleDestination,
        float $positionX,
        float $positionY,
        float $angle,
        float $tickTime
    ) {
        $distance = (($wheelRpm / 60) * $tickTime) * $this->wheelPerimeter / 1000;
        $this->logger->debug('Distance movement resolved', ['distance' => $distance]);

        $endWheelAngle = $wheelAngle;
        $toUp = $wheelAngle < $wheelAngleDestination;
        if ($wheelAngleDestination != $wheelAngle) {
            if ($wheelAngleDestination < 0) {
                $angleTick = (($this->wheelAngleRpm * 360) / 60) * -1 * $tickTime;
            } else {
                $angleTick = (($this->wheelAngleRpm * 360) / 60) * $tickTime;
            }
            if ($wheelAngle > $wheelAngleDestination && $wheelAngleDestination > 0) {
                $angle *= -1;
            }
            $endWheelAngle = $wheelAngle - $angleTick;

            if ($endWheelAngle > $wheelAngleDestination && $toUp) {
                $endWheelAngle = $wheelAngleDestination;
            } else if ($endWheelAngle < $wheelAngleDestination) {
                $endWheelAngle = $wheelAngleDestination;
            }
            $this->logger->debug('New end wheel rotation', ['from' => $wheelAngle, 'to' => $endWheelAngle]);
        }

        if ($endWheelAngle != 0 && $endWheelAngle != 90) {
            $radialAngle = $angle * (pi() / 180);
            $this->logger->debug('Radial angle', ['rad' => $radialAngle]);

            $posAX = round(cos($radialAngle) * ($this->wheelDistance / 2), 10);
            $posAY = round(sin($radialAngle) * ($this->wheelDistance / 2), 10);
            $this->logger->debug('Front wheel center', ['x' => $posAX, 'Y' => $posAY]);

            $posBX = round(cos($radialAngle + pi()) * ($this->wheelDistance / 2), 10);
            $posBY = round(sin($radialAngle + pi()) * ($this->wheelDistance / 2), 10);
            $this->logger->debug('Back wheel center', ['x' => $posBX, 'Y' => $posBY]);

            $midX = ($posAX + $posBX) / 2;
            $midY = ($posAY + $posBY) / 2;
            $this->logger->debug('Wheel center', ['x' => $midX, 'Y' => $midY]);

            $distanceAC = ($this->wheelDistance / 2) / sin((180 - $endWheelAngle - 90) * (pi() / 180));
            $this->logger->debug('Rotation center distance', ['distance' => $distanceAC]);

            if ($posAY !== $posBY) {
                $n = (pow($distanceAC, 2) - pow($distanceAC, 2) - pow($posBX, 2) + pow($posAX, 2) - pow($posBY, 2) + pow($posAY, 2)) / (2 * ($posAY - $posBY));

                $xmy = ($posAX - $posBX) / ($posAY - $posBY);
                $A = pow($xmy, 2) + 1;
                $B = (2 * $posAY * $xmy) - (2 * $n * $xmy) - 2 * $posAX;
                $C = (pow($posAX, 2) + pow($posAY, 2) + pow($n, 2) - pow($distanceAC, 2) - (2 * $posAY * $n));

                $delta = sqrt(pow($B, 2) - (4 * $A * $C));

                if ($endWheelAngle > 0) {
                    $intersectionX = ((-$B + $delta) / (2 * $A)) + $posAX;
                    $intersectionY = $n - ($intersectionX * (($posAX - $posBX) / ($posAY - $posBY))) + $posAY;
                } else {
                    $intersectionX = ((-$B - $delta) / (2 * $A)) + $posAX;
                    $intersectionY = $n - ($intersectionX * (($posAX - $posBX) / ($posAY - $posBY))) + $posAY;
                }
            } else {
                $intermediaryDistance = sqrt(pow($posAX - $posBX, 2) + pow($posAY - $posBY, 2));
                $intersectionX = ((pow($distanceAC, 2) - pow($intermediaryDistance, 2) - pow($distanceAC, 2)) / -(2 * $intermediaryDistance)) + $posAX;

                if ($endWheelAngle > 0) {
                    $intersectionY = sqrt(pow($distanceAC, 2) - pow($intermediaryDistance - $intersectionX, 2)) + $posAY;
                } else {
                    $intersectionY = -(sqrt(pow($distanceAC, 2) - pow($intermediaryDistance - $intersectionX, 2))) + $posAY;
                }
            }
            $intersectionX -= $posAX;
            $this->logger->debug('Rotation center point', ['x' => $intersectionX, 'y' => $intersectionY]);

            $paToc = sqrt(pow(($posAX - $intersectionX), 2) + pow(($posAY - $intersectionY), 2));
            $pA = [
                ($posAX - $intersectionX) / $paToc,
                ($posAY - $intersectionY) / $paToc
            ];
            if ($intersectionX > 0) {
                $pA[0] = $pA[0] * -1;
            }
            $angleToFront = acos($pA[0]);
            if ($intersectionY > 0) {
                $angleToFront = $angleToFront * -1;
            }
            $this->logger->debug('Angle to center from front', ['angle' => ($angleToFront * (180 / pi())), 'relative A' => $pA]);

            $perimeter = 2 * pi() * $distanceAC;
            $this->logger->debug('Rotation circle perimeter resolved', ['perimeter' => $perimeter]);

            $angularSpeed = (2 * pi()) / ($perimeter / $distance);
            $this->logger->debug('Angular speed resolved (rad)', ['angle' => $angularSpeed]);

            if ($endWheelAngle > 0) {
                $calculationAngle = $angleToFront + $angularSpeed;
            } else {
                $calculationAngle = $angleToFront - $angularSpeed;
            }
            $this->logger->debug('Final angle', ['angle' => ($calculationAngle * (180 / pi()))]);
            $positionX = (cos($calculationAngle) * $distanceAC) - $pA[0];
            $positionY = (sin($calculationAngle) * $distanceAC) - $pA[1];

            $this->logger->debug('New position after movement calculates', ['x' => $positionX, 'Y' => $positionY]);

            if ($endWheelAngle > 0) {
                $angle = ($calculationAngle + (pi() / 2)) * (180 / pi());
            } else {
                $angle = ($calculationAngle - (pi() / 2) * (180 / pi()));
            }
            $this->logger->debug('New angle after movement calculates', ['angle' => $angle]);
        } else if ($endWheelAngle == 90) {
            throw new \LogicException('Not implemented');
        } else {
            $positionX = $positionX + cos($angle * (pi() / 180)) * $distance;
            $positionY = $positionY + sin($angle * (pi() / 180)) * $distance;
        }

        $info = new MovementInfo();
        $info->setWheelAngle($endWheelAngle)
            ->setPositionX($positionX)
            ->setPositionY($positionY)
            ->setAngle($angle);

        return $info;
    }
}
