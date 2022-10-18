<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Messenger\MessageHandler;

use Contao\CoreBundle\Messenger\Message\SearchIndexMessage;
use Contao\CoreBundle\Search\Indexer\IndexerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class SearchIndexMessageHandler implements MessageHandlerInterface
{
    public function __construct(private IndexerInterface $indexer)
    {
    }

    public function __invoke(SearchIndexMessage $message): void
    {
        if ($message->shouldIndex()) {
            $this->indexer->index($message->getDocument());
        }

        if ($message->shouldDelete()) {
            $this->indexer->delete($message->getDocument());
        }
    }
}
