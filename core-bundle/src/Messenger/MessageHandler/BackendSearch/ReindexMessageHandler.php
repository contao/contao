<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Messenger\MessageHandler\BackendSearch;

use Contao\CoreBundle\Job\Jobs;
use Contao\CoreBundle\Messenger\Message\BackendSearch\ReindexMessage;
use Contao\CoreBundle\Messenger\Message\ScopeAwareMessageInterface;
use Contao\CoreBundle\Search\Backend\BackendSearch;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @experimental
 */
#[AsMessageHandler]
class ReindexMessageHandler
{
    public function __construct(
        private readonly BackendSearch $backendSearch,
        private readonly Jobs $jobs,
    ) {
    }

    public function __invoke(ReindexMessage $message): void
    {
        // Cannot run in a web request.
        if (ScopeAwareMessageInterface::SCOPE_CLI !== $message->getScope()) {
            if ($message->getReindexConfig()->getJobId() && ($job = $this->jobs->getByUuid($message->getReindexConfig()->getJobId()))) {
                $this->jobs->persist($job->markFailedBecauseRequiresCLI());
            }

            return;
        }

        $this->backendSearch->reindex($message->getReindexConfig(), false);
    }
}
