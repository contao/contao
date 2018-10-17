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

use Contao\CoreBundle\Event\PreviewUrlCreateEvent;
use PHPUnit\Framework\TestCase;

class PreviewUrlCreateEventTest extends TestCase
{
    public function testSupportsReadingTheId(): void
    {
        $event = new PreviewUrlCreateEvent('news', 12);

        $this->assertSame(12, $event->getId());
    }

    public function testSupportsReadingTheKey(): void
    {
        $event = new PreviewUrlCreateEvent('news', 'files/public/image.jpg');

        $this->assertSame('news', $event->getKey());
    }

    public function testSupportsReadingAndWritingTheQueryString(): void
    {
        $event = new PreviewUrlCreateEvent('news', 12);

        $this->assertNull($event->getQuery());

        $event->setQuery('act=edit&id=12');

        $this->assertSame('act=edit&id=12', $event->getQuery());
    }
}
