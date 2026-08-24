<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Cron;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCronJob;
use Contao\CoreBundle\Exception\CronExecutionSkippedException;
use Contao\CoreBundle\Util\ProcessUtil;
use Contao\Image\DeferredImageStorageInterface;
use GuzzleHttp\Promise\PromiseInterface;

#[AsCronJob('minutely')]
class ResizeImagesCron
{
    public function __construct(
        private readonly ProcessUtil $processUtil,
        private readonly DeferredImageStorageInterface $storage,
    ) {
    }

    public function __invoke(string $scope): PromiseInterface
    {
        if (Cron::SCOPE_CLI !== $scope) {
            throw new CronExecutionSkippedException();
        }

        if (!$this->hasDeferredImages()) {
            throw new CronExecutionSkippedException();
        }

        $process = $this->processUtil->createSymfonyConsoleProcess(
            'contao:resize-images',
            '--time-limit=50',
            '--concurrent=1',
        );
        $process->setTimeout(null);

        return $this->processUtil->createPromise($process);
    }

    private function hasDeferredImages(): bool
    {
        foreach ($this->storage->listPaths() as $_) {
            return true;
        }

        return false;
    }
}
