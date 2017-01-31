<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Event;

use Contao\CoreBundle\Event\PreviewUrlCreateEvent;

/**
 * Tests the PreviewUrlCreateEvent class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class PreviewUrlCreateEventTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
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

        $this->assertEquals(12, $event->getId());
    }

    /**
     * Tests the getKey() method.
     */
    public function testGetKey()
    {
        $event = new PreviewUrlCreateEvent('news', 12);

        $this->assertEquals('news', $event->getKey());
    }

    /**
     * Tests the query getter and setter.
     */
    public function testQueryGetterSetter()
    {
        $event = new PreviewUrlCreateEvent('news', 12);

        $this->assertNull($event->getQuery());

        $event->setQuery('act=edit&id=12');

        $this->assertEquals('act=edit&id=12', $event->getQuery());
    }
}
