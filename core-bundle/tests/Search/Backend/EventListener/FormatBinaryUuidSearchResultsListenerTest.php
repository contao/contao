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

use Contao\CoreBundle\Search\Backend\Event\FormatTableDataContainerDocumentEvent;
use Contao\CoreBundle\Search\Backend\EventListener\FormatBinaryUuidSearchResultsListener;
use Contao\StringUtil;
use PHPUnit\Framework\TestCase;

class FormatBinaryUuidSearchResultsListenerTest extends TestCase
{
    public function testInvokeWithRegularContent(): void
    {
        $event = new FormatTableDataContainerDocumentEvent('This is some content', []);
        $listener = new FormatBinaryUuidSearchResultsListener();
        $listener($event);
        $this->assertSame('This is some content', $event->getSearchableContent());
    }

    public function testInvokeWithBinaryUuid(): void
    {
        $uuid = '9e474bae-ce18-11ec-9465-cadae3e5cf5d';
        $event = new FormatTableDataContainerDocumentEvent(StringUtil::uuidToBin($uuid), []);
        $listener = new FormatBinaryUuidSearchResultsListener();
        $listener($event);
        $this->assertSame($uuid, $event->getSearchableContent());
    }
}
