<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Search\Backend\EventListener;

use Contao\CoreBundle\Event\InvalidateCacheTagsEvent;
use Contao\CoreBundle\Search\Backend\BackendSearch;
use Contao\CoreBundle\Search\Backend\EventListener\TriggerReindexOnTableDataContainerInvalidationListener;
use Contao\CoreBundle\Search\Backend\ReindexConfig;
use PHPUnit\Framework\TestCase;

class TriggerReindexOnTableDataContainerInvalidationListenerTest extends TestCase
{
    public function testInvokeTriggersReindexForMatchingTags(): void
    {
        $backendSearch = $this->createMock(BackendSearch::class);
        $backendSearch
            ->expects($this->once())
            ->method('reindex')
            ->with($this->callback(static fn (ReindexConfig $config) => ['contao.db.my_table' => ['42', '43'], 'contao.db.my_other_table' => ['42']] === $config->getLimitedDocumentIds()->toArray()))
        ;

        $listener = new TriggerReindexOnTableDataContainerInvalidationListener($backendSearch);
        $event = new InvalidateCacheTagsEvent([
            'contao.db.my_table.42',
            'contao.db.my_table.43',
            'contao.db.my_other_table.42',
            'contao.db.my_other_table.invalid-id',
            'some.other.tag',
        ]);

        $listener($event);
    }
}
