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
    public function __construct(private IndexerInterface|null $indexer = null)
    {
    }

    public function __invoke(SearchIndexMessage $message): void
    {
        // No search indexing activated at all
        if (null === $this->indexer) {
            return;
        }

        if ($message->shouldIndex()) {
            $this->indexer->index($message->getDocument());
        }

        if ($message->shouldDelete()) {
            $this->indexer->delete($message->getDocument());
        }
    }
}
