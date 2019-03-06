<?php
declare(strict_types=1);

namespace App\Converter;

use App\Resolver\Gyroscope\GyroscopicInfo;
use App\Resolver\RoverInformation\RoverInformation;

class TramConverter
{
    const DATA_TYPE_LASER_SENSOR = 1;

    const DATA_TYPE_ALTIMETER = 2;

    const DATA_TYPE_GYROSCOPE = 3;

    const DATA_TYPE_POSITION = 4;

    public function convert($dataType, $data)
    {
        if ($dataType === static::DATA_TYPE_LASER_SENSOR) {
            return $this->convertLaserData($data);
        } else if ($dataType === static::DATA_TYPE_ALTIMETER) {
            return $this->convertAltimeterData($data);
        } else if ($dataType === static::DATA_TYPE_GYROSCOPE) {
            return $this->convertGyroscopicInfo($data);
        } else if ($dataType === static::DATA_TYPE_POSITION) {
            return $this->convertPositionData($data);
        }
    }

    private function convertPositionData(RoverInformation $information)
    {
        return sprintf(
            '%s%s%s',
            chr(static::DATA_TYPE_POSITION),
            implode(
                chr(0x1d),
                [
                    $information->getPositionX(),
                    $information->getPositionY(),
                    $information->getWheelAngle()
                ]
            ),
            chr(0x03)
        );
    }

    private function convertAltimeterData($data)
    {
        return sprintf('%s%s%s', chr(static::DATA_TYPE_ALTIMETER), $data, chr(0x03));
    }

    private function convertLaserData($data)
    {
        $dataInfo = [];
        foreach ($data as ['x' => $x, 'y' => $y, 'z' => $z]) {
            $dataInfo[] = implode(chr(0x1f), [$x, $y, $z]);
        }
        return sprintf('%s%s%s', chr(static::DATA_TYPE_LASER_SENSOR), implode(chr(0x1d), $dataInfo), chr(0x03));
    }

    private function convertGyroscopicInfo(GyroscopicInfo $dataInfo)
    {
        return sprintf(
            '%s%s%s',
            chr(static::DATA_TYPE_GYROSCOPE),
            implode(
                chr(0x1d),
                [
                    $dataInfo->getXyAngle(),
                    $dataInfo->getXzAngle(),
                    $dataInfo->getYzAngle()
                ]
            ),
            chr(0x03)
        );
    }
}
