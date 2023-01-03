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
use Symfony\Component\Process\Process;

class ProcessUtil
{
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
}
