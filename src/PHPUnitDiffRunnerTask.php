<?php

namespace TeamnTime\GrumPHP\PHPUnitDiff;

use GrumPHP\Formatter\ProcessFormatterInterface;
use GrumPHP\Process\ProcessBuilder;
use GrumPHP\Runner\TaskResult;
use GrumPHP\Runner\TaskResultInterface;
use GrumPHP\Task\AbstractExternalTask;
use GrumPHP\Task\Context\ContextInterface;
use GrumPHP\Util\Paths;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Process\Process;

class PHPUnitDiffRunnerTask extends AbstractExternalTask
{
    private XMLWriter $xmlWriter;

    private Paths $paths;

    private DiffLocatorFunctionLoader $functionLoader;

    public function __construct(
        ProcessBuilder $processBuilder,
        ProcessFormatterInterface $formatter,
        Paths $paths,
        XMLWriter $xmlWriter,
        DiffLocatorFunctionLoader $functionLoader
    ) {
        parent::__construct($processBuilder, $formatter);

        $this->xmlWriter = $xmlWriter;
        $this->paths = $paths;
        $this->functionLoader = $functionLoader;
    }

    public static function getConfigurableOptions(): OptionsResolver
    {
        $resolver = new OptionsResolver();

        $resolver->setDefaults([
            'config_file' => null,
            'diff_locator' => null,
            'always_execute' => false,
            'always_delete_generated_configs' => false,
            'order' => null,
        ]);

        $resolver->addAllowedTypes('config_file', ['string']);
        $resolver->addAllowedTypes('diff_locator', ['null', 'string']);
        $resolver->addAllowedTypes('always_execute', ['bool']);
        $resolver->addAllowedTypes('always_delete_generated_configs', ['bool']);
        $resolver->addAllowedTypes('order', ['null', 'string']);

        return $resolver;
    }

    public function canRunInContext(ContextInterface $context): bool
    {
        return true;
    }

    public function run(ContextInterface $context): TaskResultInterface
    {
        $config = $this->getConfig()->getOptions();
        $projectRoot = $this->paths->getProjectDir();

        $this->functionLoader->loadClassNameTransformer($projectRoot . $config['diff_locator']);

        $files = $context->getFiles()->path('src')->name('*.php')->toArray();
        $testFiles = [];

        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            $testFiles[] = transformClassToTest($file->getRelativePathname());
        }

        $testFiles = array_filter($testFiles);

        $testSuiteName = 'ephimerical';

        $configPath = $this->xmlWriter->writeConfig(
            $projectRoot,
            $config['config_file'],
            $testSuiteName,
            $testFiles
        );

        $process = $this->buildProcess($configPath);

        $process->run();

        return $this->renderOutput(
            $process,
            $context,
            $testSuiteName,
            $configPath,
            $config['always_delete_generated_configs']
        );
    }

    private function buildProcess(string $configPath): Process
    {
        $config = $this->getConfig()->getOptions();

        $arguments = $this->processBuilder->createArgumentsForCommand('phpunit');
        $arguments->addOptionalArgument('--configuration=%s', $configPath);
        $arguments->addOptionalArgument('--testsuite=%s', 'ephimerical');
        $arguments->addOptionalArgument('--order-by=%s', $config['order']);

        return $this->processBuilder->buildProcess($arguments);
    }

    private function renderOutput(
        Process $process,
        ContextInterface $context,
        string $testSuiteName,
        string $configPath,
        bool $alwaysDeleteGeneratedConfigs
    ): TaskResult {
        if ($process->isSuccessful() || $alwaysDeleteGeneratedConfigs) {
            $this->xmlWriter->removeConfig($configPath);
        }

        if ($process->isSuccessful()) {
            return TaskResult::createPassed($this, $context);
        }

        $output = $this->formatter->format($process);

        $instruction = sprintf(
            'To re-run the failed tests, you can use the config file with group "%s": %s',
            $testSuiteName,
            $configPath
        );

        $output = $output . PHP_EOL . $instruction;

        return TaskResult::createFailed($this, $context, $output);
    }
}
