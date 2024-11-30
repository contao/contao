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

use Contao\CoreBundle\Messenger\Message\BackendSearch\ReindexMessage;
use Contao\CoreBundle\Search\Backend\BackendSearch;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @experimental
 */
#[AsMessageHandler]
class ReindexMessageHandler
{
    public function __construct(private readonly BackendSearch $backendSearch)
    {
    }

    public function __invoke(ReindexMessage $message): void
    {
        // Cannot run in a web request. TODO: Make this feature generally available as
        // WebWorker config for all kinds of messages
        if (\PHP_SAPI !== 'cli') {
            return;
        }

        $this->backendSearch->reindex($message->getReindexConfig(), false);
    }
}
