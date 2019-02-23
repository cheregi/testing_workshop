<?php
declare(strict_types=1);

namespace App\Converter;

class TramConverter
{
    const DATA_TYPE_LASER_SENSOR = 1;

    public function convert($dataType, $data)
    {
        if ($dataType === static::DATA_TYPE_LASER_SENSOR) {
            return $this->convertLaserData($data);
        }
    }

    private function convertLaserData($data)
    {
        $dataInfo = [];
        foreach ($data as ['x' => $x, 'y' => $y, 'z' => $z]) {
            $dataInfo[] = implode(chr(0x1f), [$x, $y, $z]);
        }
        return sprintf('%s%s%s', chr(static::DATA_TYPE_LASER_SENSOR), implode(chr(0x1f), $dataInfo), chr(0x03));
    }
}
