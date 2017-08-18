<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Event;

use Contao\CoreBundle\Event\PreviewUrlCreateEvent;
use PHPUnit\Framework\TestCase;

/**
 * Tests the PreviewUrlCreateEvent class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class PreviewUrlCreateEventTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testCanBeInstantiated()
    {
        $event = new PreviewUrlCreateEvent('news', 12);

        $this->assertInstanceOf('Contao\CoreBundle\Event\PreviewUrlCreateEvent', $event);
    }

    /**
     * Tests the getId() method.
     */
    public function testGetId()
    {
        $event = new PreviewUrlCreateEvent('news', 12);

        $this->assertSame(12, $event->getId());
    }

    /**
     * Tests the getKey() method.
     */
    public function testGetKey()
    {
        $event = new PreviewUrlCreateEvent('news', 12);

        $this->assertSame('news', $event->getKey());
    }

    /**
     * Tests the query getter and setter.
     */
    public function testQueryGetterSetter()
    {
        $event = new PreviewUrlCreateEvent('news', 12);

        $this->assertNull($event->getQuery());

        $event->setQuery('act=edit&id=12');

        $this->assertSame('act=edit&id=12', $event->getQuery());
    }
}
