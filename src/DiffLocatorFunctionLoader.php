<?php

namespace TeamnTime\GrumPHP\PHPUnitDiff;

use RuntimeException;

class DiffLocatorFunctionLoader
{
    public function loadClassNameTransformer(?string $diffLocatorFunctionPath): void
    {
        $this->decideWithFunctionToLoad($diffLocatorFunctionPath);

        if (!function_exists('transformClassToTest')) {
            throw new RuntimeException(
                sprintf(
                    'The given file %s does not contain a function ' .
                        'transformClassToTest(string $changedClassName): ?string',
                    $diffLocatorFunctionPath
                )
            );
        }
    }

    private function decideWithFunctionToLoad(?string $diffLocatorFunctionPath): void
    {
        if ($diffLocatorFunctionPath === null || !file_exists($diffLocatorFunctionPath)) {
            require __DIR__ . '/DefaultTransformFunction.php';

            return;
        }

        require $diffLocatorFunctionPath;
    }
}
