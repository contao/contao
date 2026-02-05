<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Search\Backend\EventListener;

use Contao\CoreBundle\Event\InvalidateCacheTagsEvent;
use Contao\CoreBundle\Search\Backend\BackendSearch;
use Contao\CoreBundle\Search\Backend\GroupedDocumentIds;
use Contao\CoreBundle\Search\Backend\ReindexConfig;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
class TriggerReindexOnTableDataContainerInvalidationListener
{
    public function __construct(private readonly BackendSearch $backendSearch)
    {
    }

    public function __invoke(InvalidateCacheTagsEvent $event): void
    {
        $documentIds = new GroupedDocumentIds();

        foreach ($event->getTags() as $tag) {
            if (preg_match('/^(contao\.db\.\w+)\.(\d+)$/', $tag, $matches)) {
                $documentIds->addIdToType((string) $matches[1], (string) $matches[2]);
            }
        }

        if ($documentIds->isEmpty()) {
            return;
        }

        $this->backendSearch->reindex((new ReindexConfig())->limitToDocumentIds($documentIds));
    }
}
