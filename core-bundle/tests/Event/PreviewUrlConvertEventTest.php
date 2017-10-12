<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Event;

use Contao\CoreBundle\Event\PreviewUrlConvertEvent;
use PHPUnit\Framework\TestCase;

/**
 * Tests the PreviewUrlConvertEvent class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class PreviewUrlConvertEventTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testCanBeInstantiated()
    {
        $event = new PreviewUrlConvertEvent();

        $this->assertInstanceOf('Contao\CoreBundle\Event\PreviewUrlConvertEvent', $event);
    }

    /**
     * Tests the URL getter and setter.
     */
    public function testSupportsReadingAndWritingTheUrl()
    {
        $event = new PreviewUrlConvertEvent();

        $this->assertNull($event->getUrl());

        $event->setUrl('http://localhost');

        $this->assertSame('http://localhost', $event->getUrl());
    }
}
