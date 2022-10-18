<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Cron;

use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

abstract class AbstractConsoleCron
{
    private string|null $phpBinary = null;

    public function __construct(private string $consolePath)
    {
    }

    protected function createProcess(string $command, string ...$commandArguments): Process
    {
        $arguments = [];
        $arguments[] = $this->getPhpBinary();
        $arguments[] = $this->consolePath;
        $arguments = array_merge($arguments, $this->getPhpArguments());
        $arguments[] = $command;
        $arguments = array_merge($arguments, $commandArguments);

        return new Process($arguments);
    }

    protected function getPhpArguments(): array
    {
        return [];
    }

    private function getPhpBinary(): string
    {
        if (null === $this->phpBinary) {
            $executableFinder = new PhpExecutableFinder();
            $this->phpBinary = $executableFinder->find();
        }

        return $this->phpBinary;
    }
}
