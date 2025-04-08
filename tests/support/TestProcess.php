<?php

namespace TeamnTime\GrumPHP\PHPUnitDiff\Tests\support;

use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;

class TestProcess extends Process
{
    public string $mockedOutput;

    public string $mockedErrorOutput;

    public int $mockedStatusCode = 0;

    public function start(?callable $callback = null, array $env = [])
    {
        if ($this->isRunning()) {
            throw new RuntimeException('Process is already running.');
        }
    }

    public function wait(?callable $callback = null): int
    {
        return $this->mockedStatusCode;
    }

    public function getExitCode(): ?int
    {
        return $this->mockedStatusCode;
    }

    public function getOutput(): string
    {
        return $this->mockedOutput;
    }

    public function getErrorOutput(): string
    {
        return $this->mockedErrorOutput;
    }
}
