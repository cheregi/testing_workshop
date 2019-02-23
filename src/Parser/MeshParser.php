<?php
declare(strict_types=1);
namespace App\Parser;

use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\SplFileInfo;

class MeshParser
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * MeshParser constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Parse
     *
     * Parse a file and return the set of points from file
     *
     * @param SplFileInfo $file The file SplFileInfo representation
     *
     * @return array
     */
    public function parse(SplFileInfo $file)
    {
        $fileRes = fopen($file->getRealPath(), 'r');

        while ($line = fgets($fileRes, 1000)) {
            if (substr($line, 0, 2) == 'v ') {
                yield array_slice(explode(' ', $line), 1, 3);
            }
        }
        fclose($fileRes);

        return null;
    }

    /**
     * @param SplFileInfo $file
     *
     * @return int
     */
    public function getPointCount(SplFileInfo $file)
    {
        $fileRes = fopen($file->getRealPath(), 'r');

        $count = 0;
        while ($line = fgets($fileRes, 1000)) {
            if (substr($line, 0, 2) == 'v ') {
                $count++;
            }
        }
        fclose($fileRes);

        return $count;
    }
}
