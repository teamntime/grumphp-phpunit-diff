<?php

namespace TeamnTime\GrumPHP\PHPUnitDiff;

function transformClassToTest(string $changedClassName): ?string
{
    $testClassname = str_replace('src/', 'tests/Unit/', $changedClassName);

    if (!str_contains($testClassname, 'Test.php')) {
        $testClassname = str_replace('.php', 'Test.php', $testClassname);
    }

    if (!file_exists($testClassname)) {
        return null;
    }

    return $testClassname;
}
