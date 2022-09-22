<?php

namespace TeamnTime\GrumPHP\PHPUnitDiff;

use GrumPHP\Util\Filesystem;

class XMLWriter
{
    private XMLLoader $loader;

    private Filesystem $filesystem;

    public function __construct(XMLLoader $loader, Filesystem $filesystem)
    {
        $this->loader = $loader;
        $this->filesystem = $filesystem;
    }

    /**
     * @param string[] $files
     *
     * @return string Path where the file was written
     */
    public function writeConfig(
        string $projectRoot,
        string $existingConfig,
        string $testSuiteName,
        array $files,
        string|int $suffix = null
    ): string {
        $suffix = $suffix ?? time();
        $target = $projectRoot . '/phpunit_diff' . $suffix . '.xml';

        $this->filesystem->touch($target);

        $config = $this->loader->loadFromFile($projectRoot . $existingConfig);

        if (!isset($config->testsuites)) {
            $config->addChild('testsuites');
        }

        $testSuite = $config->testsuites->addChild('testsuite');

        $testSuite->addAttribute('name', $testSuiteName);

        foreach ($files as $file) {
            $testSuite->addChild('file', $file);
        }

        $this->filesystem->appendToFile($target, $config->asXML());

        return $target;
    }

    public function removeConfig(string $path): void
    {
        $this->filesystem->remove($path);
    }
}
