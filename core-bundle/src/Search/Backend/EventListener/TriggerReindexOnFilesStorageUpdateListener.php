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

use Contao\CoreBundle\Filesystem\Dbafs\DbafsChangeEvent;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Contao\CoreBundle\Search\Backend\BackendSearch;
use Contao\CoreBundle\Search\Backend\GroupedDocumentIds;
use Contao\CoreBundle\Search\Backend\Provider\FilesStorageProvider;
use Contao\CoreBundle\Search\Backend\ReindexConfig;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
class TriggerReindexOnFilesStorageUpdateListener
{
    public function __construct(
        private readonly BackendSearch $backendSearch,
        private readonly VirtualFilesystem $filesStorage,
    ) {
    }

    public function __invoke(DbafsChangeEvent $event): void
    {
        $documentIds = new GroupedDocumentIds();

        foreach ($this->generateFilesystemItemsFromAllSources($event) as $item) {
            $documentIds->addIdToType(FilesStorageProvider::TYPE, $item->getPath());
        }

        if ($documentIds->isEmpty()) {
            return;
        }

        $this->backendSearch->reindex((new ReindexConfig())->limitToDocumentIds($documentIds));
    }

    private function generateFilesystemItemsFromAllSources(DbafsChangeEvent $event): \Generator
    {
        yield from $event->getCreatedFilesystemItems($this->filesStorage)->files();
        yield from $event->getUpdatedFilesystemItems($this->filesStorage)->files();
        yield from $event->getDeletedFilesystemItems($this->filesStorage)->files();
    }
}
