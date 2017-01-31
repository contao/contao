<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Event;

use Contao\CoreBundle\Event\PreviewUrlConvertEvent;

/**
 * Tests the PreviewUrlConvertEvent class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class PreviewUrlConvertEventTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $event = new PreviewUrlConvertEvent();

        $this->assertInstanceOf('Contao\CoreBundle\Event\PreviewUrlConvertEvent', $event);
    }

    /**
     * Tests the URL getter and setter.
     */
    public function testUrlGetterSetter()
    {
        $event = new PreviewUrlConvertEvent();

        $this->assertNull($event->getUrl());

        $event->setUrl('http://localhost');

        $this->assertEquals('http://localhost', $event->getUrl());
    }
}
