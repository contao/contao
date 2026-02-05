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

use Contao\CoreBundle\Event\BackendSearch\EnhanceHitEvent;
use Contao\CoreBundle\Search\Backend\Document;
use Contao\CoreBundle\Search\Backend\Hit;
use Contao\CoreBundle\Tests\TestCase;

class EnhanceHitEventTest extends TestCase
{
    public function testCanModifyHit(): void
    {
        $hit = new Hit(new Document('42', 'type', 'searchable'), 'title', 'https://example.com');
        $event = new EnhanceHitEvent($hit);
        $this->assertSame($hit, $event->getHit());
        $this->assertSame([], $event->getHit()->getMetadata());

        $hit = $hit->withMetadata(['something-on-top-for-my-template' => 'foobar']);
        $this->assertSame([], $event->getHit()->getMetadata());
        $event->setHit($hit);
        $this->assertSame(['something-on-top-for-my-template' => 'foobar'], $event->getHit()->getMetadata());
    }

    public function testCanRemoveHitCompletelyFromResults(): void
    {
        $hit = new Hit(new Document('42', 'type', 'searchable'), 'title', 'https://example.com');
        $event = new EnhanceHitEvent($hit);
        $event->setHit(null);
        $this->assertNull($event->getHit());
    }
}
