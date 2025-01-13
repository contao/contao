<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Event\BackendSearch;

use Contao\CoreBundle\Event\BackendSearch\IndexDocumentEvent;
use Contao\CoreBundle\Search\Backend\Document;
use Contao\CoreBundle\Tests\TestCase;

class IndexDocumentEventTest extends TestCase
{
    public function testCanModifyDocument(): void
    {
        $document = new Document('42', 'type', 'searchable');
        $event = new IndexDocumentEvent($document);
        $this->assertSame($document, $event->getDocument());
        $this->assertSame([], $event->getDocument()->getMetadata());

        $document = $document->withMetadata(['something-on-top-for-my-template' => 'foobar']);
        $this->assertSame([], $event->getDocument()->getMetadata());
        $event->setDocument($document);
        $this->assertSame(['something-on-top-for-my-template' => 'foobar'], $event->getDocument()->getMetadata());
    }

    public function testCanPreventDocumentFromBeingIndexedCompletely(): void
    {
        $document = new Document('42', 'type', 'searchable');
        $event = new IndexDocumentEvent($document);
        $event->setDocument(null);
        $this->assertNull($event->getDocument());
    }
}
