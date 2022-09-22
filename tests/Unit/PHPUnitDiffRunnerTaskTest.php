<?php

namespace TeamnTime\GrumPHP\PHPUnitDiff\Tests\Unit;

use Exception;
use GrumPHP\Collection\FilesCollection;
use GrumPHP\Collection\ProcessArgumentsCollection;
use GrumPHP\Formatter\ProcessFormatterInterface;
use GrumPHP\Process\ProcessBuilder;
use GrumPHP\Runner\TaskResultInterface;
use GrumPHP\Task\Config\Metadata;
use GrumPHP\Task\Config\TaskConfig;
use GrumPHP\Task\Context\ContextInterface;
use GrumPHP\Task\Context\GitPreCommitContext;
use GrumPHP\Task\Context\RunContext;
use GrumPHP\Util\Paths;
use PHPUnit\Framework\MockObject\MockBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\SplFileInfo;
use TeamnTime\GrumPHP\PHPUnitDiff\DiffLocatorFunctionLoader;
use TeamnTime\GrumPHP\PHPUnitDiff\PHPUnitDiffRunnerTask;
use TeamnTime\GrumPHP\PHPUnitDiff\Tests\support\TestProcess;
use TeamnTime\GrumPHP\PHPUnitDiff\XMLWriter;

class PHPUnitDiffRunnerTaskTest extends TestCase
{
    private XMLWriter|MockObject $xmlWriter;

    private Paths|MockObject $paths;

    private ProcessBuilder|MockBuilder $processBuilder;

    private ProcessFormatterInterface|MockBuilder $formatter;

    private DiffLocatorFunctionLoader $functionLoader;

    private PHPUnitDiffRunnerTask $task;

    public function setUp(): void
    {
        $this->xmlWriter = $this->createMock(XMLWriter::class);
        $this->paths = $this->createMock(Paths::class);
        $this->processBuilder = $this->createMock(ProcessBuilder::class);
        $this->formatter = $this->createMock(ProcessFormatterInterface::class);
        $this->functionLoader = $this->createMock(DiffLocatorFunctionLoader::class);

        $this->task = new PHPUnitDiffRunnerTask(
            $this->processBuilder,
            $this->formatter,
            $this->paths,
            $this->xmlWriter,
            $this->functionLoader
        );
    }

    /**
     * @test
     */
    public function it_has_configured_options(): void
    {
        $resolver = $this->task::getConfigurableOptions();

        $workingConfig = [
            'config_file' => './phpunit.xml',
            'always_delete_generated_configs' => true,
            'diff_locator' => './diff-locator.php',
            'order' => null,
        ];

        $resolver->resolve($workingConfig);

        $nonWorkingConfig = [
            'config_file' => null,
            'always_delete_generated_configs' => 'kinda-true',
        ];

        $this->expectException(Exception::class);

        $resolver->resolve($nonWorkingConfig);
    }

    /**
     * @test
     * @dataProvider processProvider
     */
    public function it_can_successfully_run_on_commit(
        ContextInterface $context,
        int $statusCode,
        string $output,
        string $errorOutput
    ): void {
        $config = [
            'config_file' => './phpunit.xml',
            'always_delete_generated_configs' => true,
            'diff_locator' => './diff-locator.php',
            'order' => null,
        ];

        /** @var PHPUnitDiffRunnerTask $configuredTask */
        $configuredTask = $this->task->withConfig(
            new TaskConfig('phpunit_diff', $config, new Metadata([]))
        );

        $this->task = $configuredTask;

        $projectRoot = '/project_root';
        $generatedXml = $projectRoot . '/phpunit_diff.xml';
        $testSuite = 'ephimerical';

        $this->functionLoader->expects($this->once())
            ->method('loadClassNameTransformer')
            ->with($config['diff_locator']);

        $this->paths
            ->method('getProjectDir')
            ->willReturn($projectRoot);

        $this->xmlWriter
            ->method('writeConfig')
            ->willReturn($generatedXml);

        $this->processBuilder
            ->expects($this->once())
            ->method('createArgumentsForCommand')
            ->willReturn(new ProcessArgumentsCollection());

        $expectedCommandConfig = new ProcessArgumentsCollection();

        $expectedCommandConfig->addOptionalArgument('--configuration=%s', $generatedXml);
        $expectedCommandConfig->addOptionalArgument('--testsuite=%s', $testSuite);
        $expectedCommandConfig->addOptionalArgument('--order-by=%s', null);

        $testProcess = new TestProcess($expectedCommandConfig->getValues());

        $testProcess->mockedStatusCode = $statusCode;
        $testProcess->mockedOutput = $output;
        $testProcess->mockedErrorOutput = $errorOutput;

        $this->processBuilder
            ->expects($this->once())
            ->method('buildProcess')
            ->with($expectedCommandConfig)
            ->willReturn($testProcess);

        $result = $this->task->run($context);

        $this->assertEquals($statusCode, $result->getResultCode());
        $this->assertEquals($output . $errorOutput, $result->getMessage());
    }

    /**
     * @return array[]|array< int, array<ContextInterface, int, string, string> >
     */
    public function processProvider(): array
    {
        $changedFiles = [
            new SplFileInfo('/project_root/src/abc.php', './src', 'abc.php'),
            new SplFileInfo('/project_root/src/def.php', './src', 'def.php' )
        ];

        $commitContext = new GitPreCommitContext(new FilesCollection($changedFiles));
        $runContext = new RunContext(new FilesCollection($changedFiles));

        return [
            [ $commitContext, TaskResultInterface::PASSED, '', ''],
            [ $runContext, TaskResultInterface::PASSED, '', ''],
            [
                $commitContext,
                TaskResultInterface::FAILED,
                '',
                PHP_EOL . 'To re-run the failed tests, you can use the config file with group "ephimerical": ' .
                    '/project_root/phpunit_diff.xml'
            ],
            [
                $runContext,
                TaskResultInterface::FAILED,
                '',
                PHP_EOL . 'To re-run the failed tests, you can use the config file with group "ephimerical": ' .
                    '/project_root/phpunit_diff.xml'
            ],
        ];
    }
}
