<?php

namespace TeamnTime\GrumPHP\PHPUnitDiff;

use GrumPHP\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ExtensionLoader implements ExtensionInterface
{
    public function load(ContainerBuilder $container): void
    {
        $container->register(XMLLoader::class, XMLLoader::class);
        $container->register(DiffLocatorFunctionLoader::class, DiffLocatorFunctionLoader::class);
        $container->register(XMLWriter::class, XMLWriter::class)
            ->addArgument(new Reference(XMLLoader::class))
            ->addArgument(new Reference('grumphp.util.filesystem'));

        $container->register('task.phpunit_diff', PHPUnitDiffRunnerTask::class)
            ->addArgument(new Reference('process_builder'))
            ->addArgument(new Reference('formatter.raw_process'))
            ->addTag('grumphp.task', ['task' => 'phpunit_diff']);
    }
}
