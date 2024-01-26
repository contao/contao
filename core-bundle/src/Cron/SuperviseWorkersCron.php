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
use GuzzleHttp\Promise\PromiseInterface;

#[AsCronJob('minutely')]
class SuperviseWorkersCron
{
    public function __construct(private readonly ProcessUtil $processUtil)
    {
    }

    public function __invoke(string $scope): PromiseInterface
    {
        if (Cron::SCOPE_CLI !== $scope) {
            throw new CronExecutionSkippedException();
        }

        return $this->processUtil->createPromise(
            $this->processUtil->createSymfonyConsoleProcess('contao:supervise-workers'),
        );
    }
}
