<?php

namespace TeamnTime\GrumPHP\PHPUnitDiff;

use InvalidArgumentException;
use SimpleXMLElement;

class XMLLoader
{
    public function loadFromFile(string $path): SimpleXMLElement
    {
        $result = simplexml_load_file($path);

        if ($result === false) {
            throw new InvalidArgumentException(
                'Given path for PHPUnit config file did not yield a valid XML config: ' . $path
            );
        }

        return $result;
    }
}
