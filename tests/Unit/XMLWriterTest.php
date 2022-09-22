<?php

namespace TeamnTime\GrumPHP\PHPUnitDiff\Tests\Unit;

use GrumPHP\Util\Filesystem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use TeamnTime\GrumPHP\PHPUnitDiff\XMLLoader;
use TeamnTime\GrumPHP\PHPUnitDiff\XMLWriter;

class XMLWriterTest extends TestCase
{
    private XMLLoader|MockObject $loader;

    private Filesystem|MockObject $filesystem;

    private XMLWriter $writer;

    public function setUp(): void
    {
        $this->loader = $this->createMock(XMLLoader::class);
        $this->filesystem = $this->createMock(Filesystem::class);

        $this->writer = new XMLWriter($this->loader, $this->filesystem);
    }

    /**
     * @test
     */
    public function it_can_remove_a_file(): void
    {
        $fakePath = './fakepath';

        $this->filesystem
            ->expects($this->once())
            ->method('remove')
            ->with($fakePath);

        $this->writer->removeConfig($fakePath);
    }

    /**
     * @test
     * @dataProvider xmlProvider
     */
    public function it_can_write_a_config_with_testsuites_in_the_existing_file(
        string $existingConfig,
        string $expectedConfig
    ): void {
        $projectRoot = '/project_root';
        $existingConfigPath = './phpunit.xml.dist';
        $testSuiteName = 'ephimerical';
        $files = ['/project_root/src/abc.php', '/project_root/src/def.php'];
        $suffix = time();


        $target = $projectRoot . '/phpunit_diff' . $suffix . '.xml';

        $this->filesystem->expects($this->once())
            ->method('touch')
            ->with($target);

        $this->loader->method('loadFromFile')
            ->with($projectRoot . $existingConfigPath)
            ->willReturn(new SimpleXMLElement($existingConfig, LIBXML_NOBLANKS));

        $this->filesystem->expects($this->once())
            ->method('appendToFile')
            ->with($target, (new SimpleXMLElement($expectedConfig, LIBXML_NOBLANKS))->asXML());

        $this->writer->writeConfig($projectRoot, $existingConfigPath, $testSuiteName, $files, $suffix);
    }

    public function xmlProvider(): array
    {
        $existingConfigWithTestSuites = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="tests/bootstrap.php">
    <php>
        <ini name="display_errors" value="1"/>
    </php>
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
    </testsuites>
</phpunit>
XML;

        $expectedConfigWithTestSuites = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="tests/bootstrap.php">
    <php>
        <ini name="display_errors" value="1"/>
    </php>
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
    
        <testsuite name="ephimerical">
            <file>/project_root/src/abc.php</file>
            <file>/project_root/src/def.php</file>
        </testsuite>
    </testsuites>
</phpunit>
XML;

        $existingConfigWithoutTestSuites = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="tests/bootstrap.php">
    <php>
        <ini name="display_errors" value="1"/>
    </php>
</phpunit>
XML;

        $expectedConfigWithoutTestSuites = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="tests/bootstrap.php">
    <php>
        <ini name="display_errors" value="1"/>
    </php>
    <testsuites>
        <testsuite name="ephimerical">
            <file>/project_root/src/abc.php</file>
            <file>/project_root/src/def.php</file>
        </testsuite>
    </testsuites>
</phpunit>
XML;

        return [
            [ $existingConfigWithTestSuites, $expectedConfigWithTestSuites ],
            [ $existingConfigWithoutTestSuites, $expectedConfigWithoutTestSuites ],
        ];
    }
}
