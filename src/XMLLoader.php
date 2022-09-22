<?php

namespace TeamnTime\GrumPHP\PHPUnitDiff;

use SimpleXMLElement;

class XMLLoader
{
    public function loadFromFile(string $path): ?SimpleXMLElement
    {
        return simplexml_load_file($path);
    }
}
