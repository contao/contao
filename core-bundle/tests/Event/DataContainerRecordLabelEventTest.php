<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Event;

use Contao\CoreBundle\Event\DataContainerRecordLabelEvent;
use PHPUnit\Framework\TestCase;

class DataContainerRecordLabelEventTest extends TestCase
{
    public function testSupportsReadingAndWritingLabel(): void
    {
        $event = new DataContainerRecordLabelEvent('contao.db.tl_foo.123', ['id' => 123]);
        $this->assertNull($event->getLabel());
        $this->assertSame('contao.db.tl_foo.123', $event->getIdentifier());
        $this->assertSame(['id' => 123], $event->getData());
        $this->assertNull($event->getLabel());

        $this->assertSame($event, $event->setLabel('Foo'));
        $this->assertSame('Foo', $event->getLabel());

        $event->setLabel(null);
        $this->assertNull($event->getLabel());
    }
}
