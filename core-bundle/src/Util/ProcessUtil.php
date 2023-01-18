<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Util;

use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Symfony\Contracts\Service\ResetInterface;

class ProcessUtil implements ResetInterface
{
    private string|null $phpBinary = null;

    public function __construct(private readonly string $consolePath)
    {
    }

    /**
     * Creates a GuzzleHttp/Promise for a Symfony Process instance.
     *
     * @param bool $start automatically calls Process::start() if true
     */
    public function createPromise(Process $process, bool $start = true): PromiseInterface
    {
        $promise = new Promise(
            static function () use (&$promise, $process): void {
                $process->wait();

                if ($process->isSuccessful()) {
                    $promise->resolve($process->getOutput());
                } else {
                    $promise->reject($process->getErrorOutput() ?: $process->getOutput());
                }
            }
        );

        if ($start) {
            $process->start();
        }

        return $promise;
    }

    public function createSymfonyConsoleProcess(string $command, string ...$commandArguments): Process
    {
        return new Process(array_merge([$this->getPhpBinary(), $this->consolePath, $command], $commandArguments));
    }

    public function reset(): void
    {
        $this->phpBinary = null;
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
