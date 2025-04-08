<?php

namespace TeamnTime\GrumPHP\PHPUnitDiff;

use GrumPHP\Extension\ExtensionInterface;

class ExtensionLoader implements ExtensionInterface
{
    public function imports(): iterable
    {
        $configDir = dirname(__DIR__) . '/config';

        yield $configDir.'/services.yaml';
    }
}
