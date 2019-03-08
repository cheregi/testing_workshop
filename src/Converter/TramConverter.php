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

    const DATA_TYPE_UPSTREAM_POSITION = 5;

    const DATA_TYPE_REQUEST_POSITION = 6;

    public function convert($dataType, $data)
    {
        switch ($dataType) {
            case static::DATA_TYPE_LASER_SENSOR:
                return $this->convertLaserData($data);
            case static::DATA_TYPE_ALTIMETER:
                return $this->convertAltimeterData($data);
            case static::DATA_TYPE_GYROSCOPE:
                return $this->convertGyroscopicInfo($data);
            case static::DATA_TYPE_POSITION:
                return $this->convertPositionData($data);
            case static::DATA_TYPE_UPSTREAM_POSITION:
                return $this->convertPositionUpstream($data);
            case static::DATA_TYPE_REQUEST_POSITION:
                return $this->convertPositionRequest($data);
        }
    }

    private function convertPositionRequest(array $data)
    {
        return sprintf('%s%s', implode(chr(0x1f), $data), chr(0x03));
    }

    private function convertPositionUpstream(string $data)
    {
        if (substr($data, -1) !== chr(0x03)) {
            return null;
        }

        $data = explode(chr(0x1f), $data);
        if (count($data) !== 2) {
            return null;
        }

        return array_map('floatval', $data);
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
