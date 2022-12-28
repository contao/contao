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

class ProcessUtil
{
    private static string|null $phpBinary = null;

    /**
     * Creates a GuzzleHttp/Promise for a Symfony Process instance.
     *
     * @param bool $start automatically calls Process::start() if true
     */
    public static function createPromise(Process $process, bool $start = true): PromiseInterface
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

    public static function createSymfonyConsoleProcess(string $consolePath, string $command, string ...$commandArguments): Process
    {
        $arguments = [];
        $arguments[] = self::getPhpBinary();
        $arguments[] = $consolePath;
        $arguments[] = $command;
        $arguments = array_merge($arguments, $commandArguments);

        return new Process($arguments);
    }

    private static function getPhpBinary(): string
    {
        if (null === self::$phpBinary) {
            $executableFinder = new PhpExecutableFinder();
            self::$phpBinary = $executableFinder->find();
        }

        return self::$phpBinary;
    }
}
